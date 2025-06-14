<?php

namespace App\Services;

use App\Core\Service\BaseService;
use App\Repositories\UserRepository;
use App\Repositories\CreditTransactionRepository;
use App\Repositories\PlanRepository;
use App\Repositories\SubscriptionRepository;
use App\Libraries\Database;
use App\Exceptions\ValidationException;
use Exception;
use Stripe\StripeClient;
use Stripe\Exception\CardException;
use Stripe\Exception\RateLimitException;
use Stripe\Exception\InvalidRequestException;
use Stripe\Exception\AuthenticationException;
use Stripe\Exception\ApiConnectionException;
use Stripe\Exception\ApiErrorException;
use Psr\Log\LoggerInterface;

/**
 * Payment Service
 * 
 * Handles payment processing, credit transactions, and subscription management
 */
class PaymentService extends BaseService
{
    /**
     * The logger instance.
     *
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;
    /** @var StripeClient */
    private $stripe;
    
    /** @var UserRepository */
    private $userRepository;
    
    /** @var CreditTransactionRepository */
    private $creditTransactionRepository;
    
    /** @var PlanRepository */
    private $planRepository;
    
    /** @var SubscriptionRepository */
    private $subscriptionRepository;
    
    /** @var bool Flag to track if we're in a transaction */
    protected $inTransaction = false;
    
    public function __construct(
        UserRepository $userRepository,
        CreditTransactionRepository $creditTransactionRepository,
        PlanRepository $planRepository,
        SubscriptionRepository $subscriptionRepository,
        LoggerInterface $logger
    ) {
        parent::__construct();
        $this->stripe = new StripeClient(getenv('STRIPE_SECRET_KEY'));
        $this->userRepository = $userRepository;
        $this->creditTransactionRepository = $creditTransactionRepository;
        $this->planRepository = $planRepository;
        $this->subscriptionRepository = $subscriptionRepository;
        $this->logger = $logger;
    }
    
    /**
     * Begin a database transaction
     */
    protected function beginTransaction()
    {
        $connection = $this->getDatabaseConnection();
        $connection->beginTransaction();
        $this->inTransaction = true;
    }
    
    /**
     * Commit the current transaction
     */
    protected function commitTransaction()
    {
        if ($this->inTransaction) {
            $connection = $this->getDatabaseConnection();
            $connection->commit();
            $this->inTransaction = false;
        }
    }
    
    /**
     * Rollback the current transaction
     */
    protected function rollbackTransaction()
    {
        if ($this->inTransaction) {
            $connection = $this->getDatabaseConnection();
            $connection->rollback();
            $this->inTransaction = false;
        }
    }
    
    /**
     * Get database connection from container
     *
     * @return \PDO
     */
    protected function getDatabaseConnection()
    {
        $app = \App\Core\Application::getInstance();
        $dbManager = $app->getContainer()->get(\App\Core\Database\DatabaseManager::class);
        return $dbManager->getConnection();
    }
    
    /**
     * Process credit purchase
     *
     * @param int $userId
     * @param int $credits
     * @param float $amount
     * @param string $paymentMethodId
     * @return array
     * @throws Exception
     */
    public function processCreditPurchase($userId, $credits, $amount, $paymentMethodId)
    {
        $this->beginTransaction();
        
        try {
            // Validate user
            $user = $this->userRepository->find($userId);
            if (!$user) {
                throw new Exception('User not found');
            }
            
            // Create payment intent
            $paymentIntent = $this->stripe->paymentIntents->create([
                'amount' => $amount * 100, // Convert to cents
                'currency' => 'usd',
                'payment_method' => $paymentMethodId,
                'confirmation_method' => 'manual',
                'confirm' => true,
                'metadata' => [
                    'user_id' => $userId,
                    'credits' => $credits,
                    'type' => 'credit_purchase'
                ]
            ]);
            
            // Handle payment result
            if ($paymentIntent->status === 'succeeded') {
                // Add credits to user
                $this->addCreditsToUser($userId, $credits, $paymentIntent->id);
                
                $this->commitTransaction();
                
                return [
                    'success' => true,
                    'payment_intent' => $paymentIntent,
                    'credits_added' => $credits
                ];
            } else {
                throw new Exception('Payment failed: ' . $paymentIntent->status);
            }
            
        } catch (Exception $e) {
            $this->rollbackTransaction();
            throw $e;
        }
    }
    
    /**
     * Add credits to user account
     *
     * @param int $userId
     * @param int $credits
     * @param string $transactionId
     * @return bool
     */
    public function addCreditsToUser($userId, $credits, $transactionId = null)
    {
        try {
            // Update user credits
            $user = $this->userRepository->find($userId);
            if (!$user) {
                throw new Exception('User not found');
            }
            
            $this->userRepository->update($userId, ['credits' => ($user->credits ?? 0) + $credits]);
            
            // Record transaction
            $this->creditTransactionRepository->create([
                'user_id' => $userId,
                'credits' => $credits,
                'transaction_type' => 'purchase',
                'transaction_id' => $transactionId,
                'created_at' => date('Y-m-d H:i:s')
            ]);
            
            return true;
            
        } catch (Exception $e) {
            $this->logger->error('Failed to add credits to user: ' . $e->getMessage(), ['exception' => $e]);
            return false;
        }
    }
    
    /**
     * Handle subscription invoice payment
     *
     * @param \Stripe\Invoice $invoice
     * @return void
     */
    public function handleSubscriptionInvoicePaid($invoice)
    {
        $stripeSubscriptionId = $invoice->subscription;
        if (!$stripeSubscriptionId) {
            $this->logger->warning("Invoice {$invoice->id} paid, but no subscription ID found.");
            return;
        }
        
        $subscription = $this->subscriptionRepository->findByStripeSubscriptionId($stripeSubscriptionId);
        if (!$subscription) {
            $this->logger->warning("Subscription not found for Stripe subscription ID: {$stripeSubscriptionId} from invoice {$invoice->id}");
            return;
        }
        
        $user = $this->userRepository->find($subscription->user_id);
        if (!$user) {
            $this->logger->warning("User not found for subscription ID: {$subscription->id}");
            return;
        }
        
        $plan = $this->planRepository->findById($subscription->plan_id);
        if (!$plan) {
            $this->logger->warning("Plan not found for subscription ID: {$subscription->id}");
            return;
        }
        
        // Update subscription status
        $this->updateSubscriptionFromStripe($subscription, $stripeSubscriptionId);
        
        // Add credits if applicable
        if ($plan->credits > 0) {
            $this->addCreditsToUser($user->id, $plan->credits, $invoice->id);
        }
    }
    
    /**
     * Update subscription from Stripe data
     *
     * @param Subscription $subscription
     * @param string $stripeSubscriptionId
     * @return void
     */
    private function updateSubscriptionFromStripe($subscription, $stripeSubscriptionId)
    {
        try {
            $stripeSub = $this->stripe->subscriptions->retrieve($stripeSubscriptionId);
            
            $this->subscriptionRepository->update($subscription->id, [
                'status' => $stripeSub->status,
                'current_period_start' => date('Y-m-d H:i:s', $stripeSub->current_period_start),
                'current_period_end' => date('Y-m-d H:i:s', $stripeSub->current_period_end),
                'updated_at' => date('Y-m-d H:i:s')
            ]);
            
        } catch (Exception $e) {
            $this->logger->error('Failed to update subscription from Stripe: ' . $e->getMessage(), ['exception' => $e]);
        }
    }
    
    /**
     * Create subscription
     *
     * @param int $userId
     * @param int $planId
     * @param string $paymentMethodId
     * @return array
     * @throws Exception
     */
    public function createSubscription($userId, $planId, $paymentMethodId)
    {
        try {
            $user = $this->userRepository->findById($userId);
            if (!$user) {
                throw new Exception('User not found');
            }
            
            $plan = $this->planRepository->findById($planId);
            if (!$plan) {
                throw new Exception('Plan not found');
            }
            
            // Create or retrieve Stripe customer
            $customerId = $this->getOrCreateStripeCustomer($user);
            
            // Attach payment method to customer
            $this->stripe->paymentMethods->attach($paymentMethodId, [
                'customer' => $customerId
            ]);
            
            // Create subscription
            $stripeSubscription = $this->stripe->subscriptions->create([
                'customer' => $customerId,
                'items' => [[
                    'price' => $plan->stripe_price_id
                ]],
                'default_payment_method' => $paymentMethodId,
                'metadata' => [
                    'user_id' => $userId,
                    'plan_id' => $planId
                ]
            ]);
            
            // Save subscription to database
            $subscription = $this->subscriptionRepository->create([
                'user_id' => $userId,
                'plan_id' => $planId,
                'stripe_subscription_id' => $stripeSubscription->id,
                'stripe_customer_id' => $customerId,
                'status' => $stripeSubscription->status,
                'current_period_start' => date('Y-m-d H:i:s', $stripeSubscription->current_period_start),
                'current_period_end' => date('Y-m-d H:i:s', $stripeSubscription->current_period_end),
                'created_at' => date('Y-m-d H:i:s')
            ]);
            
            return [
                'success' => true,
                'subscription' => $subscription,
                'stripe_subscription' => $stripeSubscription
            ];
            
        } catch (Exception $e) {
            throw new Exception('Failed to create subscription: ' . $e->getMessage());
        }
    }
    
    /**
     * Get all active plans
     *
     * @return array
     */
    public function getActivePlans()
    {
        try {
            $plans = $this->planRepository->findActivePlans();
                
            return [
                'success' => true,
                'data' => $plans,
                'message' => 'Plans retrieved successfully'
            ];
        } catch (Exception $e) {
            $this->logger->error('Failed to get active plans: ' . $e->getMessage(), ['exception' => $e]);
            return [
                'success' => false,
                'data' => [],
                'message' => 'Failed to retrieve plans'
            ];
        }
    }
    
    /**
     * Get plan by ID
     *
     * @param int $planId
     * @return array
     */
    public function getPlan($planId)
    {
        try {
            $plan = $this->planRepository->findById($planId);
            
            if (!$plan) {
                return [
                    'success' => false,
                    'data' => null,
                    'message' => 'Plan not found'
                ];
            }
            
            if (!$plan->is_active) {
                return [
                    'success' => false,
                    'data' => null,
                    'message' => 'Plan is not active'
                ];
            }
            
            return [
                'success' => true,
                'data' => $plan,
                'message' => 'Plan retrieved successfully'
            ];
        } catch (Exception $e) {
            $this->logger->error('Failed to get plan: ' . $e->getMessage(), ['exception' => $e]);
            return [
                'success' => false,
                'data' => null,
                'message' => 'Failed to retrieve plan'
            ];
        }
    }
    
    /**
     * Get or create Stripe customer for user
     *
     * @param User $user
     * @return string
     */
    private function getOrCreateStripeCustomer($user)
    {
        if ($user->stripe_customer_id) {
            return $user->stripe_customer_id;
        }
        
        $customer = $this->stripe->customers->create([
            'email' => $user->email,
            'name' => $user->name,
            'metadata' => [
                'user_id' => $user->id
            ]
        ]);
        
        // Save customer ID to user
        $this->userRepository->update($user->id, ['stripe_customer_id' => $customer->id]);
        
        return $customer->id;
    }
    
    /**
     * Cancel subscription
     *
     * @param int $subscriptionId
     * @return bool
     */
    public function cancelSubscription($subscriptionId)
    {
        try {
            $subscription = $this->subscriptionRepository->findById($subscriptionId);
            if (!$subscription) {
                throw new Exception('Subscription not found');
            }
            
            // Cancel in Stripe
            $this->stripe->subscriptions->cancel($subscription->stripe_subscription_id);
            
            // Update local subscription
            $this->subscriptionRepository->update($subscriptionId, [
                'status' => 'canceled',
                'canceled_at' => date('Y-m-d H:i:s')
            ]);
            
            return true;
            
        } catch (Exception $e) {
            $this->logger->error('Failed to cancel subscription: ' . $e->getMessage(), ['exception' => $e]);
            return false;
        }
    }
    
    /**
     * Get user's active subscription
     *
     * @param int $userId
     * @return Subscription|null
     */
    public function getUserActiveSubscription($userId)
    {
        return $this->subscriptionRepository->getCurrentSubscription($userId);
    }
    
    /**
     * Validate payment data
     *
     * @param array $data
     * @return void
     * @throws ValidationException
     */
    public function validatePaymentData($data)
    {
        $errors = [];
        
        if (empty($data['payment_method_id'])) {
            $errors['payment_method_id'] = 'Payment method is required';
        }
        
        if (isset($data['credits'])) {
            if (!is_numeric($data['credits']) || $data['credits'] <= 0) {
                $errors['credits'] = 'Credits must be a positive number';
            }
        }
        
        if (isset($data['amount'])) {
            if (!is_numeric($data['amount']) || $data['amount'] <= 0) {
                $errors['amount'] = 'Amount must be a positive number';
            }
        }
        
        if (!empty($errors)) {
            throw new ValidationException('Payment validation failed', $errors);
        }
    }

    /**
     * Handle successful payment intent
     * 
     * @param array $paymentIntent
     * @return void
     */
    public function handlePaymentIntentSucceeded(array $paymentIntent): void
    {
        try {
            $this->beginTransaction();
            
            $userId = $paymentIntent['metadata']['user_id'] ?? null;
            $credits = (int)($paymentIntent['metadata']['credits'] ?? 0);
            $amount = $paymentIntent['amount'] / 100; // Convert from cents
            
            if (!$userId || !$credits) {
                $this->logger->warning('Missing user_id or credits in payment intent metadata', ['paymentIntent' => $paymentIntent]);
                return;
            }
            
            // Add credits to user
            $this->addCreditsToUser($userId, $credits, $paymentIntent['id']);
            
            $this->commitTransaction();
            
            $this->logger->info("Payment successful for user {$userId}: {$credits} credits added");
            
        } catch (Exception $e) {
            $this->rollbackTransaction();
            $this->logger->error('Failed to process successful payment: ' . $e->getMessage(), ['exception' => $e, 'paymentIntent' => $paymentIntent]);
        }
    }
    
    /**
     * Handle failed payment intent
     * 
     * @param array $paymentIntent
     * @return void
     */
    public function handlePaymentIntentFailed(array $paymentIntent): void
    {
        try {
            $userId = $paymentIntent['metadata']['user_id'] ?? null;
            $amount = $paymentIntent['amount'] / 100; // Convert from cents
            
            $this->logger->error("Payment failed for user {$userId}: Amount {$amount}", ['userId' => $userId, 'amount' => $amount, 'paymentIntent' => $paymentIntent]);
            
        } catch (Exception $e) {
            $this->logger->error('Failed to process payment failure: ' . $e->getMessage(), ['exception' => $e, 'paymentIntent' => $paymentIntent]);
        }
    }
    
    /**
     * Get user transaction history with pagination
     * 
     * @param int $userId
     * @param int $page
     * @param int $perPage
     * @return array
     */
    public function getUserTransactionHistory(int $userId, int $page = 1, int $perPage = 15): array
    {
        try {
            $offset = ($page - 1) * $perPage;
            
            // Get transactions
            $transactions = $this->creditTransactionRepository->findByUserId($userId, $perPage, $offset);
            
            $totalCount = $this->creditTransactionRepository->countByUserId($userId);
            $totalPages = ceil($totalCount / $perPage);
            
            return [
                'transactions' => $transactions,
                'pagination' => [
                    'current_page' => $page,
                    'total_pages' => $totalPages,
                    'per_page' => $perPage,
                    'total_count' => $totalCount,
                    'has_previous' => $page > 1,
                    'has_next' => $page < $totalPages
                ]
            ];
            
        } catch (Exception $e) {
            $this->logger->error('Failed to get transaction history: ' . $e->getMessage(), ['exception' => $e, 'userId' => $userId, 'page' => $page, 'perPage' => $perPage]);
            throw $e;
        }
    }
}