<?php
/**
 * Subscription Model
 * 
 * Handles all database operations for the subscriptions table.
 */
namespace App\Models;

use App\Core\Database\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subscription extends Model
{
    /**
     * @var string The database table name
     */

    
    /**
     * @var array The model's fillable attributes
     */
    protected $fillable = [
        'user_id',
        'plan_id',
        'stripe_subscription_id', // Added for Stripe integration
        'stripe_customer_id', // Added for Stripe integration
        'status',
        'starts_at',
        'ends_at',
        'canceled_at',
        'trial_ends_at', // Added for Stripe trials
        'created_at',
        'updated_at'
    ];
    
    /**
     * The attributes that should be cast
     * 
     * @var array
     */
    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'canceled_at' => 'datetime',
        'trial_ends_at' => 'datetime', // Added for Stripe trials
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];
    
    /**
     * Subscription status constants
     */
    const STATUS_ACTIVE = 'active';
    const STATUS_CANCELED = 'canceled';
    const STATUS_EXPIRED = 'expired';
    const STATUS_PAST_DUE = 'past_due';
    const STATUS_UNPAID = 'unpaid';
    const STATUS_INCOMPLETE = 'incomplete';
    const STATUS_INCOMPLETE_EXPIRED = 'incomplete_expired';
    const STATUS_TRIALING = 'trialing';
    const STATUS_DELETED = 'deleted'; // Added for subscriptions fully removed
    
    /**
     * Get the user that owns the subscription
     * 
     * @return User|null
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    
    /**
     * Get the plan associated with the subscription
     * 
     * @return Plan|null
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }
    
    /**
     * Get the subscription items for this subscription
     * 
     * @return array
     */
    public function items(): HasMany
    {
        return $this->hasMany(SubscriptionItem::class);
    }
    
    /**
     * Check if the subscription is active
     * 
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->status === static::STATUS_ACTIVE &&
               (strtotime($this->ends_at) > time());
    }
    
    /**
     * Check if the subscription is canceled
     * 
     * @return bool
     */
    public function isCanceled(): bool
    {
        return $this->status === static::STATUS_CANCELED || $this->canceled_at !== null;
    }
    
    /**
     * Check if the subscription is past due
     * 
     * @return bool
     */
    public function isPastDue(): bool
    {
        return $this->status === static::STATUS_PAST_DUE;
    }
    
    /**
     * Check if the subscription is on trial
     * 
     * @return bool
     */
    public function onTrial(): bool
    {
        return $this->status === static::STATUS_TRIALING &&
               (strtotime($this->trial_ends_at) > time());
    }
    
    /**
     * Cancel the subscription
     * 
     * @param bool $atPeriodEnd Whether to cancel at the end of the billing period
     * @return bool
     */
    public function cancel(bool $atPeriodEnd = true): bool
    {
        if ($atPeriodEnd) {
            // Mark as canceled but let it run until the end of the period
            // The status remains active until ends_at is reached.
            // Stripe handles this by setting cancel_at_period_end = true on their end.
            // We just record the canceled_at timestamp.
            $this->canceled_at = date('Y-m-d H:i:s');
        } else {
            // Cancel immediately
            $this->status = static::STATUS_CANCELED;
            $this->canceled_at = date('Y-m-d H:i:s');
            $this->ends_at = date('Y-m-d H:i:s');
        }

        return $this->save();
    }
    
    /**
     * Resume a canceled subscription
     * 
     * @return bool
     */
    public function resume(): bool
    {
        if (!$this->isCanceled() && $this->status !== static::STATUS_CANCELED) {
            // Can only resume if it was actually canceled and not just marked for cancellation at period end
            return false;
        }

        // To truly resume, Stripe API would be called to uncancel.
        // For local state, we revert the cancellation markers.
        $this->status = static::STATUS_ACTIVE;
        $this->canceled_at = null;

        // If ends_at was set to now() due to immediate cancellation, it needs to be restored.
        // This typically involves fetching the original period end from Stripe or recalculating based on plan.
        // For simplicity, let's assume it's extended by a month from now if it was in the past.
        if (strtotime($this->ends_at) < time()) {
            $this->ends_at = date('Y-m-d H:i:s', strtotime('+1 month'));
        }

        return $this->save();
    }
    
    /**
     * Get the subscription status as a human-readable string
     * 
     * @return string
     */
    public function getStatusName(): string
    {
        $statuses = static::getStatuses();
        return $statuses[$this->status] ?? ucfirst((string) $this->status);
    }
    
    /**
     * Get all available subscription statuses
     * 
     * @return array
     */
    public static function getStatuses(): array
    {
        return [
            static::STATUS_ACTIVE => 'Active',
            static::STATUS_CANCELED => 'Canceled',
            static::STATUS_EXPIRED => 'Expired',
            static::STATUS_PAST_DUE => 'Past Due',
            static::STATUS_UNPAID => 'Unpaid',
            static::STATUS_INCOMPLETE => 'Incomplete',
            static::STATUS_INCOMPLETE_EXPIRED => 'Incomplete Expired',
            static::STATUS_TRIALING => 'Trialing',
            static::STATUS_DELETED => 'Deleted',
        ];
    }
    
    /**
     * Get the subscription's next billing date
     * 
     * @return string|null
     */
    public function getNextBillingDate() {
        if ($this->isCanceled() || !$this->isActive()) {
            return null;
        }
        
        return $this->ends_at;
    }
    
    /**
     * Get the subscription's remaining days
     * 
     * @return int|null
     */
    public function getRemainingDays() {
        if ($this->isCanceled() || !$this->isActive()) {
            return null;
        }
        
        $now = new DateTime();
        $end = new DateTime($this->ends_at);
        $interval = $now->diff($end);
        
        return (int) $interval->format('%r%a');
    }
    
    /**
     * Get the subscription's total value
     * 
     * @return float
     */
    public function getTotalValue() {
        $total = 0;
        
        foreach ($this->items() as $item) {
            $total += $item->quantity * $item->price;
        }
        
        return $total;
    }
}
