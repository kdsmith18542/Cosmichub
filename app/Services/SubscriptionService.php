<?php

namespace App\Services;

use App\Core\Service\Service;
use App\Repositories\SubscriptionRepository;
use App\Repositories\UserRepository;

/**
 * Subscription Service for handling subscription business logic
 */
class SubscriptionService extends Service
{
    /**
     * @var SubscriptionRepository
     */
    protected $subscriptionRepository;
    
    /**
     * @var UserRepository
     */
    protected $userRepository;
    
    /**
     * Initialize the service
     */
    public function __construct($app)
    {
        parent::__construct($app);
        $this->subscriptionRepository = $this->getRepository('SubscriptionRepository');
        $this->userRepository = $this->getRepository('UserRepository');
    }
    
    /**
     * Get subscription by ID
     * 
     * @param int $id The subscription ID
     * @return array
     */
    public function getSubscription($id)
    {
        try {
            $subscription = $this->subscriptionRepository->find($id);
            
            if (!$subscription) {
                return $this->error('Subscription not found');
            }
            
            return $this->success('Subscription retrieved successfully', $subscription);
            
        } catch (\Exception $e) {
            $this->log('error', 'Error retrieving subscription: ' . $e->getMessage(), ['subscription_id' => $id]);
            return $this->error('An error occurred while retrieving the subscription');
        }
    }
    
    /**
     * Get subscriptions by user ID
     * 
     * @param int $userId The user ID
     * @return array
     */
    public function getUserSubscriptions($userId)
    {
        try {
            $user = $this->userRepository->find($userId);
            if (!$user) {
                return $this->error('User not found');
            }
            
            $subscriptions = $this->subscriptionRepository->findByUserId($userId);
            return $this->success('User subscriptions retrieved successfully', $subscriptions);
            
        } catch (\Exception $e) {
            $this->log('error', 'Error retrieving user subscriptions: ' . $e->getMessage(), ['user_id' => $userId]);
            return $this->error('An error occurred while retrieving user subscriptions');
        }
    }
    
    /**
     * Get active subscription for user
     * 
     * @param int $userId The user ID
     * @return array
     */
    public function getUserActiveSubscription($userId)
    {
        try {
            $user = $this->userRepository->find($userId);
            if (!$user) {
                return $this->error('User not found');
            }
            
            $subscription = $this->subscriptionRepository->getCurrentSubscription($userId);
            
            if (!$subscription) {
                return $this->error('No active subscription found for this user');
            }
            
            return $this->success('Active subscription retrieved successfully', $subscription);
            
        } catch (\Exception $e) {
            $this->log('error', 'Error retrieving user active subscription: ' . $e->getMessage(), ['user_id' => $userId]);
            return $this->error('An error occurred while retrieving user active subscription');
        }
    }
    
    /**
     * Check if user has active subscription
     * 
     * @param int $userId The user ID
     * @return array
     */
    public function hasActiveSubscription($userId)
    {
        try {
            $user = $this->userRepository->find($userId);
            if (!$user) {
                return $this->error('User not found');
            }
            
            $hasActive = $this->subscriptionRepository->hasActiveSubscription($userId);
            
            return $this->success('Subscription status checked successfully', [
                'has_active_subscription' => $hasActive
            ]);
            
        } catch (\Exception $e) {
            $this->log('error', 'Error checking active subscription: ' . $e->getMessage(), ['user_id' => $userId]);
            return $this->error('An error occurred while checking subscription status');
        }
    }
    
    /**
     * Create new subscription
     * 
     * @param array $data Subscription data
     * @return array
     */
    public function createSubscription($data)
    {
        try {
            // Validate required fields
            $validation = $this->validateSubscriptionData($data);
            if (!empty($validation)) {
                return $this->error('Validation failed', $validation);
            }
            
            // Check if user exists
            $user = $this->userRepository->find($data['user_id']);
            if (!$user) {
                return $this->error('User not found');
            }
            
            // Check if user already has an active subscription
            $activeSubscription = $this->subscriptionRepository->getCurrentSubscription($data['user_id']);
            if ($activeSubscription) {
                return $this->error('User already has an active subscription');
            }
            
            // Prepare subscription data
            $subscriptionData = [
                'user_id' => $data['user_id'],
                'plan_id' => $data['plan_id'],
                'plan_name' => $data['plan_name'],
                'amount' => $data['amount'],
                'currency' => $data['currency'] ?? 'USD',
                'interval' => $data['interval'] ?? 'month',
                'status' => 'active',
                'start_date' => $data['start_date'] ?? date('Y-m-d'),
                'end_date' => $data['end_date'] ?? $this->calculateEndDate($data['start_date'] ?? date('Y-m-d'), $data['interval'] ?? 'month'),
                'payment_method' => $data['payment_method'] ?? 'credit_card',
                'payment_id' => $data['payment_id'] ?? null,
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            $subscription = $this->subscriptionRepository->create($subscriptionData);
            
            if ($subscription) {
                // Update user subscription status
                $this->userRepository->update($data['user_id'], ['is_subscribed' => 1]);
                
                $this->log('info', 'Subscription created successfully', [
                    'subscription_id' => $subscription['id'],
                    'user_id' => $data['user_id']
                ]);
                
                return $this->success('Subscription created successfully', $subscription);
            }
            
            return $this->error('Failed to create subscription');
            
        } catch (\Exception $e) {
            $this->log('error', 'Error creating subscription: ' . $e->getMessage());
            return $this->error('An error occurred while creating the subscription');
        }
    }
    
    /**
     * Update subscription
     * 
     * @param int $id Subscription ID
     * @param array $data Updated data
     * @return array
     */
    public function updateSubscription($id, $data)
    {
        try {
            $subscription = $this->subscriptionRepository->find($id);
            if (!$subscription) {
                return $this->error('Subscription not found');
            }
            
            // Validate data
            $validation = $this->validateSubscriptionData($data, true);
            if (!empty($validation)) {
                return $this->error('Validation failed', $validation);
            }
            
            $data['updated_at'] = date('Y-m-d H:i:s');
            $updated = $this->subscriptionRepository->update($id, $data);
            
            if ($updated) {
                $this->log('info', 'Subscription updated successfully', ['subscription_id' => $id]);
                return $this->success('Subscription updated successfully');
            }
            
            return $this->error('Failed to update subscription');
            
        } catch (\Exception $e) {
            $this->log('error', 'Error updating subscription: ' . $e->getMessage(), ['subscription_id' => $id]);
            return $this->error('An error occurred while updating the subscription');
        }
    }
    
    /**
     * Cancel subscription
     * 
     * @param int $id Subscription ID
     * @param string $reason Cancellation reason
     * @return array
     */
    public function cancelSubscription($id, $reason = '')
    {
        try {
            $subscription = $this->subscriptionRepository->find($id);
            if (!$subscription) {
                return $this->error('Subscription not found');
            }
            
            if ($subscription['status'] !== 'active') {
                return $this->error('Only active subscriptions can be cancelled');
            }
            
            $data = [
                'status' => 'cancelled',
                'cancellation_reason' => $reason,
                'cancelled_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            $cancelled = $this->subscriptionRepository->update($id, $data);
            
            if ($cancelled) {
                // Update user subscription status if they have no other active subscriptions
                $hasOtherActive = $this->subscriptionRepository->hasActiveSubscription($subscription['user_id']);
                if (!$hasOtherActive) {
                    $this->userRepository->update($subscription['user_id'], ['is_subscribed' => 0]);
                }
                
                $this->log('info', 'Subscription cancelled successfully', [
                    'subscription_id' => $id,
                    'user_id' => $subscription['user_id']
                ]);
                
                return $this->success('Subscription cancelled successfully');
            }
            
            return $this->error('Failed to cancel subscription');
            
        } catch (\Exception $e) {
            $this->log('error', 'Error cancelling subscription: ' . $e->getMessage(), ['subscription_id' => $id]);
            return $this->error('An error occurred while cancelling the subscription');
        }
    }
    
    /**
     * Renew subscription
     * 
     * @param int $id Subscription ID
     * @param array $data Renewal data
     * @return array
     */
    public function renewSubscription($id, $data = [])
    {
        try {
            $subscription = $this->subscriptionRepository->find($id);
            if (!$subscription) {
                return $this->error('Subscription not found');
            }
            
            // Calculate new dates
            $startDate = date('Y-m-d');
            $endDate = $this->calculateEndDate($startDate, $subscription['interval']);
            
            $renewalData = [
                'status' => 'active',
                'start_date' => $startDate,
                'end_date' => $endDate,
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            // Add any additional data provided
            if (!empty($data)) {
                $renewalData = array_merge($renewalData, $data);
            }
            
            $renewed = $this->subscriptionRepository->update($id, $renewalData);
            
            if ($renewed) {
                // Update user subscription status
                $this->userRepository->update($subscription['user_id'], ['is_subscribed' => 1]);
                
                $this->log('info', 'Subscription renewed successfully', [
                    'subscription_id' => $id,
                    'user_id' => $subscription['user_id']
                ]);
                
                return $this->success('Subscription renewed successfully');
            }
            
            return $this->error('Failed to renew subscription');
            
        } catch (\Exception $e) {
            $this->log('error', 'Error renewing subscription: ' . $e->getMessage(), ['subscription_id' => $id]);
            return $this->error('An error occurred while renewing the subscription');
        }
    }
    
    /**
     * Get subscription statistics
     * 
     * @return array
     */
    public function getSubscriptionStatistics()
    {
        try {
            $stats = $this->subscriptionRepository->getStatistics();
            return $this->success('Subscription statistics retrieved successfully', $stats);
            
        } catch (\Exception $e) {
            $this->log('error', 'Error retrieving subscription statistics: ' . $e->getMessage());
            return $this->error('An error occurred while retrieving subscription statistics');
        }
    }
    
    /**
     * Get revenue statistics
     * 
     * @param string $period Period (day, week, month, year)
     * @return array
     */
    public function getRevenueStatistics($period = 'month')
    {
        try {
            $stats = $this->subscriptionRepository->getRevenueStatistics($period);
            return $this->success('Revenue statistics retrieved successfully', $stats);
            
        } catch (\Exception $e) {
            $this->log('error', 'Error retrieving revenue statistics: ' . $e->getMessage());
            return $this->error('An error occurred while retrieving revenue statistics');
        }
    }
    
    /**
     * Get paginated subscriptions
     * 
     * @param int $page Page number
     * @param int $perPage Items per page
     * @return array
     */
    public function getPaginatedSubscriptions($page = 1, $perPage = 10)
    {
        try {
            $result = $this->subscriptionRepository->paginate($page, $perPage);
            return $this->success('Subscriptions retrieved successfully', $result);
            
        } catch (\Exception $e) {
            $this->log('error', 'Error retrieving paginated subscriptions: ' . $e->getMessage());
            return $this->error('An error occurred while retrieving subscriptions');
        }
    }
    
    /**
     * Process expiring subscriptions
     * 
     * @return array
     */
    public function processExpiringSubscriptions()
    {
        try {
            $expiringSubscriptions = $this->subscriptionRepository->findExpiring();
            $processed = 0;
            $errors = 0;
            
            foreach ($expiringSubscriptions as $subscription) {
                try {
                    // Attempt to auto-renew or mark as expired based on your business logic
                    // For this example, we'll just mark them as expired
                    $this->subscriptionRepository->update($subscription['id'], [
                        'status' => 'expired',
                        'updated_at' => date('Y-m-d H:i:s')
                    ]);
                    
                    // Update user subscription status if they have no other active subscriptions
                    $hasOtherActive = $this->subscriptionRepository->hasActiveSubscription($subscription['user_id']);
                    if (!$hasOtherActive) {
                        $this->userRepository->update($subscription['user_id'], ['is_subscribed' => 0]);
                    }
                    
                    $processed++;
                } catch (\Exception $e) {
                    $this->log('error', 'Error processing expiring subscription: ' . $e->getMessage(), [
                        'subscription_id' => $subscription['id']
                    ]);
                    $errors++;
                }
            }
            
            return $this->success('Expiring subscriptions processed', [
                'processed' => $processed,
                'errors' => $errors,
                'total' => count($expiringSubscriptions)
            ]);
            
        } catch (\Exception $e) {
            $this->log('error', 'Error processing expiring subscriptions: ' . $e->getMessage());
            return $this->error('An error occurred while processing expiring subscriptions');
        }
    }
    
    /**
     * Validate subscription data
     * 
     * @param array $data Subscription data
     * @param bool $isUpdate Whether this is an update operation
     * @return array
     */
    protected function validateSubscriptionData($data, $isUpdate = false)
    {
        $errors = [];
        
        if (!$isUpdate || isset($data['user_id'])) {
            if (empty($data['user_id'])) {
                $errors[] = 'User ID is required';
            }
        }
        
        if (!$isUpdate || isset($data['plan_id'])) {
            if (empty($data['plan_id'])) {
                $errors[] = 'Plan ID is required';
            }
        }
        
        if (!$isUpdate || isset($data['plan_name'])) {
            if (empty($data['plan_name'])) {
                $errors[] = 'Plan name is required';
            }
        }
        
        if (!$isUpdate || isset($data['amount'])) {
            if (!isset($data['amount']) || !is_numeric($data['amount'])) {
                $errors[] = 'Valid amount is required';
            }
        }
        
        if (isset($data['interval'])) {
            $validIntervals = ['day', 'week', 'month', 'year'];
            if (!in_array($data['interval'], $validIntervals)) {
                $errors[] = 'Invalid interval';
            }
        }
        
        if (isset($data['status'])) {
            $validStatuses = ['active', 'cancelled', 'expired', 'pending'];
            if (!in_array($data['status'], $validStatuses)) {
                $errors[] = 'Invalid status';
            }
        }
        
        if (isset($data['start_date'])) {
            if (!$this->validateDate($data['start_date'])) {
                $errors[] = 'Invalid start date format. Use Y-m-d format.';
            }
        }
        
        if (isset($data['end_date'])) {
            if (!$this->validateDate($data['end_date'])) {
                $errors[] = 'Invalid end date format. Use Y-m-d format.';
            }
        }
        
        return $errors;
    }
    
    /**
     * Calculate subscription end date based on start date and interval
     * 
     * @param string $startDate Start date (Y-m-d format)
     * @param string $interval Interval (day, week, month, year)
     * @return string End date (Y-m-d format)
     */
    protected function calculateEndDate($startDate, $interval)
    {
        $date = new \DateTime($startDate);
        
        switch ($interval) {
            case 'day':
                $date->add(new \DateInterval('P1D'));
                break;
            case 'week':
                $date->add(new \DateInterval('P1W'));
                break;
            case 'month':
                $date->add(new \DateInterval('P1M'));
                break;
            case 'year':
                $date->add(new \DateInterval('P1Y'));
                break;
            default:
                $date->add(new \DateInterval('P1M')); // Default to month
        }
        
        return $date->format('Y-m-d');
    }
    
    /**
     * Validate date format
     * 
     * @param string $date Date string
     * @return bool
     */
    protected function validateDate($date)
    {
        $d = \DateTime::createFromFormat('Y-m-d', $date);
        return $d && $d->format('Y-m-d') === $date;
    }
}