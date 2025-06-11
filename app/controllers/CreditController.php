<?php

namespace App\Controllers;

use App\Models\CreditPack; // Added for CreditPack model

use App\Controllers\BaseController;
use App\Models\User;
use App\Models\CreditTransaction;
use App\Models\Plan;
use App\Libraries\Database;
use Exception;

class CreditController extends BaseController
{
    /**
     * Display the credit purchase page with available credit packs.
     */
    public function index()
    {
        $this->requireLogin('/login?redirect=/credits');
        $creditPacks = CreditPack::getActivePacks();

        $this->view('credits/index', [
            'title' => 'Purchase Credits',
            'creditPacks' => $creditPacks,
            'stripe_publishable_key' => getenv('STRIPE_PUBLISHABLE_KEY')
        ]);
    }
    /**
     * Display the credit purchase page.
     *
     * @return void
     */
    public function index()
    {
        $this->requireLogin('/login?redirect=/credits');
        $user = auth();

        // Fetch available credit packs (plans that offer credits)
        $creditPacks = Plan::where('credits', '>', 0)->get();

        $this->view('credits/index', [
            'title' => 'Buy Credits',
            'user' => $user,
            'creditPacks' => $creditPacks
        ]);
    }

    /**
     * Handle the purchase of credits.
     *
     * @return void
     */
    public function purchase()
    {
        $this->requireLogin('/login?redirect=/credits');
        if (!csrf_verify('credit_purchase_form')) {
            $this->jsonResponse(['error' => 'Invalid security token. Please try again.'], 400);
            return;
        }

        $packId = sanitize_input($_POST['pack_id'] ?? null);
        $paymentMethodId = sanitize_input($_POST['payment_method_id'] ?? null); // For custom flow, not Stripe Checkout

        if (!$packId) {
            $this->jsonResponse(['error' => 'Credit pack ID is required.'], 400);
            return;
        }

        $user = auth();
        $creditPack = CreditPack::find($packId);

        if (!$creditPack || !$creditPack->is_active) {
            $this->jsonResponse(['error' => 'Invalid or inactive credit pack selected.'], 404);
            return;
        }

        if (empty(getenv('STRIPE_SECRET_KEY')) || empty(getenv('STRIPE_PUBLISHABLE_KEY'))) {
            error_log('Stripe keys are not set in the environment variables.');
            $this->jsonResponse(['error' => 'Payment system configuration error.'], 500);
            return;
        }

        $stripe = new \Stripe\StripeClient(getenv('STRIPE_SECRET_KEY'));

        try {
            // Create a Stripe Customer if one doesn't exist
            $stripeCustomerId = $user->stripe_customer_id;
            if (!$stripeCustomerId) {
                $customer = $stripe->customers->create([
                    'email' => $user->email,
                    'name' => $user->name,
                ]);
                $stripeCustomerId = $customer->id;
                User::update($user->id, ['stripe_customer_id' => $stripeCustomerId]);
            }

            // Create a Stripe Checkout Session for one-time purchase
            $checkout_session = $stripe->checkout->sessions->create([
                'customer' => $stripeCustomerId,
                'payment_method_types' => ['card'],
                'line_items' => [[ 
                    'price_data' => [
                        'currency' => 'usd', // Or your preferred currency
                        'product_data' => [
                            'name' => $creditPack->name,
                            'description' => $creditPack->description ?? 'Credit Pack Purchase',
                        ],
                        'unit_amount' => $creditPack->price * 100, // Price in cents
                    ],
                    'quantity' => 1,
                ]],
                'mode' => 'payment',
                'success_url' => base_url('/credits/success?session_id={CHECKOUT_SESSION_ID}'),
                'cancel_url' => base_url('/credits/cancel'),
                'metadata' => [
                    'user_id' => $user->id,
                    'credit_pack_id' => $creditPack->id,
                    'credits_to_award' => $creditPack->credits,
                ]
            ]);

            $this->jsonResponse(['sessionId' => $checkout_session->id]);

        } catch (\Stripe\Exception\ApiErrorException $e) {
            error_log('Stripe API Error during credit purchase: ' . $e->getMessage());
            $this->jsonResponse(['error' => 'Payment processing failed: ' . $e->getMessage()], 500);
        } catch (Exception $e) {
            error_log('Error during credit purchase: ' . $e->getMessage());
            $this->jsonResponse(['error' => 'An unexpected error occurred. Please try again.'], 500);
        }
    }

    /**
     * Handle successful credit purchase (redirect from Stripe Checkout).
     */
    public function success()
    {
        $this->requireLogin('/login');
        $sessionId = sanitize_input($_GET['session_id'] ?? null);
        $user = auth();

        if (!$sessionId) {
            redirect('/credits?error=session_missing');
            return;
        }

        if (empty(getenv('STRIPE_SECRET_KEY'))) {
            error_log('Stripe secret key is not set for success check.');
            redirect('/credits?error=config_error');
            return;
        }

        $stripe = new \Stripe\StripeClient(getenv('STRIPE_SECRET_KEY'));

        try {
            $session = $stripe->checkout->sessions->retrieve($sessionId);

            if ($session->payment_status == 'paid') {
                // Check if this session has already been processed
                $existingTransaction = CreditTransaction::where('reference_id', $session->id)
                                           ->where('reference_type', CreditTransaction::REF_PURCHASE)
                                           ->first();
                if ($existingTransaction) {
                    // Already processed, perhaps user refreshed the success page
                    set_flash_message('success', 'Credits already added to your account!');
                    redirect('/dashboard'); 
                    return;
                }

                $userId = $session->metadata->user_id ?? null;
                $creditPackId = $session->metadata->credit_pack_id ?? null;
                $creditsToAward = $session->metadata->credits_to_award ?? 0;

                if ($userId != $user->id) {
                    error_log("User ID mismatch in Stripe session metadata. Expected: {$user->id}, Got: {$userId}");
                    redirect('/credits?error=user_mismatch');
                    return;
                }

                if ($creditsToAward > 0 && $creditPackId) {
                    $this->beginTransaction();
                    try {
                        $currentUser = User::find($user->id);
                        User::update($user->id, ['credits' => $currentUser->credits + $creditsToAward]);

                        CreditTransaction::create([
                            'user_id' => $user->id,
                            'transaction_type' => CreditTransaction::TYPE_CREDIT_PURCHASE, // Ensure this constant exists or use a string
                            'amount' => $creditsToAward,
                            'description' => 'Purchased ' . $creditsToAward . ' credits.',
                            'related_id' => $creditPackId,
                            'related_type' => 'credit_pack',
                            'reference_id' => $session->id, // Stripe Checkout Session ID
                            'reference_type' => CreditTransaction::REF_PURCHASE,
                            'created_at' => date('Y-m-d H:i:s'),
                        ]);
                        $this->commitTransaction();
                        set_flash_message('success', $creditsToAward . ' credits have been successfully added to your account!');
                        redirect('/dashboard');
                        return;
                    } catch (Exception $e) {
                        $this->rollbackTransaction();
                        error_log('Error awarding credits after purchase: ' . $e->getMessage());
                        redirect('/credits?error=award_failed');
                        return;
                    }
                } else {
                    error_log('Missing metadata for credit award. Session ID: ' . $sessionId);
                    redirect('/credits?error=metadata_missing');
                    return;
                }
            } else {
                 set_flash_message('error', 'Payment was not successful. Please try again or contact support.');
                redirect('/credits?error=payment_not_paid');
                return;
            }
        } catch (\Stripe\Exception\ApiErrorException $e) {
            error_log('Stripe API Error on success page: ' . $e->getMessage());
            redirect('/credits?error=stripe_error');
            return;
        } catch (Exception $e) {
            error_log('Error on success page: ' . $e->getMessage());
            redirect('/credits?error=processing_error');
            return;
        }
    }

    /**
     * Handle canceled credit purchase.
     */
    public function cancel()
    {
        $this->requireLogin('/login');
        set_flash_message('info', 'Your credit purchase was canceled.');
        redirect('/credits');
    }
        // CSRF verification
        if (!csrf_verify('credit_purchase_form')) {
            flash('error', 'Invalid security token. Please try again.');
            redirect('/credits');
        }

        $planId = sanitize_input($_POST['plan_id'] ?? null);
        $user = auth();

        if (!$user) {
            flash('error', 'You must be logged in to purchase credits.');
            redirect('/login');
        }

        if (!$planId) {
            flash('error', 'No credit pack selected.');
            redirect('/credits');
        }

        $plan = Plan::find($planId);

        if (!$plan || $plan->credits <= 0) {
            flash('error', 'Invalid credit pack.');
            redirect('/credits');
        }

        // For now, we'll simulate a successful purchase.
        // In a real application, this would integrate with a payment gateway (e.g., Stripe, PayPal).
        // This is where the payment processing logic would go.

        try {
            $db = Database::getInstance();
            $db->beginTransaction();

            // Add credits to user's account
            $user->credits += $plan->credits;
            $user->save();

            // Record the transaction
            CreditTransaction::create([
                'user_id' => $user->id,
                'amount' => $plan->credits,
                'type' => 'purchase',
                'description' => 'Purchased ' . $plan->credits . ' credits with ' . $plan->name,
                'status' => 'completed'
            ]);

            $db->commit();

            flash('success', 'Successfully purchased ' . $plan->credits . ' credits!');
            redirect('/dashboard'); // Or wherever you want to redirect after purchase

        } catch (Exception $e) {
            $db->rollBack();
            error_log('Credit purchase failed: ' . $e->getMessage());
            flash('error', 'An error occurred during credit purchase. Please try again.');
            redirect('/credits');
        }
    }

    /**
     * Display user's credit transaction history.
     *
     * @return void
     */
    public function history()
    {
        $this->requireLogin('/login?redirect=/credits/history');
        $user = auth();
        $transactions = CreditTransaction::where('user_id', $user->id)
                                        ->orderBy('created_at', 'DESC')
                                        ->get();

        $this->view('credits/history', [
            'title' => 'Credit Transaction History',
            'transactions' => $transactions
        ]);
    }
        $this->requireLogin('/login?redirect=/credits/history');
        $user = auth();

        $transactions = CreditTransaction::where('user_id', $user->id)
                                        ->orderBy('created_at', 'desc')
                                        ->get();

        $this->view('credits/history', [
            'title' => 'Credit History',
            'user' => $user,
            'transactions' => $transactions
        ]);
    }
}