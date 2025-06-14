<?php
/**
 * Payment Controller
 * 
 * Handles payment processing and credit purchases
 */

namespace App\Controllers;

use App\Core\Controller\Controller;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\View\View;
use App\Services\PaymentService;
use App\Services\AuthService;
use App\Exceptions\ValidationException;
use Psr\Log\LoggerInterface;
use Exception;
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
class PaymentController extends Controller
{
    /** @var PaymentService */
    private $paymentService;
    
    /** @var AuthService */
    private $authService;
    
    /** @var LoggerInterface */
    private $logger;

    public function __construct(PaymentService $paymentService, AuthService $authService, LoggerInterface $logger)
    {
        parent::__construct();
        $this->paymentService = $paymentService;
        $this->authService = $authService;
        $this->logger = $logger;
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
        $this->paymentService->handleSubscriptionInvoicePaid($invoice);
    }

    /**
     * Handle failed subscription invoice payment.
     *
     * @param \Stripe\Invoice $invoice
     * @return void
     */
    protected function handleSubscriptionInvoiceFailed($invoice)
    {
        $this->paymentService->handleSubscriptionInvoiceFailed($invoice);
    }

    /**
     * Handle subscription updates from Stripe (e.g., status changes, plan changes).
     *
     * @param \Stripe\Subscription $stripeSubscription
     * @return void
     */
    protected function handleSubscriptionUpdated($stripeSubscription)
    {
        $this->paymentService->handleSubscriptionUpdated($stripeSubscription);
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
        $this->paymentService->handleSubscriptionDeleted($stripeSubscription);
    }
    
    /**
     * Rollback any active transaction
     */
    private function rollbackTransaction()
    {
        $this->paymentService->rollbackTransaction();
    }
    


    /**
     * Display the subscription checkout form.
     *
     * @param Request $request
     * @param int $planId
     * @return Response
     */
    public function subscriptionCheckoutForm(Request $request, Response $response, $planId): Response
    {
        if (!$this->authService->isLoggedIn($request)) {
            return $response->redirect('/login?redirect=/subscription/checkout/' . $planId);
        }
        $user = $this->authService->getCurrentUser($request);

        $result = $this->paymentService->getPlan($planId);
        if (!$result['success']) {
            $request->flash('error', $result['message']);
            return $response->redirect('/payment/plans');
        }
        
        $plan = $result['data'];
        if (!in_array($plan->billing_cycle, ['month', 'year'])) {
            $request->flash('error', 'Invalid plan for subscription.');
            return $response->redirect('/payment/plans');
        }

        if (empty(getenv('STRIPE_PUBLISHABLE_KEY'))) {
            $this->logger->error('Stripe publishable key is not set in the environment variables.');
            // Optionally, set a flash message for the user
            return $response->render('payment/checkout_error', ['title' => 'Configuration Error', 'message' => 'Payment system is currently unavailable.']);
        }

        return $response->render('payment/subscription_checkout', [
            'title' => 'Subscribe to ' . htmlspecialchars($plan->name),
            'plan' => $plan,
            'user' => $user,
            'stripe_publishable_key' => getenv('STRIPE_PUBLISHABLE_KEY')
        ]);
    }

    /**
     * Handle subscription to a plan.
     *
     * @param Request $request
     * @return Response
     */
    public function subscribeToPlan(Request $request, Response $response): Response
    {
        // Verify CSRF token
        if (!$this->verifyCsrf($request)) {
            return $response->json(['error' => 'Invalid security token. Please try again.'], 400);
        }

        $planId = $request->input('plan_id');
        $paymentMethodId = $request->input('payment_method_id');

        if (!$planId || !$paymentMethodId) {
            return $response->json(['error' => 'Plan ID and Payment Method ID are required.'], 400);
        }

        $user = $this->authService->getCurrentUser($request);
        if (!$user) {
            return $response->json(['error' => 'User not authenticated.'], 401);
        }

        try {
            $result = $this->paymentService->createSubscription($user, $planId, $paymentMethodId);
            return $response->json($result['data'], $result['status_code']);
        } catch (ValidationException $e) {
            return $response->json(['error' => $e->getMessage()], 400);
        } catch (Exception $e) {
            $this->logger->error("Subscription creation failed: " . $e->getMessage());
            return $response->json(['error' => 'Subscription creation failed'], 500);
        }
    }
    
    /**
     * Show available credit plans
     * 
     * @param Request $request
     * @return Response
     */
    public function plans(Request $request, Response $response): Response
    {
        // Require authentication
        if (!$this->authService->isLoggedIn($request)) {
            return $response->redirect('/login?redirect=/credits');
        }
        
        // Get all active plans
        $result = $this->paymentService->getActivePlans();
        
        if (!$result['success']) {
            $request->flash('error', $result['message']);
            return $response->redirect('/dashboard');
        }
            
        $data = [
            'title' => 'Buy Credits',
            'plans' => $result['data'],
            'user' => $this->authService->getCurrentUser($request)
        ];
        
        return $response->render('payment/plans', $data);
    }
    
    /**
     * Show checkout form
     * 
     * @param Request $request
     * @param int $planId
     * @return Response
     */
    public function checkout(Request $request, Response $response, $planId): Response
    {
        if (!$this->authService->isLoggedIn($request)) {
            return $response->redirect('/login?redirect=/payment/checkout/' . $planId);
        }
        $user = $this->authService->getCurrentUser($request);

        $result = $this->paymentService->getPlan($planId);
        if (!$result['success']) {
            $request->flash('error', $result['message']);
            return $response->redirect('/payment/plans');
        }
        
        $plan = $result['data'];
        
        // Render view
        // Ensure Stripe public key is available
        if (empty(getenv('STRIPE_PUBLIC_KEY'))) {
            $this->logger->error('Stripe public key is not set in the environment variables.');
            $request->flash('error', 'Payment system configuration error. Please contact support.');
            return $response->redirect('payment/plans');
        }

        return $response->render('payment/checkout', [
            'title' => 'Checkout',
            'plan' => $plan,
            'stripePublicKey' => getenv('STRIPE_PUBLIC_KEY')
        ]);
    }
    
    /**
     * Process payment
     * 
     * @param Request $request
     * @return Response
     */
    public function process(Request $request, Response $response): Response
    {
        // Verify CSRF token
        if (!$this->verifyCsrf('payment_form', false)) {
            return $response->json(['error' => 'Invalid security token. Please try again.'], 400);
        }
        
        // Get input
        $planId = (int)$request->input('plan_id', 0);
        $paymentMethodId = $request->input('payment_method_id', '');
        $paymentIntentId = $request->input('payment_intent_id', '');
        
        // Validate input
        if (empty($planId) || (empty($paymentMethodId) && empty($paymentIntentId))) {
            return $response->json(['error' => 'Missing required fields.'], 400);
        }
        
        $user = $this->authService->getCurrentUser($request);
        if (!$user) {
            return $response->json(['error' => 'User not authenticated'], 401);
        }
        
        try {
            $result = $this->paymentService->processCreditPurchase($user, $planId, $paymentMethodId, $paymentIntentId);
            return $response->json($result['data'], $result['status_code']);
        } catch (ValidationException $e) {
            return $response->json(['error' => $e->getMessage()], 400);
        } catch (Exception $e) {
            $this->logger->error("Credit purchase processing failed: " . $e->getMessage());
            return $response->json(['error' => 'Payment processing failed'], 500);
        }
    }
    
    /**
     * Payment success callback
     * 
     * @param Request $request
     * @return Response
     */
    public function success(Request $request, Response $response): Response
    {
        if (!$this->authService->isLoggedIn($request)) {
            return $response->redirect('/login');
        }
        
        $data = [
            'title' => 'Payment Successful',
            'message' => 'Thank you for your purchase! Your credits have been added to your account.'
        ];
        
        return $response->render('payment/success', $data);
    }
    
    /**
     * Handle payment cancellation
     * 
     * @param Request $request
     * @return Response
     */
    public function cancel(Request $request, Response $response): Response
    {
        // Require login
        if (!$this->authService->isLoggedIn($request)) {
            return $response->redirect('login?redirect=' . urlencode($request->getUri()));
        }
        
        try {
            // Set flash message
            $request->flash('info', 'Your payment was cancelled. No charges were made to your account.');
            
            // Redirect to plans page
            return $response->redirect('payment/plans');
            
        } catch (Exception $e) {
            $this->logger->error('Payment cancel error: ' . $e->getMessage());
            $request->flash('error', 'An error occurred while processing your cancellation.');
            return $response->redirect('dashboard');
        }
    }
    
    /**
     * Handle Stripe webhook events
     * 
     * @param Request $request
     * @return Response
     */
    public function webhook(Request $request, Response $response): Response
    {
        // Get the webhook secret from environment
        $endpoint_secret = getenv('STRIPE_WEBHOOK_SECRET');
        
        // Get the request payload
        $payload = $request->getBody();
        $sig_header = $request->header('stripe-signature', '');
        $event = null;
        
        try {
            // Verify the webhook signature
            $event = \Stripe\Webhook::constructEvent(
                $payload, $sig_header, $endpoint_secret
            );
        } catch(\UnexpectedValueException $e) {
            // Invalid payload
            return $response->json(['error' => 'Invalid payload'], 400);
        } catch(\Stripe\Exception\SignatureVerificationException $e) {
            // Invalid signature
            return $response->json(['error' => 'Invalid signature'], 400);
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
                $this->logger->warning('Webhook received for unhandled event type: ' . $event->type);
                // Unexpected event type
                return $response->json(['message' => 'Unhandled event type'], 200);
        }
        
        // Return a 200 response to acknowledge receipt of the event
        return $response->json(['message' => 'Webhook processed successfully'], 200);
    }
    
    /**
     * Handle successful payment intent
     * 
     * @param \Stripe\PaymentIntent $paymentIntent
     * @return void
     */
    protected function handlePaymentIntentSucceeded($paymentIntent)
    {
        $this->paymentService->handlePaymentIntentSucceeded($paymentIntent);
    }
    
    /**
     * Handle failed payment intent
     * 
     * @param \Stripe\PaymentIntent $paymentIntent
     * @return void
     */
    protected function handlePaymentIntentFailed($paymentIntent)
    {
        $this->paymentService->handlePaymentIntentFailed($paymentIntent);
    }
    
    /**
     * Handle charge refund
     * 
     * @param \Stripe\Charge $charge
     * @return void
     */
    protected function handleChargeRefunded($charge)
    {
        $this->paymentService->handleChargeRefunded($charge);
    }
    
    /**
     * Show transaction history
     * 
     * @param Request $request
     * @return Response
     */
    public function history(Request $request, Response $response): Response
    {
        if (!$this->authService->isLoggedIn($request)) {
            return $response->redirect('/login?redirect=' . urlencode($request->getUri()));
        }
        
        try {
            $user = $this->authService->getCurrentUser($request);
            $page = max(1, (int)$request->query('page', 1));
            $perPage = 15;
            
            $result = $this->paymentService->getUserTransactionHistory($user->id, $page, $perPage);
            
            $data = [
                'title' => 'Transaction History',
                'transactions' => $result['transactions'],
                'user' => $user,
                'pagination' => $result['pagination']
            ];
            
            return $response->render('payment/history', $data);
            
        } catch (Exception $e) {
            $this->logger->error('Failed to load transaction history: ' . $e->getMessage());
            $request->flash('error', 'Failed to load transaction history. Please try again later.');
            return $response->redirect('dashboard');
        }
    }
}
