<?php

namespace App\Repositories;

use App\Core\Repository\Repository;
use App\Models\Subscription;
use DateTime;

/**
 * Subscription Repository for handling subscription data operations
 */
class SubscriptionRepository extends Repository
{
    protected string $model = Subscription::class; // Changed from $modelClass

    /**
     * Find subscription by user ID.
     *
     * @param int $userId The user ID.
     * @return Subscription|null
     */
    public function findByUserId(int $userId): ?Subscription
    {
        return $this->newQuery()->where('user_id', $userId)->first();
        // return $this->mapResultToModel($result);
    }

    /**
     * Find subscriptions by plan ID.
     *
     * @param int $planId The subscription plan ID.
     * @return Subscription[]
     */
    public function findByPlanId(int $planId): array
    {
        $results = $this->newQuery()->where('plan_id', $planId)->get();
        return array_map(fn($data) => new $this->model((array)$data), $results);
    }

    /**
     * Find subscription by Stripe subscription ID.
     *
     * @param string $stripeSubscriptionId The Stripe subscription ID.
     * @return Subscription|null
     */
    public function findByStripeSubscriptionId(string $stripeSubscriptionId): ?Subscription
    {
        return $this->newQuery()->where('stripe_subscription_id', $stripeSubscriptionId)->first();
        // return $this->mapResultToModel($result);
    }
    
    /**
     * Find active subscriptions
     * 
     * @return Subscription[]
     */
    public function findActive(): array
    {
        $results = $this->newQuery()
            ->where('status', Subscription::STATUS_ACTIVE)
            ->where('ends_at', '>', date('Y-m-d H:i:s'))
            ->get();
        return array_map(fn($data) => new $this->model((array)$data), $results);
    }
    
    /**
     * Find expired subscriptions
     * 
     * @return Subscription[]
     */
    public function findExpired(): array
    {
        $results = $this->newQuery()
            ->where('ends_at', '<=', date('Y-m-d H:i:s'))
            ->where('status', '!=', Subscription::STATUS_CANCELED)
            ->get();
        return array_map(fn($data) => new $this->model((array)$data), $results);
    }
    
    /**
     * Find subscriptions expiring soon
     * 
     * @param int $days Number of days ahead to check
     * @return Subscription[]
     */
    public function findExpiringSoon(int $days = 7): array
    {
        $futureDate = date('Y-m-d H:i:s', strtotime("+{$days} days"));

        $results = $this->newQuery()
            ->where('status', Subscription::STATUS_ACTIVE)
            ->where('ends_at', '>', date('Y-m-d H:i:s'))
            ->where('ends_at', '<=', $futureDate)
            ->get();
        return array_map(fn($data) => new $this->model((array)$data), $results);
    }
    
    /**
     * Check if user has active subscription
     * 
     * @param int $userId The user ID
     * @return bool
     */
    public function hasActiveSubscription(int $userId): bool
    {
        return (bool) $this->newQuery()
            ->where('user_id', $userId)
            ->where('status', Subscription::STATUS_ACTIVE)
            ->where('ends_at', '>', date('Y-m-d H:i:s'))
            ->exists();
    }
    
    /**
     * Get user's current subscription
     * 
     * @param int $userId The user ID
     * @return Subscription|null
     */
    public function getCurrentSubscription(int $userId): ?Subscription
    {
        return $this->newQuery()
            ->where('user_id', $userId)
            ->where('status', Subscription::STATUS_ACTIVE)
            ->where('ends_at', '>', date('Y-m-d H:i:s'))
            ->first();
        // return $this->mapResultToModel($result);
    }
    
    /**
     * Create or update subscription.
     * 
     * @param int $userId The user ID
     * @param array $data Subscription data, including 'plan_id'
     * @return Subscription|null Returns Subscription instance on success, null on failure.
     */
    public function createOrUpdate(int $userId, array $data): ?Subscription
    {
        $existingSubscription = $this->findByUserId($userId);

        if ($existingSubscription) {
            $this->update($existingSubscription->getId(), $data);
            return $this->findByUserId($userId); // Return the updated model by refetching
        } else {
            $data['user_id'] = $userId;
            return $this->create($data); // Use the base repository's create method
        }
    }
    
    /**
     * Cancel subscription for a user.
     * 
     * @param int $userId The user ID
     * @return bool Returns true on success, false on failure.
     */
    public function cancelSubscription(int $userId): bool
    {
        $affectedRows = $this->newQuery()
            ->where('user_id', $userId)
            ->where('status', Subscription::STATUS_ACTIVE) // Only cancel active subscriptions this way
            ->update([
                'status' => Subscription::STATUS_CANCELED,
                'canceled_at' => date('Y-m-d H:i:s')
            ]);
        return $affectedRows > 0;
    }
    
    /**
     * Renew subscription for a user.
     * 
     * @param int $userId The user ID
     * @param string $newExpiryDate New expiry date
     * @param array $additionalData Optional additional data to update (e.g. plan_id, stripe_subscription_id)
     * @return bool Returns true on success, false on failure.
     */
    public function renewSubscription(int $userId, $newExpiryDate, array $additionalData = []): bool
    {
        $updateData = array_merge([
            'status' => Subscription::STATUS_ACTIVE,
            'ends_at' => is_string($newExpiryDate) ? $newExpiryDate : date('Y-m-d H:i:s', strtotime($newExpiryDate)),
            'renewed_at' => date('Y-m-d H:i:s'),
            'canceled_at' => null // Ensure canceled_at is cleared on renewal
        ], $additionalData);

        $affectedRows = $this->newQuery()
            ->where('user_id', $userId)
            // Allow renewal for active or specific past_due/expired statuses if applicable by business logic
            // For now, let's assume we can renew any non-canceled subscription or one that matches the user ID.
            // ->whereIn('status', [Subscription::STATUS_ACTIVE, Subscription::STATUS_EXPIRED, Subscription::STATUS_PAST_DUE])
            ->update($updateData);

        return $affectedRows > 0;
    }
    
    /**
     * Find subscriptions that are on trial.
     *
     * @return Subscription[]
     */
    public function findOnTrial(): array
    {
        $results = $this->newQuery()
            ->whereNotNull('trial_ends_at')
            ->where('trial_ends_at', '>', date('Y-m-d H:i:s'))
            ->get();
        return array_map(fn($data) => new $this->model((array)$data), $results);
    }

    /**
     * Find subscriptions whose trial period is ending soon.
     *
     * @param int $days
     * @return Subscription[]
     */
    public function findTrialEndingSoon(int $days = 7): array
    {
        $futureDate = date('Y-m-d H:i:s', strtotime("+{$days} days"));
        $results = $this->newQuery()
            ->whereNotNull('trial_ends_at')
            ->where('trial_ends_at', '>', date('Y-m-d H:i:s'))
            ->where('trial_ends_at', '<=', $futureDate)
            ->get();
        return array_map(fn($data) => new $this->model((array)$data), $results);
    }

    /**
     * Get subscriptions by status.
     *
     * @param string $status
     * @return Subscription[]
     */
    public function findByStatus(string $status): array
    {
        $results = $this->newQuery()->where('status', $status)->get();
        return array_map(fn($data) => new $this->model((array)$data), $results);
    }

    /**
     * Count active subscriptions.
     *
     * @return int
     */
    public function countActive(): int
    {
        return $this->newQuery()
            ->where('status', Subscription::STATUS_ACTIVE)
            ->where('ends_at', '>', date('Y-m-d H:i:s'))
            ->count();
    }

    /**
     * Count subscriptions by plan ID.
     *
     * @param int $planId
     * @return int
     */
    public function countByPlanId(int $planId): int
    {
        return $this->newQuery()->where('plan_id', $planId)->count();
    }
}