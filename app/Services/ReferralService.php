<?php

namespace App\Services;

use App\Models\Referral;
use App\Core\Service\Service;
use App\Repositories\ReferralRepository;

class ReferralService extends Service
{
    /**
     * @var ReferralRepository
     */
    protected $referralRepository;
    
    /**
     * Initialize the service
     */
    public function __construct($app)
    {
        parent::__construct($app);
        $this->referralRepository = $this->getRepository('ReferralRepository');
    }
    
    /**
     * Create a referral for a user
     *
     * @param int $userId
     * @param string $type
     * @return Referral
     */
    public function createForUser(int $userId, string $type): Referral
    {
        return $this->referralRepository->createForUser($userId, $type);
    }

    /**
     * Get referral by user ID and type
     *
     * @param int $userId
     * @param string $type
     * @return Referral|null
     */
    public function getReferralByUserAndType(int $userId, string $type): ?Referral
    {
        return $this->referralRepository->findByUserAndType($userId, $type);
    }

    /**
     * Get successful referrals count for a referral
     *
     * @param Referral $referral
     * @return int
     */
    public function getSuccessfulReferralsCount(Referral $referral): int
    {
        return $referral->successful_referrals;
    }

    /**
     * Get referral URL for a referral
     *
     * @param Referral $referral
     * @return string
     */
    public function getReferralUrl(Referral $referral): string
    {
        return $referral->getReferralUrl();
    }

    /**
     * Check if referral has enough successful referrals
     *
     * @param Referral $referral
     * @param int $requiredCount
     * @return bool
     */
    public function hasEnoughReferrals(Referral $referral, int $requiredCount = 3): bool
    {
        return $referral->successful_referrals >= $requiredCount;
    }
}