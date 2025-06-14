<?php

namespace App\Services;

use App\Core\Service\Service;
use App\Repositories\CreditRepository;
use App\Repositories\UserRepository;
use App\Repositories\CreditPackRepository;
use App\Repositories\CreditTransactionRepository;

/**
 * Credit Service for handling credit business logic
 */
class CreditService extends Service
{
    /**
     * @var CreditRepository
     */
    protected $creditRepository;
    
    /**
     * @var UserRepository
     */
    protected $userRepository;
    
    /**
     * @var CreditPackRepository
     */
    protected $creditPackRepository;
    
    /**
     * @var CreditTransactionRepository
     */
    protected $creditTransactionRepository;
    
    /**
     * Initialize the service
     */
    public function __construct($app)
    {
        parent::__construct($app);
        $this->creditRepository = $this->getRepository('CreditRepository');
        $this->userRepository = $this->getRepository('UserRepository');
        $this->creditPackRepository = $this->getRepository('CreditPackRepository');
        $this->creditTransactionRepository = $this->getRepository('CreditTransactionRepository');
    }
    
    /**
     * Get database connection using dependency injection
     * 
     * @return \PDO
     */
    protected function getDatabaseConnection()
    {
        $dbManager = $this->app->get('DatabaseManager');
        return $dbManager->getConnection();
    }
    
    /**
     * Get user credit balance
     * 
     * @param int $userId The user ID
     * @return array
     */
    public function getUserBalance($userId)
    {
        try {
            $user = $this->userRepository->find($userId);
            if (!$user) {
                return $this->error('User not found');
            }
            
            $balance = $this->creditRepository->getUserBalance($userId);
            
            return $this->success('User balance retrieved successfully', [
                'user_id' => $userId,
                'balance' => $balance
            ]);
            
        } catch (\Exception $e) {
            $this->log('error', 'Error retrieving user balance: ' . $e->getMessage(), ['user_id' => $userId]);
            return $this->error('An error occurred while retrieving user balance');
        }
    }
    
    /**
     * Add credits to user
     * 
     * @param int $userId The user ID
     * @param int $amount Amount to add
     * @param string $description Transaction description
     * @param string $type Transaction type
     * @return array
     */
    public function addCredits($userId, $amount, $description = '', $type = 'purchase')
    {
        try {
            if ($amount <= 0) {
                return $this->error('Amount must be greater than zero');
            }
            
            $user = $this->userRepository->find($userId);
            if (!$user) {
                return $this->error('User not found');
            }
            
            $transaction = $this->creditRepository->addCredits($userId, $amount, $description, $type);
            
            if ($transaction) {
                $newBalance = $this->creditRepository->getUserBalance($userId);
                
                $this->log('info', 'Credits added successfully', [
                    'user_id' => $userId,
                    'amount' => $amount,
                    'type' => $type,
                    'new_balance' => $newBalance
                ]);
                
                return $this->success('Credits added successfully', [
                    'transaction' => $transaction,
                    'new_balance' => $newBalance
                ]);
            }
            
            return $this->error('Failed to add credits');
            
        } catch (\Exception $e) {
            $this->log('error', 'Error adding credits: ' . $e->getMessage(), [
                'user_id' => $userId,
                'amount' => $amount
            ]);
            return $this->error('An error occurred while adding credits');
        }
    }
    
    /**
     * Deduct credits from user
     * 
     * @param int $userId The user ID
     * @param int $amount Amount to deduct
     * @param string $description Transaction description
     * @param string $type Transaction type
     * @return array
     */
    public function deductCredits($userId, $amount, $description = '', $type = 'usage')
    {
        try {
            if ($amount <= 0) {
                return $this->error('Amount must be greater than zero');
            }
            
            $user = $this->userRepository->find($userId);
            if (!$user) {
                return $this->error('User not found');
            }
            
            // Check if user has enough credits
            $balance = $this->creditRepository->getUserBalance($userId);
            if ($balance < $amount) {
                return $this->error('Insufficient credits', [
                    'required' => $amount,
                    'available' => $balance
                ]);
            }
            
            $transaction = $this->creditRepository->deductCredits($userId, $amount, $description, $type);
            
            if ($transaction) {
                $newBalance = $this->creditRepository->getUserBalance($userId);
                
                $this->log('info', 'Credits deducted successfully', [
                    'user_id' => $userId,
                    'amount' => $amount,
                    'type' => $type,
                    'new_balance' => $newBalance
                ]);
                
                return $this->success('Credits deducted successfully', [
                    'transaction' => $transaction,
                    'new_balance' => $newBalance
                ]);
            }
            
            return $this->error('Failed to deduct credits');
            
        } catch (\Exception $e) {
            $this->log('error', 'Error deducting credits: ' . $e->getMessage(), [
                'user_id' => $userId,
                'amount' => $amount
            ]);
            return $this->error('An error occurred while deducting credits');
        }
    }
    
    /**
     * Get all active credit packs
     * 
     * @return array
     */
    public function getActiveCreditPacks()
    {
        try {
            $creditPacks = $this->creditRepository->getActivePacks();
            
            return $this->success('Active credit packs retrieved successfully', $creditPacks);
            
        } catch (\Exception $e) {
            $this->log('error', 'Error retrieving active credit packs: ' . $e->getMessage());
            return $this->error('An error occurred while retrieving credit packs');
        }
    }
    
    /**
     * Check if user has enough credits
     * 
     * @param int $userId The user ID
     * @param int $amount Amount to check
     * @return array
     */
    public function hasEnoughCredits($userId, $amount)
    {
        try {
            if ($amount <= 0) {
                return $this->error('Amount must be greater than zero');
            }
            
            $user = $this->userRepository->find($userId);
            if (!$user) {
                return $this->error('User not found');
            }
            
            $balance = $this->creditRepository->getUserBalance($userId);
            $hasEnough = $balance >= $amount;
            
            return $this->success('Credit check completed', [
                'has_enough' => $hasEnough,
                'required' => $amount,
                'available' => $balance
            ]);
            
        } catch (\Exception $e) {
            $this->log('error', 'Error checking credits: ' . $e->getMessage(), [
                'user_id' => $userId,
                'amount' => $amount
            ]);
            return $this->error('An error occurred while checking credits');
        }
    }
    
    /**
     * Get recent credit transactions for user
     * 
     * @param int $userId The user ID
     * @param int $limit Number of transactions to retrieve
     * @return array
     */
    public function getRecentTransactions($userId, $limit = 5)
    {
        try {
            $user = $this->userRepository->find($userId);
            if (!$user) {
                return $this->error('User not found');
            }
            
            $transactions = $this->creditTransactionRepository->findByUserId($userId, $limit);
                
            return $this->success('Recent transactions retrieved successfully', [
                'transactions' => $transactions
            ]);
            
        } catch (\Exception $e) {
            $this->log('error', 'Error getting recent transactions: ' . $e->getMessage(), [
                'user_id' => $userId,
                'limit' => $limit
            ]);
            return $this->error('An error occurred while retrieving transactions');
        }
    }
    
    /**
     * Get total credits earned by user
     * 
     * @param int $userId The user ID
     * @return array
     */
    public function getTotalCreditsEarned($userId)
    {
        try {
            $user = $this->userRepository->find($userId);
            if (!$user) {
                return $this->error('User not found');
            }
            
            $total = $this->creditTransactionRepository->getTotalCreditsByUserId($userId);
                
            return $this->success('Total credits earned retrieved successfully', [
                'total' => (int)$total
            ]);
            
        } catch (\Exception $e) {
            $this->log('error', 'Error getting total credits earned: ' . $e->getMessage(), [
                'user_id' => $userId
            ]);
            return $this->error('An error occurred while calculating total credits earned');
        }
    }
    
    /**
     * Get total credits used by user
     * 
     * @param int $userId The user ID
     * @return array
     */
    public function getTotalCreditsUsed($userId)
    {
        try {
            $user = $this->userRepository->find($userId);
            if (!$user) {
                return $this->error('User not found');
            }
            
            $total = $this->creditTransactionRepository->getTotalDebitsByUserId($userId);
                
            return $this->success('Total credits used retrieved successfully', [
                'total' => (int)$total
            ]);
            
        } catch (\Exception $e) {
            $this->log('error', 'Error getting total credits used: ' . $e->getMessage(), [
                'user_id' => $userId
            ]);
            return $this->error('An error occurred while calculating total credits used');
        }
    }
    
    /**
     * Get credits earned this month
     * 
     * @param int $userId The user ID
     * @return array
     */
    public function getCreditsEarnedThisMonth($userId)
    {
        try {
            $user = $this->userRepository->find($userId);
            if (!$user) {
                return $this->error('User not found');
            }
            
            $now = \Carbon\Carbon::now();
            $total = $this->creditTransactionRepository->getCreditsEarnedThisMonth($userId);
                
            return $this->success('Credits earned this month retrieved successfully', [
                'total' => (int)$total
            ]);
            
        } catch (\Exception $e) {
            $this->log('error', 'Error getting credits earned this month: ' . $e->getMessage(), [
                'user_id' => $userId
            ]);
            return $this->error('An error occurred while calculating credits earned this month');
        }
    }
    
    /**
     * Get user credit history
     * 
     * @param int $userId The user ID
     * @param int $limit Limit of transactions to retrieve
     * @return array
     */
    public function getUserHistory($userId, $limit = 10)
    {
        try {
            $user = $this->userRepository->find($userId);
            if (!$user) {
                return $this->error('User not found');
            }
            
            $transactions = $this->creditRepository->findByUserId($userId, $limit);
            $balance = $this->creditRepository->getUserBalance($userId);
            
            return $this->success('User credit history retrieved successfully', [
                'transactions' => $transactions,
                'current_balance' => $balance
            ]);
            
        } catch (\Exception $e) {
            $this->log('error', 'Error retrieving user credit history: ' . $e->getMessage(), ['user_id' => $userId]);
            return $this->error('An error occurred while retrieving user credit history');
        }
    }
    
    /**
     * Get transactions by type
     * 
     * @param string $type Transaction type
     * @param int $limit Limit of transactions to retrieve
     * @return array
     */
    public function getTransactionsByType($type, $limit = 10)
    {
        try {
            $transactions = $this->creditRepository->getTransactionsByType($type, $limit);
            
            return $this->success('Transactions retrieved successfully', $transactions);
            
        } catch (\Exception $e) {
            $this->log('error', 'Error retrieving transactions by type: ' . $e->getMessage(), ['type' => $type]);
            return $this->error('An error occurred while retrieving transactions');
        }
    }
    
    /**
     * Transfer credits between users
     * 
     * @param int $fromUserId Source user ID
     * @param int $toUserId Destination user ID
     * @param int $amount Amount to transfer
     * @param string $description Transaction description
     * @return array
     */
    public function transferCredits($fromUserId, $toUserId, $amount, $description = '')
    {
        try {
            if ($amount <= 0) {
                return $this->error('Amount must be greater than zero');
            }
            
            if ($fromUserId == $toUserId) {
                return $this->error('Cannot transfer credits to the same user');
            }
            
            // Check if both users exist
            $fromUser = $this->userRepository->find($fromUserId);
            if (!$fromUser) {
                return $this->error('Source user not found');
            }
            
            $toUser = $this->userRepository->find($toUserId);
            if (!$toUser) {
                return $this->error('Destination user not found');
            }
            
            // Check if source user has enough credits
            $balance = $this->creditRepository->getUserBalance($fromUserId);
            if ($balance < $amount) {
                return $this->error('Insufficient credits', [
                    'required' => $amount,
                    'available' => $balance
                ]);
            }
            
            // Perform transfer
            $result = $this->creditRepository->transferCredits($fromUserId, $toUserId, $amount, $description);
            
            if ($result) {
                $fromBalance = $this->creditRepository->getUserBalance($fromUserId);
                $toBalance = $this->creditRepository->getUserBalance($toUserId);
                
                $this->log('info', 'Credits transferred successfully', [
                    'from_user_id' => $fromUserId,
                    'to_user_id' => $toUserId,
                    'amount' => $amount
                ]);
                
                return $this->success('Credits transferred successfully', [
                    'from_user_balance' => $fromBalance,
                    'to_user_balance' => $toBalance
                ]);
            }
            
            return $this->error('Failed to transfer credits');
            
        } catch (\Exception $e) {
            $this->log('error', 'Error transferring credits: ' . $e->getMessage(), [
                'from_user_id' => $fromUserId,
                'to_user_id' => $toUserId,
                'amount' => $amount
            ]);
            return $this->error('An error occurred while transferring credits');
        }
    }
    
    /**
     * Create Stripe checkout session for credit purchase
     * 
     * @param int $userId The user ID
     * @param int $packId Credit pack ID
     * @return array
     */
    public function createStripeCheckoutSession($userId, $packId)
    {
        try {
            $user = $this->userRepository->find($userId);
            if (!$user) {
                return $this->error('User not found');
            }

            $creditPack = $this->creditPackRepository->findById($packId);
            if (!$creditPack || !$creditPack->is_active) {
                return $this->error('Invalid or inactive credit pack selected');
            }

            if (empty(getenv('STRIPE_SECRET_KEY')) || empty(getenv('STRIPE_PUBLISHABLE_KEY'))) {
                $this->log('error', 'Stripe keys are not set in the environment variables');
                return $this->error('Payment system configuration error');
            }

            $stripe = new \Stripe\StripeClient(getenv('STRIPE_SECRET_KEY'));

            // Create a Stripe Customer if one doesn't exist
            $stripeCustomerId = $user->stripe_customer_id;
            if (!$stripeCustomerId) {
                $customer = $stripe->customers->create([
                    'email' => $user->email,
                    'name' => $user->name,
                ]);
                $stripeCustomerId = $customer->id;
                $this->userRepository->update($user->id, ['stripe_customer_id' => $stripeCustomerId]);
            }

            // Create a Stripe Checkout Session for one-time purchase
            $checkout_session = $stripe->checkout->sessions->create([
                'customer' => $stripeCustomerId,
                'payment_method_types' => ['card'],
                'line_items' => [[
                    'price_data' => [
                        'currency' => 'usd',
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

            $this->log('info', 'Stripe checkout session created', [
                'user_id' => $userId,
                'pack_id' => $packId,
                'session_id' => $checkout_session->id
            ]);

            return $this->success('Checkout session created successfully', [
                'sessionId' => $checkout_session->id
            ]);

        } catch (\Stripe\Exception\ApiErrorException $e) {
            $this->log('error', 'Stripe API Error during credit purchase: ' . $e->getMessage(), [
                'user_id' => $userId,
                'pack_id' => $packId
            ]);
            return $this->error('Payment processing failed: ' . $e->getMessage());
        } catch (\Exception $e) {
            $this->log('error', 'Error during credit purchase: ' . $e->getMessage(), [
                'user_id' => $userId,
                'pack_id' => $packId
            ]);
            return $this->error('An unexpected error occurred. Please try again.');
        }
    }

    /**
     * Process successful Stripe payment
     * 
     * @param string $sessionId Stripe session ID
     * @param int $userId User ID
     * @return array
     */
    public function processStripeSuccess($sessionId, $userId)
    {
        try {
            if (empty(getenv('STRIPE_SECRET_KEY'))) {
                $this->log('error', 'Stripe secret key is not set for success check');
                return $this->error('Payment system configuration error');
            }

            $stripe = new \Stripe\StripeClient(getenv('STRIPE_SECRET_KEY'));
            $session = $stripe->checkout->sessions->retrieve($sessionId);

            if ($session->payment_status !== 'paid') {
                return $this->error('Payment was not successful');
            }

            // Check if this session has already been processed
            $existingTransaction = $this->creditTransactionRepository->findByReferenceId(
                $session->id,
                \App\Models\CreditTransaction::REF_PURCHASE
            );
            if ($existingTransaction) {
                return $this->success('Credits already processed', [
                    'already_processed' => true
                ]);
            }

            $sessionUserId = $session->metadata->user_id ?? null;
            $creditPackId = $session->metadata->credit_pack_id ?? null;
            $creditsToAward = $session->metadata->credits_to_award ?? 0;

            if ($sessionUserId != $userId) {
                $this->log('error', "User ID mismatch in Stripe session metadata. Expected: {$userId}, Got: {$sessionUserId}");
                return $this->error('User verification failed');
            }

            if ($creditsToAward <= 0 || !$creditPackId) {
                $this->log('error', 'Missing metadata for credit award. Session ID: ' . $sessionId);
                return $this->error('Invalid payment metadata');
            }

            // Begin database transaction
            $this->getDatabaseConnection()->beginTransaction();

            try {
                $currentUser = $this->userRepository->find($userId);
                $newCreditBalance = ($currentUser->credits ?? 0) + $creditsToAward;
                $this->userRepository->update($userId, ['credits' => $newCreditBalance]);

                $this->creditTransactionRepository->create([
                    'user_id' => $userId,
                    'transaction_type' => \App\Models\CreditTransaction::TYPE_CREDIT_PURCHASE,
                    'amount' => $creditsToAward,
                    'description' => 'Purchased ' . $creditsToAward . ' credits.',
                    'related_id' => $creditPackId,
                    'related_type' => 'credit_pack',
                    'reference_id' => $session->id,
                    'reference_type' => \App\Models\CreditTransaction::REF_PURCHASE,
                    'created_at' => date('Y-m-d H:i:s'),
                ]);

                $this->getDatabaseConnection()->commit();

                $this->log('info', 'Credits awarded successfully after purchase', [
                    'user_id' => $userId,
                    'credits_awarded' => $creditsToAward,
                    'session_id' => $sessionId
                ]);

                return $this->success('Credits awarded successfully', [
                    'credits_awarded' => $creditsToAward,
                    'new_balance' => $newCreditBalance
                ]);

            } catch (\Exception $e) {
                $this->getDatabaseConnection()->rollback();
                throw $e;
            }

        } catch (\Stripe\Exception\ApiErrorException $e) {
            $this->log('error', 'Stripe API Error on success page: ' . $e->getMessage(), [
                'session_id' => $sessionId,
                'user_id' => $userId
            ]);
            return $this->error('Payment verification failed');
        } catch (\Exception $e) {
            $this->log('error', 'Error processing successful payment: ' . $e->getMessage(), [
                'session_id' => $sessionId,
                'user_id' => $userId
            ]);
            return $this->error('An error occurred while processing your payment');
        }
    }

    /**
     * Purchase credits
     * 
     * @param int $userId The user ID
     * @param int $amount Amount of credits to purchase
     * @param float $price Purchase price
     * @param string $paymentMethod Payment method
     * @param string $paymentId Payment ID
     * @return array
     */
    public function purchaseCredits($userId, $amount, $price, $paymentMethod, $paymentId = null)
    {
        try {
            if ($amount <= 0) {
                return $this->error('Amount must be greater than zero');
            }
            
            if ($price <= 0) {
                return $this->error('Price must be greater than zero');
            }
            
            $user = $this->userRepository->find($userId);
            if (!$user) {
                return $this->error('User not found');
            }
            
            // Process payment (this would typically involve a payment gateway)
            // For this example, we'll assume payment is successful
            
            // Add credits to user account
            $description = "Purchase of {$amount} credits for {$price}";
            $transaction = $this->creditRepository->addCredits($userId, $amount, $description, 'purchase', [
                'payment_method' => $paymentMethod,
                'payment_id' => $paymentId,
                'price' => $price
            ]);
            
            if ($transaction) {
                $newBalance = $this->creditRepository->getUserBalance($userId);
                
                $this->log('info', 'Credits purchased successfully', [
                    'user_id' => $userId,
                    'amount' => $amount,
                    'price' => $price,
                    'payment_method' => $paymentMethod,
                    'new_balance' => $newBalance
                ]);
                
                return $this->success('Credits purchased successfully', [
                    'transaction' => $transaction,
                    'new_balance' => $newBalance
                ]);
            }
            
            return $this->error('Failed to purchase credits');
            
        } catch (\Exception $e) {
            $this->log('error', 'Error purchasing credits: ' . $e->getMessage(), [
                'user_id' => $userId,
                'amount' => $amount,
                'price' => $price
            ]);
            return $this->error('An error occurred while purchasing credits');
        }
    }
    
    /**
     * Get credit statistics
     * 
     * @return array
     */
    public function getCreditStatistics()
    {
        try {
            $stats = $this->creditRepository->getStatistics();
            return $this->success('Credit statistics retrieved successfully', $stats);
            
        } catch (\Exception $e) {
            $this->log('error', 'Error retrieving credit statistics: ' . $e->getMessage());
            return $this->error('An error occurred while retrieving credit statistics');
        }
    }
    
    /**
     * Get top users by credit balance
     * 
     * @param int $limit Number of users to retrieve
     * @return array
     */
    public function getTopUsers($limit = 10)
    {
        try {
            $users = $this->creditRepository->getTopUsers($limit);
            return $this->success('Top users retrieved successfully', $users);
            
        } catch (\Exception $e) {
            $this->log('error', 'Error retrieving top users: ' . $e->getMessage());
            return $this->error('An error occurred while retrieving top users');
        }
    }
    
    /**
     * Get credit usage over time
     * 
     * @param string $period Period (day, week, month, year)
     * @return array
     */
    public function getCreditUsageOverTime($period = 'month')
    {
        try {
            $usage = $this->creditRepository->getUsageOverTime($period);
            return $this->success('Credit usage retrieved successfully', $usage);
            
        } catch (\Exception $e) {
            $this->log('error', 'Error retrieving credit usage: ' . $e->getMessage());
            return $this->error('An error occurred while retrieving credit usage');
        }
    }
    
    /**
     * Get paginated credit transactions
     * 
     * @param int $page Page number
     * @param int $perPage Items per page
     * @return array
     */
    public function getPaginatedTransactions($page = 1, $perPage = 10)
    {
        try {
            $result = $this->creditRepository->paginate($page, $perPage);
            return $this->success('Credit transactions retrieved successfully', $result);
            
        } catch (\Exception $e) {
            $this->log('error', 'Error retrieving paginated credit transactions: ' . $e->getMessage());
            return $this->error('An error occurred while retrieving credit transactions');
        }
    }
}