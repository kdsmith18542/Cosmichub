<?php

namespace App\Models;

use App\Core\Database\Model;

/**
 * Gift Model
 * 
 * Represents a gift entity
 */
class Gift extends Model
{
    protected $table = 'gifts';
    
    protected $fillable = [
        'gift_code',
        'sender_user_id',
        'sender_name',
        'recipient_email',
        'recipient_name',
        'gift_message',
        'credits_amount',
        'plan_id',
        'purchase_amount',
        'stripe_payment_intent_id',
        'status',
        'expires_at',
        'redeemed_by_user_id',
        'redeemed_at'
    ];
    
    /**
     * Gift constructor
     * 
     * @param array $attributes
     */
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
    }
    
    /**
     * Get the gift code
     * 
     * @return string|null
     */
    public function getGiftCode(): ?string
    {
        return $this->getAttribute('gift_code');
    }
    
    /**
     * Get the sender user ID
     * 
     * @return int|null
     */
    public function getSenderUserId(): ?int
    {
        return $this->getAttribute('sender_user_id');
    }
    
    /**
     * Get the recipient email
     * 
     * @return string|null
     */
    public function getRecipientEmail(): ?string
    {
        return $this->getAttribute('recipient_email');
    }
    
    /**
     * Get the credits amount
     * 
     * @return int|null
     */
    public function getCreditsAmount(): ?int
    {
        return $this->getAttribute('credits_amount');
    }
    
    /**
     * Get the gift status
     * 
     * @return string|null
     */
    public function getStatus(): ?string
    {
        return $this->getAttribute('status');
    }
    
    /**
     * Check if gift is pending
     * 
     * @return bool
     */
    public function isPending(): bool
    {
        return $this->getStatus() === 'pending';
    }
    
    /**
     * Check if gift is redeemed
     * 
     * @return bool
     */
    public function isRedeemed(): bool
    {
        return $this->getStatus() === 'redeemed';
    }
    
    /**
     * Check if gift is expired
     * 
     * @return bool
     */
    public function isExpired(): bool
    {
        if ($this->getStatus() === 'expired') {
            return true;
        }
        
        $expiresAt = $this->getAttribute('expires_at');
        return $expiresAt && strtotime($expiresAt) < time();
    }
    
}