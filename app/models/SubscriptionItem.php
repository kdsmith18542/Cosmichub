<?php
/**
 * Subscription Item Model
 * 
 * Handles all database operations for the subscription_items table.
 */

class SubscriptionItem extends Model {
    /**
     * @var string The database table name
     */
    protected static $table = 'subscription_items';
    
    /**
     * @var string The primary key for the table
     */
    protected static $primaryKey = 'id';
    
    /**
     * @var array The model's fillable attributes
     */
    protected $fillable = [
        'subscription_id',
        'plan_id',
        'stripe_id',
        'stripe_product',
        'stripe_price',
        'quantity',
        'created_at',
        'updated_at'
    ];
    
    /**
     * The attributes that should be cast
     * 
     * @var array
     */
    protected $casts = [
        'quantity' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];
    
    /**
     * Get the subscription that owns the subscription item
     * 
     * @return Subscription|null
     */
    public function subscription() {
        return Subscription::find($this->subscription_id);
    }
    
    /**
     * Get the plan associated with the subscription item
     * 
     * @return Plan|null
     */
    public function plan() {
        return Plan::find($this->plan_id);
    }
    
    /**
     * Get the total price for this subscription item
     * 
     * @return float
     */
    public function getTotal() {
        $plan = $this->plan();
        return $plan ? $plan->price * $this->quantity : 0;
    }
    
    /**
     * Get the formatted total price
     * 
     * @return string
     */
    public function getFormattedTotal() {
        return '$' . number_format($this->getTotal(), 2);
    }
    
    /**
     * Increment the quantity of the subscription item
     * 
     * @param int $count
     * @return bool
     */
    public function incrementQuantity($count = 1) {
        $this->quantity += $count;
        return $this->save();
    }
    
    /**
     * Decrement the quantity of the subscription item
     * 
     * @param int $count
     * @return bool
     */
    public function decrementQuantity($count = 1) {
        $this->quantity = max(0, $this->quantity - $count);
        return $this->save();
    }
    
    /**
     * Update the quantity of the subscription item
     * 
     * @param int $quantity
     * @return bool
     */
    public function updateQuantity($quantity) {
        $this->quantity = max(0, (int) $quantity);
        return $this->save();
    }
}
