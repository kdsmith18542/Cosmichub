<?php
/**
 * Credit Pack Model
 * 
 * Handles all database operations for the credit_packs table.
 */

namespace App\Models;

class CreditPack extends Model {
    /**
     * @var string The database table name
     */
    protected static $table = 'credit_packs';
    
    /**
     * @var string The primary key for the table
     */
    protected static $primaryKey = 'id';
    
    /**
     * @var array The model's fillable attributes
     */
    protected $fillable = [
        'name',
        'description',
        'credits',
        'price',
        'stripe_product_id',
        'stripe_price_id',
        'is_active',
        'sort_order',
        'created_at',
        'updated_at'
    ];
    
    /**
     * The attributes that should be cast
     * 
     * @var array
     */
    protected $casts = [
        'credits' => 'integer',
        'price' => 'float',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    /**
     * Get all active credit packs, ordered by sort_order
     * 
     * @return array
     */
    public static function getActivePacks() {
        return self::where('is_active', '=', 1)->orderBy('sort_order', 'ASC')->get();
    }

    /**
     * Get the pack price formatted with currency
     * 
     * @return string
     */
    public function getFormattedPrice() {
        return '$' . number_format($this->price, 2);
    }
}