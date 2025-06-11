<?php
/**
 * Payment Controller
 * 
 * Handles payment processing and credit purchases
 */

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\User;
use App\Models\CreditTransaction;
use App\Models\Plan;
use App\Libraries\Database;
use Exception;
use Stripe\StripeClient;
use Stripe\Exception\CardException;
use Stripe\Exception\RateLimitException;
use Stripe\Exception\InvalidRequestException;
use Stripe\Exception\AuthenticationException;
use Stripe\Exception\ApiConnectionException;
use Stripe\Exception\ApiErrorException;
use App\Models\Subscription;

/**
 * Payment Controller
 * 
 * Handles payment processing and credit purchases
 */
class PaymentController extends BaseController
{
    /**
     * @var bool Flag to track if we're in a transaction
     */
    protected $inTransaction = false;
    
    /**
     * Begin a database transaction
     */
    protected function beginTransaction()
    {
        $db = Database::getInstance();
        $db->beginTransaction();
        $this->inTransaction = true;
    }
    
    /**
     * Commit the current transaction
     */
    protected function commitTransaction()
    {
        if ($this->inTransaction) {
            $db = Database::getInstance();
            $db->commit();
            $this->inTransaction = false;
        }
    }

    /**
     * Handle successful subscription invoice payment.
     * This is typically for recurring payments or the first payment if SCA was involved.
     *
     * @param \Stripe\Invoice $invoice
     * @return void
     */
    protected function handleSubscriptionInvoicePaid($invoice)
    {
        $stripeSubscriptionId = $invoice->subscription;
        if (!$stripeSubscriptionId) {
            error_log("Invoice {$invoice->id} paid, but no subscription ID found.");
            return;
        }

        $subscription = Subscription::where('stripe_subscription_id', $stripeSubscriptionId)->first();
        if (!$subscription) {
            error_log("Subscription not found for Stripe subscription ID: {$stripeSubscriptionId} from invoice {$invoice->id}");
            return;
        }

        $user = User::find($subscription->user_id);
        if (!$user) {
            error_log("User not found for subscription ID: {$subscription->id}");
            return;
        }

        $plan = Plan::find($subscription->plan_id);
        if (!$plan) {
            error_log("Plan not found for subscription ID: {$subscription->id}");
            return;
        }

        // Update subscription details from the invoice's subscription data (if available and more current)
        // Stripe often sends a customer.subscription.updated event too, but this can be a fallback.
        $stripeSub = null;
        try {
            $stripe = new StripeClient(getenv('STRIPE_SECRET_KEY'));
            $stripeSub = $stripe->subscriptions->retrieve($stripeSubscriptionId);
        } catch (Exception $e) {
            error_log("Error retrieving Stripe subscription {$stripeSubscriptionId} during invoice.payment_succeeded: " . $e->getMessage());
            // Continue with existing data if retrieval fails, but log it.
        }

        $this->beginTransaction();
        try {
            $subscription->status = $stripeSub ? $stripeSub->status : Subscription::STATUS_ACTIVE; // Default to active if Stripe sub not fetched or status missing
            $subscription->current_period_starts_at = date('Y-m-d H:i:s', $stripeSub ? $stripeSub->current_period_start : $invoice->period_start);
            $subscription->current_period_ends_at = date('Y-m-d H:i:s', $stripeSub ? $stripeSub->current_period_end : $invoice->period_end);
            $subscription->updated_at = date('Y-m-d H:i:s');
            Subscription::update($subscription->id, [
                'status' => $subscription->status,
                'current_period_starts_at' => $subscription->current_period_starts_at,
                'current_period_ends_at' => $subscription->current_period_ends_at,
                'updated_at' => $subscription->updated_at,
            ]);

            // Award credits for renewal if applicable
            // Check if it's a renewal (not the very first payment which is handled in subscribeToPlan or by payment_intent.succeeded)
            // A simple check could be if billing_reason is 'subscription_cycle' or 'subscription_update'
            if ($plan->credits_on_renewal > 0 && isset($invoice->billing_reason) && 
                in_array($invoice->billing_reason, ['subscription_cycle', 'subscription_create', 'subscription_update'])) {
                CreditTransaction::create([
                    'user_id' => $user->id,
                    'transaction_type' => CreditTransaction::TYPE_SUBSCRIPTION_RENEWAL,
                    'amount' => $plan->credits_on_renewal, // Assuming a 'credits_on_renewal' field in Plan model
                    'description' => 'Credits awarded for ' . $plan->name . ' subscription renewal.',
                    'related_id' => $subscription->id,
                    'related_type' => 'subscription',
                    'reference_id' => $invoice->id,
                    'reference_type' => 'invoice',
                    'created_at' => date('Y-m-d H:i:s'),
                ]);
                User::update($user->id, ['credits' => $user->credits + $plan->credits_on_renewal]);
                error_log("Credits awarded for renewal. User: {$user->id}, Plan: {$plan->id}, Credits: {$plan->credits_on_renewal}");
            } else if ($plan->credits > 0 && isset($invoice->billing_reason) && $invoice->billing_reason === 'subscription_create' && $subscription->status === Subscription::STATUS_ACTIVE) {
                 // This handles the case where the initial subscription payment comes via invoice.payment_succeeded (e.g. after SCA)
                 // and credits were not awarded during subscribeToPlan because it returned 'requires_action'.
                 // Check if credits were already awarded for this subscription to prevent double awarding.
                $existingCreditTx = CreditTransaction::where('related_id', $subscription->id)
                                        ->where('related_type', 'subscription')
                                        ->where('transaction_type', CreditTransaction::TYPE_SUBSCRIPTION_AWARD)
                                        ->first();
                if (!$existingCreditTx) {
                    CreditTransaction::create([
                        'user_id' => $user->id,
                        'transaction_type' => CreditTransaction::TYPE_SUBSCRIPTION_AWARD,
                        'amount' => $plan->credits,
                        'description' => 'Credits awarded for ' . $plan->name . ' subscription (via invoice).',
                        'related_id' => $subscription->id,
                        'related_type' => 'subscription',
                        'reference_id' => $invoice->id,
                        'reference_type' => 'invoice',
                        'created_at' => date('Y-m-d H:i:s'),
                    ]);
                    User::update($user->id, ['credits' => $user->credits + $plan->credits]);
                    error_log("Initial credits awarded via invoice. User: {$user->id}, Plan: {$plan->id}, Credits: {$plan->credits}");
                }
            }

            $this->commitTransaction();
            error_log("Subscription invoice paid successfully processed for Stripe sub ID: {$stripeSubscriptionId}, User: {$user->id}");
            // Optionally send a renewal confirmation email

        } catch (Exception $e) {
            $this->rollbackTransaction();
            error_log("Error processing successful subscription invoice {$invoice->id}: " . $e->getMessage());
        }
    }

    /**
     * Handle failed subscription invoice payment.
     *
     * @param \Stripe\Invoice $invoice
     * @return void
     */
    protected function handleSubscriptionInvoiceFailed($invoice)
    {
        $stripeSubscriptionId = $invoice->subscription;
        if (!$stripeSubscriptionId) {
            error_log("Invoice {$invoice->id} payment failed, but no subscription ID found.");
            return;
        }

        $subscription = Subscription::where('stripe_subscription_id', $stripeSubscriptionId)->first();
        if (!$subscription) {
            error_log("Subscription not found for Stripe subscription ID: {$stripeSubscriptionId} from failed invoice {$invoice->id}");
            return;
        }

        // Update local subscription status if necessary (e.g., to 'past_due' or as Stripe dictates)
        // Stripe will typically send a customer.subscription.updated event with the new status.
        // This handler can be used for logging or sending notifications.
        error_log("Subscription invoice payment failed for Stripe sub ID: {$stripeSubscriptionId}, User: {$subscription->user_id}. Invoice ID: {$invoice->id}. Attempt: {$invoice->attempt_count}");

        // Optionally, update local status to 'past_due' if not already handled by customer.subscription.updated
        if ($subscription->status !== Subscription::STATUS_PAST_DUE && $subscription->status !== Subscription::STATUS_CANCELED) {
            Subscription::update($subscription->id, ['status' => Subscription::STATUS_PAST_DUE, 'updated_at' => date('Y-m-d H:i:s')]);
        }
        // Optionally send a payment failed notification to the user
    }

    /**
     * Handle subscription updates from Stripe (e.g., status changes, plan changes).
     *
     * @param \Stripe\Subscription $stripeSubscription
     * @return void
     */
    protected function handleSubscriptionUpdated($stripeSubscription)
    {
        $localSubscription = Subscription::where('stripe_subscription_id', $stripeSubscription->id)->first();
        if (!$localSubscription) {
            error_log("Received customer.subscription.updated for unknown Stripe subscription ID: {$stripeSubscription->id}");
            // Potentially create a new local record if it's a new subscription not caught by subscribeToPlan (less common)
            return;
        }

        $this->beginTransaction();
        try {
            $oldStatus = $localSubscription->status;
            $newStatus = $stripeSubscription->status;

            $updateData = [
                'status' => $newStatus,
                'current_period_starts_at' => date('Y-m-d H:i:s', $stripeSubscription->current_period_start),
                'current_period_ends_at' => date('Y-m-d H:i:s', $stripeSubscription->current_period_end),
                'trial_ends_at' => $stripeSubscription->trial_end ? date('Y-m-d H:i:s', $stripeSubscription->trial_end) : null,
                'cancel_at_period_end' => $stripeSubscription->cancel_at_period_end,
                'canceled_at' => $stripeSubscription->canceled_at ? date('Y-m-d H:i:s', $stripeSubscription->canceled_at) : null,
                'ended_at' => $stripeSubscription->ended_at ? date('Y-m-d H:i:s', $stripeSubscription->ended_at) : null,
                'updated_at' => date('Y-m-d H:i:s'),
            ];
            
            // If plan changed, update plan_id
            // This assumes single subscription item. For multiple items, logic would be more complex.
            if (isset($stripeSubscription->items->data[0]->price->id) && $stripeSubscription->items->data[0]->price->id !== Plan::find($localSubscription->plan_id)->stripe_price_id) {
                $newPlan = Plan::where('stripe_price_id', $stripeSubscription->items->data[0]->price->id)->first();
                if ($newPlan) {
                    $updateData['plan_id'] = $newPlan->id;
                    error_log("Subscription {$localSubscription->id} plan changed to {$newPlan->id}");
                } else {
                    error_log("WARNING: Subscription {$localSubscription->id} plan changed on Stripe to an unknown price ID: {$stripeSubscription->items->data[0]->price->id}");
                }
            }

            Subscription::update($localSubscription->id, $updateData);
            $this->commitTransaction();

            error_log("Subscription {$localSubscription->id} (Stripe ID: {$stripeSubscription->id}) updated. Old status: {$oldStatus}, New status: {$newStatus}");

            // Additional logic based on status change (e.g., email notifications)
            if ($newStatus === Subscription::STATUS_CANCELED && $oldStatus !== Subscription::STATUS_CANCELED) {
                // Send cancellation confirmation email
                error_log("Subscription {$localSubscription->id} was canceled.");
            }
            // Handle other status changes like 'past_due', 'unpaid'

        } catch (Exception $e) {
            $this->rollbackTransaction();
            error_log("Error processing customer.subscription.updated for Stripe ID {$stripeSubscription->id}: " . $e->getMessage());
        }
    }

    /**
     * Handle subscription deletion from Stripe.
     * This means the subscription is permanently gone.
     *
     * @param \Stripe\Subscription $stripeSubscription The Stripe Subscription object from the event
     * @return void
     */
    protected function handleSubscriptionDeleted($stripeSubscription)
    {
        $localSubscription = Subscription::where('stripe_subscription_id', $stripeSubscription->id)->first();
        if (!$localSubscription) {
            error_log("Received customer.subscription.deleted for unknown Stripe subscription ID: {$stripeSubscription->id}");
            return;
        }

        $this->beginTransaction();
        try {
            // Mark the subscription as 'deleted' or 'ended' locally, or actually delete the record
            // depending on business logic. Setting status to 'deleted' and ended_at is often preferred for history.
            Subscription::update($localSubscription->id, [
                'status' => Subscription::STATUS_DELETED, // Or a more specific 'ended' status if you have one
                'ended_at' => $stripeSubscription->ended_at ? date('Y-m-d H:i:s', $stripeSubscription->ended_at) : date('Y-m-d H:i:s'), // Use ended_at from Stripe if available, else now
                'updated_at' => date('Y-m-d H:i:s')
            ]);
            $this->commitTransaction();
            error_log("Subscription {$localSubscription->id} (Stripe ID: {$stripeSubscription->id}) was deleted/ended.");
            // Optionally send a final notification to the user

        } catch (Exception $e) {
            $this->rollbackTransaction();
            error_log("Error processing customer.subscription.deleted for Stripe ID {$stripeSubscription->id}: " . $e->getMessage());
        }
    }
    
    /**
     * Rollback the current transaction
     */
    protected function rollbackTransaction()
    {
        if ($this->inTransaction) {
            $db = Database::getInstance();
            $db->rollBack();
            $this->inTransaction = false;
        }
    }
    
    /**
     * Send a JSON response
     * 
     * @param array $data Response data
     * @param int $statusCode HTTP status code
     */
    protected function jsonResponse($data, $statusCode = 200)
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    /**
     * Display the subscription checkout form.
     *
     * @param int $planId
     * @return void
     */
    public function subscriptionCheckoutForm($planId)
    {
        $this->requireLogin('/login?redirect=/subscription/checkout/' . $planId);
        $user = auth();

        $plan = Plan::find($planId);
        if (!$plan || !$plan->is_active || !in_array($plan->billing_cycle, ['month', 'year'])) {
            // Optionally, set a flash message for the user
            redirect('/payment/plans'); // Redirect if plan is not valid for subscription
            return;
        }

        if (empty(getenv('STRIPE_PUBLISHABLE_KEY'))) {
            error_log('Stripe publishable key is not set in the environment variables.');
            // Optionally, set a flash message for the user
            $this->view('payment/checkout_error', ['title' => 'Configuration Error', 'message' => 'Payment system is currently unavailable.']);
            return;
        }

        $this->view('payment/subscription_checkout', [
            'title' => 'Subscribe to ' . htmlspecialchars($plan->name),
            'plan' => $plan,
            'user' => $user,
            'stripe_publishable_key' => getenv('STRIPE_PUBLISHABLE_KEY')
        ]);
    }

    /**
     * Handle subscription to a plan.
     *
     * @return void
     */
    public function subscribeToPlan()
    {
        // Verify CSRF token
        if (!csrf_verify('payment_form', false)) { // Assuming the same CSRF token name, adjust if different for subscription form
            $this->jsonResponse(['error' => 'Invalid security token. Please try again.'], 400);
            return;
        }

        $planId = sanitize_input($_POST['plan_id'] ?? null);
        $paymentMethodId = sanitize_input($_POST['payment_method_id'] ?? null);

        if (!$planId || !$paymentMethodId) {
            $this->jsonResponse(['error' => 'Plan ID and Payment Method ID are required.'], 400);
            return;
        }

        $user = auth();
        if (!$user) {
            $this->jsonResponse(['error' => 'User not authenticated.'], 401);
            return;
        }

        $plan = Plan::find($planId);
        if (!$plan || !$plan->is_active) {
            $this->jsonResponse(['error' => 'Invalid or inactive plan selected.'], 404);
            return;
        }

        if (empty(getenv('STRIPE_SECRET_KEY'))) {
            error_log('Stripe secret key is not set in the environment variables.');
            $this->jsonResponse(['error' => 'Payment system configuration error.'], 500);
            return;
        }

        $stripe = new StripeClient(getenv('STRIPE_SECRET_KEY'));

        try {
            $this->beginTransaction();

            // Check if user already has an active subscription to this plan or any plan
            // This logic might need adjustment based on business rules (e.g., allow multiple subscriptions, or only one active at a time)
            $existingSubscription = Subscription::where('user_id', $user->id)
                                        ->where('status', Subscription::STATUS_ACTIVE)
                                        ->first();

            if ($existingSubscription) {
                 // Optionally, handle upgrades/downgrades or prevent new subscriptions if one is active
                $this->jsonResponse(['error' => 'You already have an active subscription.'], 400);
                $this->rollbackTransaction();
                return;
            }

            // Create the Stripe Customer if one doesn't exist
            $stripeCustomerId = $user->stripe_customer_id;
            if (!$stripeCustomerId) {
                $customer = $stripe->customers->create([
                    'email' => $user->email,
                    'name' => $user->name,
                    'payment_method' => $paymentMethodId,
                    'invoice_settings' => [
                        'default_payment_method' => $paymentMethodId,
                    ],
                ]);
                $stripeCustomerId = $customer->id;
                User::update($user->id, ['stripe_customer_id' => $stripeCustomerId]);
            } else {
                // Attach the payment method to the existing customer
                $stripe->paymentMethods->attach($paymentMethodId, ['customer' => $stripeCustomerId]);
                // Set it as the default payment method for the customer's invoices
                $stripe->customers->update($stripeCustomerId, [
                    'invoice_settings' => [
                        'default_payment_method' => $paymentMethodId,
                    ],
                ]);
            }

            // Create the Stripe Subscription
            $subscriptionParams = [
                'customer' => $stripeCustomerId,
                'items' => [['price' => $plan->stripe_price_id]], // Ensure $plan->stripe_price_id is available and correct
                'expand' => ['latest_invoice.payment_intent'],
                'payment_behavior' => 'default_incomplete', // Allows handling of SCA post-creation
            ];

            // Example: Add trial period if plan has one and it's configured in the Plan model
            // if (method_exists($plan, 'getTrialPeriodDays') && $plan->getTrialPeriodDays() > 0) {
            //    $subscriptionParams['trial_period_days'] = $plan->getTrialPeriodDays();
            // }

            $stripeSubscription = $stripe->subscriptions->create($subscriptionParams);

            // If the subscription requires further action (e.g., 3D Secure for SCA)
            if ($stripeSubscription->status === 'incomplete' && 
                isset($stripeSubscription->latest_invoice->payment_intent->status) &&
                $stripeSubscription->latest_invoice->payment_intent->status === 'requires_action') {
                
                // Commit customer creation/update part, so customer ID is saved.
                $this->commitTransaction(); 
                
                $this->jsonResponse([
                    'requires_action' => true,
                    'payment_intent_client_secret' => $stripeSubscription->latest_invoice->payment_intent->client_secret,
                    'subscription_id' => $stripeSubscription->id,
                    'message' => 'Subscription requires further authentication.'
                ], 200);
                return;
            }

            // Check if subscription is active or trialing, otherwise it's an issue.
            if (!in_array($stripeSubscription->status, [Subscription::STATUS_ACTIVE, Subscription::STATUS_TRIALING])) {
                error_log("Subscription creation for user {$user->id}, plan {$plan->id} resulted in unexpected status: {$stripeSubscription->status}. Stripe subscription ID: {$stripeSubscription->id}.");
                // Attempt to cancel it on Stripe's side to avoid unexpected charges.
                try {
                    $stripe->subscriptions->cancel($stripeSubscription->id, []);
                    error_log("Successfully cancelled Stripe subscription {$stripeSubscription->id} due to unexpected initial status.");
                } catch (Exception $cancelEx) {
                    error_log("Failed to cancel Stripe subscription {$stripeSubscription->id} after unexpected initial status: " . $cancelEx->getMessage());
                }
                
                $this->rollbackTransaction();
                $this->jsonResponse(['error' => 'Subscription creation failed. Unexpected status: ' . $stripeSubscription->status], 400);
                return;
            }

            // Store subscription in local database
            $dbSubscription = Subscription::create([
                'user_id' => $user->id,
                'plan_id' => $plan->id,
                'stripe_subscription_id' => $stripeSubscription->id,
                'stripe_customer_id' => $stripeCustomerId,
                'status' => $stripeSubscription->status, // 'active' or 'trialing'
                'trial_ends_at' => $stripeSubscription->trial_end ? date('Y-m-d H:i:s', $stripeSubscription->trial_end) : null,
                'current_period_starts_at' => date('Y-m-d H:i:s', $stripeSubscription->current_period_start),
                'current_period_ends_at' => date('Y-m-d H:i:s', $stripeSubscription->current_period_end),
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

            if (!$dbSubscription) {
                error_log("CRITICAL: Failed to save subscription locally for user {$user->id}, Stripe sub ID {$stripeSubscription->id}. Attempting to cancel Stripe subscription.");
                try {
                    $stripe->subscriptions->cancel($stripeSubscription->id, []);
                     error_log("Successfully cancelled Stripe subscription {$stripeSubscription->id} due to local save failure.");
                } catch (Exception $cancelEx) {
                    error_log("CRITICAL: Failed to cancel Stripe subscription {$stripeSubscription->id} after local save failure: " . $cancelEx->getMessage());
                }
                $this->rollbackTransaction();
                $this->jsonResponse(['error' => 'Failed to save subscription locally. The payment provider has been notified.'], 500);
                return;
            }

            // Grant credits if the plan includes them and subscription is active/trialing
            if ($plan->credits > 0 && in_array($dbSubscription->status, [Subscription::STATUS_ACTIVE, Subscription::STATUS_TRIALING])) {
                CreditTransaction::create([
                    'user_id' => $user->id,
                    'transaction_type' => CreditTransaction::TYPE_SUBSCRIPTION_AWARD,
                    'amount' => $plan->credits,
                    'description' => 'Credits awarded for ' . $plan->name . ' subscription.',
                    'related_id' => $dbSubscription->id,
                    'related_type' => 'subscription',
                    'created_at' => date('Y-m-d H:i:s'),
                ]);
                
                $userModel = User::find($user->id);
                if ($userModel) {
                    $newCreditBalance = $userModel->credits + $plan->credits;
                    User::update($user->id, ['credits' => $newCreditBalance]);
                }
            }

            $this->commitTransaction();
            
            $this->jsonResponse([
                'success' => true,
                'message' => 'Subscription successful!',
                'subscription_id' => $stripeSubscription->id,
                'local_subscription_id' => $dbSubscription->id,
                'status' => $stripeSubscription->status,
                'redirect_url' => '/subscription'
            ], 200);

        } catch (CardException $e) {
            $this->rollbackTransaction();
            $this->jsonResponse(['error' => $e->getError()->message, 'code' => $e->getError()->code], 402);
        } catch (RateLimitException | InvalidRequestException | AuthenticationException | ApiConnectionException | ApiErrorException $e) {
            $this->rollbackTransaction();
            error_log('Stripe API Error: ' . $e->getMessage());
            $this->jsonResponse(['error' => 'Could not process subscription with Stripe. Please try again later or contact support.'], 500);
        } catch (Exception $e) {
            $this->rollbackTransaction();
            error_log('Subscription Error: ' . $e->getMessage());
            $this->jsonResponse(['error' => 'An unexpected error occurred: ' . $e->getMessage()], 500);
        }
    }
    
    /**
     * Show available credit plans
     * 
     * @return void
     */
    public function plans()
    {
        // Require authentication
        $this->requireLogin('/login?redirect=/credits');
        
        // Get all active plans
        $plans = Plan::where('is_active', true)
            ->orderBy('price', 'asc')
            ->get();
            
        $data = [
            'title' => 'Buy Credits',
            'plans' => $plans,
            'user' => auth()
        ];
        
        $this->view('payment/plans', $data);
    }
    
    /**
     * Show checkout form
     * 
     * @param int $planId
     * @return void
     */
    public function checkout($planId)
    {
        // Require login
        if (!auth('id')) {
            $this->redirect('login?redirect=' . urlencode($this->currentUrl()));
            return;
        }
        
        // Get the plan
        $plan = Plan::find($planId);
        
        if (!$plan || !$plan->is_active) {
            $this->setFlash('error', 'Invalid plan selected.');
            $this->redirect('payment/plans');
            return;
        }
        
        // Render view
        // Ensure Stripe public key is available
        if (empty(getenv('STRIPE_PUBLIC_KEY'))) {
            error_log('Stripe public key is not set in the environment variables.');
            $this->setFlash('error', 'Payment system configuration error. Please contact support.');
            $this->redirect('payment/plans');
            return;
        }

        $this->view('payment/checkout', [
            'title' => 'Checkout',
            'plan' => $plan,
            'stripePublicKey' => getenv('STRIPE_PUBLIC_KEY')
        ]);
    }
    
    /**
     * Process payment
     * 
     * @return void
     */
    public function process()
    {
        // Verify CSRF token
        if (!csrf_verify('payment_form', false)) {
            $this->jsonResponse(['error' => 'Invalid security token. Please try again.'], 400);
            return;
        }
        
        // Get input
        $planId = (int)sanitize_input($_POST['plan_id'] ?? '0');
        $paymentMethodId = sanitize_input($_POST['payment_method_id'] ?? '');
        $paymentIntentId = sanitize_input($_POST['payment_intent_id'] ?? '');
        
        // Validate input
        if (empty($planId) || (empty($paymentMethodId) && empty($paymentIntentId))) {
            $this->jsonResponse(['error' => 'Missing required fields.'], 400);
            return;
        }
        
        try {
            // Get the plan
            $plan = Plan::find($planId);
            
            if (!$plan || !$plan->is_active) {
                throw new Exception('Invalid plan selected.');
            }
            
            // Get the current user
            $user = User::find(auth('id'));
            
            if (!$user) {
                throw new Exception('User not found. Please log in again.');
            }
            
            // Initialize Stripe
            if (empty(getenv('STRIPE_SECRET_KEY'))) {
                error_log('Stripe secret key is not set in the environment variables.');
                throw new Exception('Payment system configuration error. Please contact support.');
            }
            $stripe = new \Stripe\StripeClient([
                'api_key' => getenv('STRIPE_SECRET_KEY'),
                'stripe_version' => '2023-10-16',
            ]);
            
            $paymentDetails = [];
            
            // Handle payment intent confirmation if we have an intent ID
            if (!empty($paymentIntentId)) {
                $intent = $stripe->paymentIntents->retrieve($paymentIntentId);
                $paymentDetails['payment_intent'] = $intent->id;
                $paymentDetails['payment_method'] = $intent->payment_method;
                $paymentDetails['receipt_url'] = $intent->charges->data[0]->receipt_url ?? null;
            }
            // Otherwise create and confirm a new payment intent
            else {
                // Create a PaymentIntent with the order amount and currency
                $intent = $stripe->paymentIntents->create([
                    'amount' => (int)($plan->price * 100), // Amount in cents
                    'currency' => 'usd',
                    'payment_method' => $paymentMethodId,
                    'confirmation_method' => 'manual',
                    'confirm' => true, // Confirm immediately
                    'return_url' => url('/payment/success?session_id={CHECKOUT_SESSION_ID}'), // Required for some payment methods like SEPA Direct Debit
                    'confirm' => true,
                    'return_url' => url('/payment/success'),
                    'metadata' => [
                        'user_id' => $user->id,
                        'plan_id' => $plan->id,
                        'plan_name' => $plan->name,
                        'credits' => $plan->credits,
                    ],
                    'description' => 'Purchase of ' . $plan->name . ' (' . $plan->credits . ' credits)',
                ]);
                
                // If the payment requires additional actions, return the client secret
                if ($intent->status === 'requires_action' && $intent->next_action->type === 'use_stripe_sdk') {
                    $this->jsonResponse([
                        'requires_action' => true,
                        'payment_intent_client_secret' => $intent->client_secret,
                        'payment_intent_id' => $intent->id
                    ]);
                    return;
                }
                
                $paymentDetails['payment_intent'] = $intent->id;
                $paymentDetails['payment_method'] = $paymentMethodId;
                $paymentDetails['receipt_url'] = $intent->charges->data[0]->receipt_url ?? null;
            }
            
            // Verify the payment was successful
            if ($intent->status !== 'succeeded') {
                throw new Exception('Payment was not successful. Status: ' . $intent->status);
            }
            
            // Begin database transaction
            $this->beginTransaction();
            
            // Add credits to user's account
            $creditAdded = $user->addCredits($plan->credits, 'purchase', [
                'plan_id' => $plan->id,
                'plan_name' => $plan->name,
                'payment_method' => $paymentDetails['payment_method'],
                'payment_intent' => $paymentDetails['payment_intent'],
                'amount_paid' => $plan->price,
                'receipt_url' => $paymentDetails['receipt_url']
            ]);
            
            if (!$creditAdded) {
                throw new Exception('Failed to add credits to your account. Please contact support.');
            }
            
            // If this is a subscription, update the user's subscription status
            if ($plan->billing_cycle !== 'one_time') {
                $user->subscription_status = 'active';
                $user->subscription_ends_at = $plan->billing_cycle === 'monthly' 
                    ? date('Y-m-d H:i:s', strtotime('+1 month')) 
                    : date('Y-m-d H:i:s', strtotime('+1 year'));
                
                if (!$user->save()) {
                    throw new Exception('Failed to update subscription status.');
                }
            }
            
            // Commit transaction
            $this->commitTransaction();
            
            // Return success response
            $this->jsonResponse([
                'success' => true,
                'message' => 'Payment processed successfully!',
                'credits' => $user->getCreditBalance(),
                'redirect' => url('/payment/success?txn=' . $paymentDetails['payment_intent'])
            ]);
            
        } catch (\Stripe\Exception\CardException $e) {
            // Card was declined
            $error = $e->getError();
            $this->jsonResponse([
                'error' => $error->message ?? 'Your card was declined. Please try again with a different payment method.'
            ], 400);
        } catch (\Stripe\Exception\RateLimitException $e) {
            // Too many requests made to the API too quickly
            error_log('Stripe RateLimitException: ' . $e->getMessage());
            $this->jsonResponse([
                'error' => 'Too many requests. Please try again later.'
            ], 429);
        } catch (\Stripe\Exception\InvalidRequestException $e) {
            // Invalid parameters were supplied to Stripe's API
            error_log('Stripe InvalidRequestException: ' . $e->getMessage());
            $this->jsonResponse([
                'error' => 'Invalid payment details. Please check your information and try again.'
            ], 400);
        } catch (\Stripe\Exception\AuthenticationException $e) {
            // Authentication with Stripe's API failed
            error_log('Stripe AuthenticationException: ' . $e->getMessage());
            $this->jsonResponse([
                'error' => 'Unable to process payment. Please contact support.'
            ], 500);
        } catch (\Stripe\Exception\ApiConnectionException $e) {
            // Network communication with Stripe failed
            error_log('Stripe ApiConnectionException: ' . $e->getMessage());
            $this->jsonResponse([
                'error' => 'Network error. Please check your connection and try again.'
            ], 500);
        } catch (\Stripe\Exception\ApiErrorException $e) {
            // Generic error
            error_log('Stripe ApiErrorException: ' . $e->getMessage());
            $this->jsonResponse([
                'error' => 'An error occurred while processing your payment. Please try again.'
            ], 500);
        } catch (Exception $e) {
            // Rollback transaction on error
            if ($this->inTransaction) {
                $this->rollbackTransaction();
            }
            
            // Log the error
            error_log('Payment processing error: ' . $e->getMessage());
            
            // Return error response
            $this->jsonResponse([
                'error' => $e->getMessage() ?: 'An error occurred while processing your payment. Please try again.'
            ], 500);
        }
    }
    
    /**
     * Payment success callback
     * 
     * @return void
     */
    public function success()
    {
        $this->requireLogin();
        
        $data = [
            'title' => 'Payment Successful',
            'message' => 'Thank you for your purchase! Your credits have been added to your account.'
        ];
        
        $this->view('payment/success', $data);
    }
    
    /**
     * Handle payment cancellation
     * 
     * @return void
     */
    public function cancel()
    {
        // Require login
        if (!$this->isLoggedIn()) {
            $this->redirect('login?redirect=' . urlencode($this->currentUrl()));
            return;
        }
        
        try {
            // Set flash message
            $this->setFlash('info', 'Your payment was cancelled. No charges were made to your account.');
            
            // Redirect to plans page
            $this->redirect('payment/plans');
            
        } catch (Exception $e) {
            error_log('Payment cancel error: ' . $e->getMessage());
            $this->setFlash('error', 'An error occurred while processing your cancellation.');
            $this->redirect('dashboard');
        }
    }
    
    /**
     * Handle Stripe webhook events
     * 
     * @return void
     */
    public function webhook()
    {
        // Get the webhook secret from environment
        $endpoint_secret = getenv('STRIPE_WEBHOOK_SECRET');
        
        // Get the request payload
        $payload = @file_get_contents('php://input');
        $sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';
        $event = null;
        
        try {
            // Verify the webhook signature
            $event = \Stripe\Webhook::constructEvent(
                $payload, $sig_header, $endpoint_secret
            );
        } catch(\UnexpectedValueException $e) {
            // Invalid payload
            http_response_code(400);
            exit('Invalid payload');
        } catch(\Stripe\Exception\SignatureVerificationException $e) {
            // Invalid signature
            http_response_code(400);
            exit('Invalid signature');
        }
        
        // Handle the event
        switch ($event->type) {
            case 'payment_intent.succeeded':
                $paymentIntent = $event->data->object;
                $this->handlePaymentIntentSucceeded($paymentIntent);
                break;
                
            case 'payment_intent.payment_failed':
                $paymentIntent = $event->data->object;
                $this->handlePaymentIntentFailed($paymentIntent);
                break;
                
            case 'charge.refunded':
                $charge = $event->data->object;
                $this->handleChargeRefunded($charge);
                break;

            case 'invoice.payment_succeeded':
                $invoice = $event->data->object;
                // Check if this invoice is for a subscription
                if (isset($invoice->subscription) && $invoice->subscription) {
                    $this->handleSubscriptionInvoicePaid($invoice);
                }
                break;

            case 'invoice.payment_failed':
                $invoice = $event->data->object;
                if (isset($invoice->subscription) && $invoice->subscription) {
                    $this->handleSubscriptionInvoiceFailed($invoice);
                }
                break;

            case 'customer.subscription.updated':
                $subscription = $event->data->object;
                $this->handleSubscriptionUpdated($subscription);
                break;

            case 'customer.subscription.deleted':
                $subscription = $event->data->object;
                $this->handleSubscriptionDeleted($subscription);
                break;
                
            // ... handle other event types
            default:
                error_log('Webhook received for unhandled event type: ' . $event->type);
                // Unexpected event type
                http_response_code(200); // Still acknowledge receipt to Stripe
                exit('Unhandled event type');
        }
        
        // Return a 200 response to acknowledge receipt of the event
        http_response_code(200);
    }
    
    /**
     * Handle successful payment intent
     * 
     * @param \Stripe\PaymentIntent $paymentIntent
     * @return void
     */
    protected function handlePaymentIntentSucceeded($paymentIntent)
    {
        // Get the payment intent ID
        $paymentIntentId = $paymentIntent->id;
        
        // Log the successful payment
        error_log('Payment succeeded: ' . $paymentIntentId);
        
        // Get the user ID and plan ID from metadata
        $userId = $paymentIntent->metadata->user_id ?? null;
        $planId = $paymentIntent->metadata->plan_id ?? null;
        
        if (!$userId || !$planId) {
            error_log('Missing user_id or plan_id in payment intent metadata');
            return;
        }
        
        // Get the user and plan
        $user = User::find($userId);
        $plan = Plan::find($planId);
        
        if (!$user || !$plan) {
            error_log('User or plan not found');
            return;
        }
        
        // Add credits to user's account
        $user->addCredits($plan->credits, 'purchase', [
            'plan_id' => $plan->id,
            'plan_name' => $plan->name,
            'payment_intent' => $paymentIntentId,
            'amount_paid' => $plan->price,
            'receipt_url' => $paymentIntent->charges->data[0]->receipt_url ?? null
        ]);
        
        // Send confirmation email
        $this->sendPaymentConfirmationEmail($user, $plan, $paymentIntent);
    }
    
    /**
     * Handle failed payment intent
     * 
     * @param \Stripe\PaymentIntent $paymentIntent
     * @return void
     */
    protected function handlePaymentIntentFailed($paymentIntent)
    {
        // Get the payment intent ID
        $paymentIntentId = $paymentIntent->id;
        $error = $paymentIntent->last_payment_error ?? null;
        
        // Log the failed payment
        error_log('Payment failed: ' . $paymentIntentId . ' - ' . ($error ? $error->message : 'Unknown error'));
        
        // Get the user ID from metadata
        $userId = $paymentIntent->metadata->user_id ?? null;
        
        if ($userId) {
            // Send failure notification to user
            $this->sendPaymentFailedEmail($userId, $paymentIntent);
        }
    }
    
    /**
     * Handle charge refund
     * 
     * @param \Stripe\Charge $charge
     * @return void
     */
    protected function handleChargeRefunded($charge)
    {
        $paymentIntentId = $charge->payment_intent;
        $refundAmount = $charge->amount_refunded / 100; // Convert from cents
        
        // Find the original transaction
        $transaction = CreditTransaction::where('reference_id', $paymentIntentId)
            ->where('reference_type', 'payment_intent')
            ->first();
            
        if ($transaction) {
            // Create a refund transaction
            $user = User::find($transaction->user_id);
            
            if ($user) {
                $user->addCredits(-$transaction->amount, 'refund', [
                    'reference_id' => $charge->id,
                    'reference_type' => 'refund',
                    'original_payment_intent' => $paymentIntentId,
                    'amount_refunded' => $refundAmount
                ]);
                
                // Send refund confirmation email
                $this->sendRefundConfirmationEmail($user, $refundAmount, $charge);
            }
        }
    }
    
    /**
     * Send payment confirmation email
     * 
     * @param User $user
     * @param Plan $plan
     * @param \Stripe\PaymentIntent $paymentIntent
     * @return bool
     */
    protected function sendPaymentConfirmationEmail($user, $plan, $paymentIntent)
    {
        try {
            $to = $user->email;
            $subject = 'Payment Confirmation - ' . SITE_NAME;
            
            $message = 'Hello ' . htmlspecialchars($user->first_name) . ",\n\n";
            $message .= "Thank you for your purchase! Your payment has been processed successfully.\n\n";
            $message .= "Plan: " . htmlspecialchars($plan->name) . "\n";
            $message .= "Credits Added: " . number_format($plan->credits) . "\n";
            $message .= "Amount Paid: $" . number_format($plan->price, 2) . "\n";
            $message .= "Transaction ID: " . $paymentIntent->id . "\n\n";
            $message .= "Your new credit balance is: " . number_format($user->credits + $plan->credits) . "\n\n";
            $message .= "You can view your transaction history in your account dashboard.\n\n";
            $message .= "Thank you for using " . SITE_NAME . "!\n";
            
            $headers = 'From: ' . SITE_EMAIL . "\r\n" .
                      'Reply-To: ' . SITE_EMAIL . "\r\n" .
                      'X-Mailer: PHP/' . phpversion();
            
            return mail($to, $subject, $message, $headers);
            
        } catch (Exception $e) {
            error_log('Failed to send payment confirmation email: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Send payment failed email
     * 
     * @param int $userId
     * @param \Stripe\PaymentIntent $paymentIntent
     * @return bool
     */
    protected function sendPaymentFailedEmail($userId, $paymentIntent)
    {
        $user = User::find($userId);
        if (!$user) {
            return false;
        }
        
        try {
            $to = $user->email;
            $subject = 'Payment Failed - ' . SITE_NAME;
            
            $error = $paymentIntent->last_payment_error ?? null;
            $errorMessage = $error ? $error->message : 'Unknown error';
            
            $message = 'Hello ' . htmlspecialchars($user->first_name) . ",\n\n";
            $message .= "We're sorry, but there was a problem processing your payment.\n\n";
            $message .= "Error: " . htmlspecialchars($errorMessage) . "\n";
            $message .= "Transaction ID: " . $paymentIntent->id . "\n\n";
            $message .= "Please try again or contact support if the problem persists.\n\n";
            $message .= "Thank you for using " . SITE_NAME . "!\n";
            
            $headers = 'From: ' . SITE_EMAIL . "\r\n" .
                      'Reply-To: ' . SITE_EMAIL . "\r\n" .
                      'X-Mailer: PHP/' . phpversion();
            
            return mail($to, $subject, $message, $headers);
            
        } catch (Exception $e) {
            error_log('Failed to send payment failed email: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Show transaction history
     * 
     * @return void
     */
    public function history()
    {
        // Require login
        if (!$this->isLoggedIn()) {
            $this->redirect('login?redirect=' . urlencode($this->currentUrl()));
            return;
        }
        
        try {
            // Get current user
            $user = $this->getCurrentUser();
            
            // Pagination
            $perPage = 15;
            $page = isset($_GET['page']) ? (int)sanitize_input($_GET['page']) : 1;
            $page = max(1, $page); // Ensure page is at least 1
            
            // Get total count
            $total = CreditTransaction::where('user_id', $user->id)->count();
            $totalPages = ceil($total / $perPage);
            
            // Get transactions with pagination
            $transactions = CreditTransaction::where('user_id', $user->id)
                ->orderBy('created_at', 'desc')
                ->skip(($page - 1) * $perPage)
                ->take($perPage)
                ->get();
            
            // Calculate running balance for each transaction
            $runningBalance = $user->credits;
            foreach ($transactions as $txn) {
                $txn->running_balance = $runningBalance;
                $runningBalance -= $txn->amount;
            }
            
            // Prepare pagination data for view
            $pagination = [
                'current_page' => $page,
                'total_pages' => $totalPages,
                'total_items' => $total,
                'per_page' => $perPage
            ];
            
            // Set view data
            $this->viewData['title'] = 'Transaction History';
            $this->viewData['transactions'] = $transactions;
            $this->viewData['user'] = $user;
            $this->viewData['pagination'] = $pagination;
            
            // Render view
            $this->render('payment/history', $this->viewData);
            
        } catch (Exception $e) {
            error_log('Failed to load transaction history: ' . $e->getMessage());
            $this->setFlash('error', 'Failed to load transaction history. Please try again later.');
            $this->redirect('dashboard');
        }
    }
    
    /**
     * Send refund confirmation email
     * 
     * @param User $user
     * @param float $amount
     * @param \Stripe\Charge $charge
     * @return bool
     */
    protected function sendRefundConfirmationEmail($user, $amount, $charge)
    {
        try {
            $to = $user->email;
            $subject = 'Refund Processed - ' . SITE_NAME;
            
            $message = 'Hello ' . htmlspecialchars($user->first_name) . ",\n\n";
            $message .= "A refund has been processed for your recent payment.\n\n";
            $message .= "Amount Refunded: $" . number_format($amount, 2) . "\n";
            $message .= "Transaction ID: " . $charge->payment_intent . "\n";
            $message .= "Refund ID: " . $charge->id . "\n\n";
            $message .= "The refund may take 5-10 business days to appear in your account.\n\n";
            $message .= "If you have any questions, please contact our support team.\n\n";
            $message .= "Thank you for using " . SITE_NAME . "!\n";
            
            $headers = 'From: ' . SITE_EMAIL . "\r\n" .
                      'Reply-To: ' . SITE_EMAIL . "\r\n" .
                      'X-Mailer: PHP/' . phpversion();
            
            return mail($to, $subject, $message, $headers);
            
        } catch (Exception $e) {
            error_log('Failed to send refund confirmation email: ' . $e->getMessage());
            return false;
        }
    }
}
