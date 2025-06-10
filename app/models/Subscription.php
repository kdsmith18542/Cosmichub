<?php
/**
 * Subscription Model
 * 
 * Handles all database operations for the subscriptions table.
 */

class Subscription extends Model {
    /**
     * @var string The database table name
     */
    protected static $table = 'subscriptions';
    
    /**
     * @var string The primary key for the table
     */
    protected static $primaryKey = 'id';
    
    /**
     * @var array The model's fillable attributes
     */
    protected $fillable = [
        'user_id',
        'plan_id',
        'status',
        'starts_at',
        'ends_at',
        'canceled_at',
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
    
    /**
     * Get the user that owns the subscription
     * 
     * @return User|null
     */
    public function user() {
        return User::find($this->user_id);
    }
    
    /**
     * Get the plan associated with the subscription
     * 
     * @return Plan|null
     */
    public function plan() {
        return Plan::find($this->plan_id);
    }
    
    /**
     * Get the subscription items for this subscription
     * 
     * @return array
     */
    public function items() {
        return SubscriptionItem::where('subscription_id', '=', $this->id)->get();
    }
    
    /**
     * Check if the subscription is active
     * 
     * @return bool
     */
    public function isActive() {
        return $this->status === self::STATUS_ACTIVE && 
               $this->ends_at > date('Y-m-d H:i:s');
    }
    
    /**
     * Check if the subscription is canceled
     * 
     * @return bool
     */
    public function isCanceled() {
        return $this->status === self::STATUS_CANCELED || $this->canceled_at !== null;
    }
    
    /**
     * Check if the subscription is past due
     * 
     * @return bool
     */
    public function isPastDue() {
        return $this->status === self::STATUS_PAST_DUE;
    }
    
    /**
     * Check if the subscription is on trial
     * 
     * @return bool
     */
    public function onTrial() {
        return $this->status === self::STATUS_TRIALING && $this->trial_ends_at > date('Y-m-d H:i:s');
    }
    
    /**
     * Cancel the subscription
     * 
     * @param bool $atPeriodEnd Whether to cancel at the end of the billing period
     * @return bool
     */
    public function cancel($atPeriodEnd = true) {
        if ($atPeriodEnd) {
            // Mark as canceled but let it run until the end of the period
            $this->status = self::STATUS_ACTIVE;
            $this->canceled_at = date('Y-m-d H:i:s');
        } else {
            // Cancel immediately
            $this->status = self::STATUS_CANCELED;
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
    public function resume() {
        if (!$this->isCanceled()) {
            return false;
        }
        
        $this->status = self::STATUS_ACTIVE;
        $this->canceled_at = null;
        
        // Set a new end date (e.g., 1 month from now)
        $this->ends_at = date('Y-m-d H:i:s', strtotime('+1 month'));
        
        return $this->save();
    }
    
    /**
     * Get the subscription status as a human-readable string
     * 
     * @return string
     */
    public function getStatusName() {
        $statuses = self::getStatuses();
        return $statuses[$this->status] ?? ucfirst($this->status);
    }
    
    /**
     * Get all available subscription statuses
     * 
     * @return array
     */
    public static function getStatuses() {
        return [
            self::STATUS_ACTIVE => 'Active',
            self::STATUS_CANCELED => 'Canceled',
            self::STATUS_EXPIRED => 'Expired',
            self::STATUS_PAST_DUE => 'Past Due',
            self::STATUS_UNPAID => 'Unpaid',
            self::STATUS_INCOMPLETE => 'Incomplete',
            self::STATUS_INCOMPLETE_EXPIRED => 'Incomplete Expired',
            self::STATUS_TRIALING => 'Trialing'
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
