<?php
/**
 * Plan Model
 * 
 * Handles all database operations for the plans table.
 */

class Plan extends Model {
    /**
     * @var string The database table name
     */
    protected static $table = 'plans';
    
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
    public static function getActive() {
        return self::where('is_active', '=', 1)->get();
    }
    
    /**
     * Get plans by billing cycle
     * 
     * @param string $billingCycle
     * @return array
     */
    public static function getByBillingCycle($billingCycle) {
        return self::where('billing_cycle', '=', $billingCycle)
                 ->where('is_active', '=', 1)
                 ->get();
    }
    
    /**
     * Get plan features as an array
     * 
     * @return array
     */
    public function getFeatures() {
        if (is_string($this->features)) {
            return json_decode($this->features, true) ?: [];
        }
        return (array) $this->features;
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
    public function getFormattedPrice($withBillingCycle = true) {
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
    public function getBillingCycleName() {
        $cycles = self::getBillingCycles();
        return $cycles[$this->billing_cycle] ?? ucfirst($this->billing_cycle);
    }
    
    /**
     * Get all available billing cycles
     * 
     * @return array
     */
    public static function getBillingCycles() {
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
    public function getSubscriptionInterval() {
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
    public function getTrialPeriodDays() {
        // Default trial period is 7 days
        return 7;
    }
    
    /**
     * Get the number of credits this plan provides
     * 
     * @return int
     */
    public function getCredits() {
        return (int) $this->credits;
    }
    
    /**
     * Get the number of reports included in this plan
     * 
     * @return int
     */
    public function getIncludedReports() {
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
    public function hasUnlimitedReports() {
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
