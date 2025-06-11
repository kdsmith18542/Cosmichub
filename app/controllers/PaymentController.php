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
        $planId = $_POST['plan_id'] ?? 0;
        $paymentMethodId = $_POST['payment_method_id'] ?? '';
        $paymentIntentId = $_POST['payment_intent_id'] ?? '';
        
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
                
            // ... handle other event types
            default:
                // Unexpected event type
                http_response_code(400);
                exit('Unexpected event type');
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
            $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
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
