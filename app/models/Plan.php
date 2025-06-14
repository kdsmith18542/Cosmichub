<?php
/**
 * Plan Model
 * 
 * Handles all database operations for the plans table.
 */

class Plan extends \App\Core\Database\Model {
    // The table name 'plans' and primary key 'id' will be inferred by the base Model.
    // Timestamps (created_at, updated_at) are handled by the base Model by default.

    /**
     * @var array The model's fillable attributes
     */
    protected $fillable = [
        'name',
        'description',
        'price',
        'billing_cycle',
        'features',
        'is_active',
        'credits',
        'credits_on_renewal',
        'created_at',
        'updated_at',
        'stripe_price_id' // Added for Stripe integration
    ];
    
    /**
     * The attributes that should be cast
     * 
     * @var array
     */
    protected $casts = [
        'price' => 'float',
        'is_active' => 'boolean',
        'credits' => 'integer',
        'credits_on_renewal' => 'integer',
        'features' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];
    
    /**
     * Billing cycle constants
     */
    const BILLING_CYCLE_MONTHLY = 'monthly';
    const BILLING_CYCLE_QUARTERLY = 'quarterly';
    const BILLING_CYCLE_ANNUALLY = 'annually';
    
    /**
     * Get all active plans
     * 
     * @return array
     */
    public static function getActive(): \App\Core\Database\Collection
    {
        return static::query()->where('is_active', true)->get();
    }
    
    /**
     * Get plans by billing cycle
     * 
     * @param string $billingCycle
     * @return array
     */
    public static function getByBillingCycle(string $billingCycle): \App\Core\Database\Collection
    {
        return static::query()
                 ->where('billing_cycle', $billingCycle)
                 ->where('is_active', true)
                 ->get();
    }
    
    /**
     * Get plan features as an array
     * 
     * @return array
     */
    public function getFeatures(): array
    {
        // The 'features' attribute is automatically cast to an array by the base Model
        // due to the $casts property.
        return (array) $this->getAttribute('features');
    }
    
    /**
     * Check if the plan has a specific feature
     * 
     * @param string $feature
     * @return bool
     */
    public function hasFeature($feature) {
        $features = $this->getFeatures();
        return in_array($feature, $features);
    }
    
    /**
     * Get the plan price formatted with currency
     * 
     * @param bool $withBillingCycle Whether to include the billing cycle
     * @return string
     */
    public function getFormattedPrice(bool $withBillingCycle = true): string
    {
        $price = '$' . number_format($this->price, 2);
        
        if ($withBillingCycle) {
            $price .= ' / ' . $this->getBillingCycleName();
        }
        
        return $price;
    }
    
    /**
     * Get the billing cycle name
     * 
     * @return string
     */
    public function getBillingCycleName(): string
    {
        $cycles = self::getBillingCycles();
        return $cycles[$this->billing_cycle] ?? ucfirst($this->billing_cycle);
    }
    
    /**
     * Get all available billing cycles
     * 
     * @return array
     */
    public static function getBillingCycles(): array
    {
        return [
            self::BILLING_CYCLE_MONTHLY => 'Per Month',
            self::BILLING_CYCLE_QUARTERLY => 'Per Quarter',
            self::BILLING_CYCLE_ANNUALLY => 'Per Year'
        ];
    }
    
    /**
     * Get the plan's subscription interval for Stripe
     * 
     * @return array
     */
    public function getSubscriptionInterval(): array
    {
        switch ($this->billing_cycle) {
            case self::BILLING_CYCLE_QUARTERLY:
                return ['interval' => 'month', 'count' => 3];
            case self::BILLING_CYCLE_ANNUALLY:
                return ['interval' => 'year', 'count' => 1];
            case self::BILLING_CYCLE_MONTHLY:
            default:
                return ['interval' => 'month', 'count' => 1];
        }
    }
    
    /**
     * Get the plan's trial period in days
     * 
     * @return int
     */
    public function getTrialPeriodDays(): int
    {
        // Default trial period is 7 days
        return 7;
    }
    
    /**
     * Get the number of credits this plan provides
     * 
     * @return int
     */
    public function getCredits(): int
    {
        return (int) $this->credits;
    }
    
    /**
     * Get the number of reports included in this plan
     * 
     * @return int
     */
    public function getIncludedReports(): int
    {
        // This could be customized based on plan features
        if ($this->hasFeature('unlimited_reports')) {
            return -1; // Unlimited
        }
        
        return $this->getCredits(); // Default to credits if no specific feature
    }
    
    /**
     * Check if this plan includes unlimited reports
     * 
     * @return bool
     */
    public function hasUnlimitedReports(): bool
    {
        return $this->getIncludedReports() === -1;
    }
    
    /**
     * Get the plan's price per report
     * 
     * @return float|null Returns null if no credits/reports are included
     */
    public function getPricePerReport() {
        if ($this->credits <= 0) {
            return null;
        }
        
        return $this->price / $this->credits;
    }
}
