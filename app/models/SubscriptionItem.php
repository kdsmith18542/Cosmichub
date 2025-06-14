<?php
/**
 * Subscription Item Model
 * 
 * Handles all database operations for the subscription_items table.
 */

namespace App\Models;

use App\Core\Database\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubscriptionItem extends Model {
    /**
     * @var string The database table name
     */
    // protected static $table = 'subscription_items'; // Conventionally handled by base Model
    
    /**
     * @var string The primary key for the table
     */
    // protected static $primaryKey = 'id'; // Conventionally handled by base Model
    
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
        // 'created_at', // Handled by timestamps
        // 'updated_at'  // Handled by timestamps
    ];
    
    /**
     * The attributes that should be cast
     * 
     * @var array
     */
    protected $casts = [
        'quantity' => 'integer',
        // 'created_at' and 'updated_at' are handled by Eloquent's $timestamps property (true by default)
    ];

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = true;
    
    /**
     * Get the subscription that owns the subscription item.
     * 
     * @return BelongsTo
     */
    public function subscription(): BelongsTo 
    {
        return $this->belongsTo(Subscription::class);
    }
    
    /**
     * Get the plan associated with the subscription item.
     * 
     * @return BelongsTo
     */
    public function plan(): BelongsTo 
    {
        return $this->belongsTo(Plan::class);
    }
    
    /**
     * Get the total price for this subscription item.
     * 
     * @return float
     */
    public function getTotal(): float 
    {
        return $this->plan ? (float) $this->plan->price * $this->quantity : 0.0;
    }
    
    /**
     * Get the formatted total price.
     * 
     * @return string
     */
    public function getFormattedTotal(): string 
    {
        return '$' . number_format($this->getTotal(), 2);
    }
    
    /**
     * Increment the quantity of the subscription item.
     * 
     * @param int $count
     * @return bool
     */
    public function incrementQuantity(int $count = 1): bool 
    {
        $this->quantity += $count;
        return $this->save();
    }
    
    /**
     * Decrement the quantity of the subscription item.
     * 
     * @param int $count
     * @return bool
     */
    public function decrementQuantity(int $count = 1): bool 
    {
        $this->quantity = max(0, $this->quantity - $count);
        return $this->save();
    }
    
    /**
     * Update the quantity of the subscription item.
     * 
     * @param int $quantity
     * @return bool
     */
    public function updateQuantity(int $quantity): bool 
    {
        $this->quantity = max(0, $quantity);
        return $this->save();
    }
}
