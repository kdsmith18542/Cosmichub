<?php
/**
 * Credit Transaction Model
 * 
 * Handles all database operations for the credit_transactions table.
 */

namespace App\Models;

class CreditTransaction extends Model {
    /**
     * @var string The database table name
     */
    protected static $table = 'credit_transactions';
    
    /**
     * @var string The primary key for the table
     */
    protected static $primaryKey = 'id';
    
    /**
     * @var array The model's fillable attributes
     */
    protected $fillable = [
        'user_id',
        'amount',
        'type',
        'description',
        'reference_id',
        'reference_type',
        'metadata',
        'created_at',
        'updated_at'
    ];
    
    /**
     * The attributes that should be cast
     * 
     * @var array
     */
    protected $casts = [
        'amount' => 'integer',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];
    
    /**
     * Transaction type constants
     */
    const TYPE_CREDIT = 'credit';
    const TYPE_DEBIT = 'debit';
    const TYPE_CREDIT_PURCHASE = 'credit_purchase'; // For one-time credit pack purchases
    
    /**
     * Reference type constants
     */
    const REF_PURCHASE = 'purchase';
    const REF_SUBSCRIPTION = 'subscription';
    const REF_REFUND = 'refund';
    const REF_ADJUSTMENT = 'adjustment';
    const REF_REPORT = 'report';
    const REF_SYSTEM = 'system';
    
    /**
     * Get the user that owns the transaction
     * 
     * @return User|null
     */
    public function user() {
        return User::find($this->user_id);
    }
    
    /**
     * Get the transaction amount as a formatted string
     * 
     * @return string
     */
    public function getFormattedAmount() {
        $prefix = $this->type === self::TYPE_CREDIT ? '+' : '-';
        return $prefix . $this->amount . ' credits';
    }
    
    /**
     * Get the transaction type as a human-readable string
     * 
     * @return string
     */
    public function getTypeName() {
        $types = self::getTypes();
        return $types[$this->type] ?? ucfirst($this->type);
    }
    
    /**
     * Get the reference type as a human-readable string
     * 
     * @return string
     */
    public function getReferenceTypeName() {
        $types = self::getReferenceTypes();
        return $types[$this->reference_type] ?? ucfirst($this->reference_type);
    }
    
    /**
     * Get all available transaction types
     * 
     * @return array
     */
    public static function getTypes() {
        return [
            self::TYPE_CREDIT => 'Credit',
            self::TYPE_DEBIT => 'Debit',
            self::TYPE_CREDIT_PURCHASE => 'Credit Purchase'
        ];
    }
    
    /**
     * Get all available reference types
     * 
     * @return array
     */
    public static function getReferenceTypes() {
        return [
            self::REF_PURCHASE => 'Purchase',
            self::REF_SUBSCRIPTION => 'Subscription',
            self::REF_REFUND => 'Refund',
            self::REF_ADJUSTMENT => 'Adjustment',
            self::REF_REPORT => 'Report',
            self::REF_SYSTEM => 'System'
        ];
    }
    
    /**
     * Get the transaction metadata as an array
     * 
     * @return array
     */
    public function getMetadata() {
        if (is_string($this->metadata)) {
            return json_decode($this->metadata, true) ?: [];
        }
        return (array) $this->metadata;
    }
    
    /**
     * Add a credit transaction
     * 
     * @param int $userId
     * @param int $amount
     * @param string $referenceType
     * @param mixed $referenceId
     * @param string $description
     * @param array $metadata
     * @return bool
     */
    public static function addCredit($userId, $amount, $referenceType, $referenceId = null, $description = '', $metadata = []) {
        return self::create([
            'user_id' => $userId,
            'amount' => abs($amount),
            'type' => self::TYPE_CREDIT,
            'description' => $description,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'metadata' => json_encode($metadata),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);
    }
    
    /**
     * Add a debit transaction
     * 
     * @param int $userId
     * @param int $amount
     * @param string $referenceType
     * @param mixed $referenceId
     * @param string $description
     * @param array $metadata
     * @return bool
     */
    public static function addDebit($userId, $amount, $referenceType, $referenceId = null, $description = '', $metadata = []) {
        return self::create([
            'user_id' => $userId,
            'amount' => abs($amount),
            'type' => self::TYPE_DEBIT,
            'description' => $description,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'metadata' => json_encode($metadata),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);
    }
    
    /**
     * Get the user's current credit balance
     * 
     * @param int $userId
     * @return int
     */
    public static function getBalance($userId) {
        $credits = self::where('user_id', '=', $userId)
                      ->where('type', '=', self::TYPE_CREDIT)
                      ->sum('amount');
        
        $debits = self::where('user_id', '=', $userId)
                     ->where('type', '=', self::TYPE_DEBIT)
                     ->sum('amount');
        
        return $credits - $debits;
    }
    
    /**
     * Get the user's transaction history
     * 
     * @param int $userId
     * @param int $limit
     * @return array
     */
    public static function getUserHistory($userId, $limit = 10) {
        return self::where('user_id', '=', $userId)
                  ->orderBy('created_at', 'desc')
                  ->limit($limit)
                  ->get();
    }
}
