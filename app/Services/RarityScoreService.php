<?php

namespace App\Services;

use DateTime;
use Exception;
use App\Core\Service\BaseService;
use App\Models\User;
use App\Models\Referral;
use App\Repositories\ReferralRepository;

/**
 * Rarity Score Service
 * 
 * Handles the business logic for calculating birthday rarity scores
 * This service has been created as part of the refactoring plan to
 * separate business logic from controllers and improve testability.
 */
class RarityScoreService extends BaseService
{
    /**
     * @var ReferralRepository
     */
    protected $referralRepository;
    
    /**
     * Constructor
     */
    public function __construct(ReferralRepository $referralRepository)
    {
        $this->referralRepository = $referralRepository;
    }
    
    /**
     * Calculate the rarity score for a given birthdate
     * 
     * @param string $birthdate The birthdate in Y-m-d format
     * @return array Array containing score, description, color, and explanation
     * @throws Exception If birthdate is invalid
     */
    public function calculateRarityScore(string $birthdate): array
    {
        if (!$this->validateDate($birthdate)) {
            throw new Exception('Invalid birthdate format. Please use YYYY-MM-DD');
        }

        try {
            // Parse the birthdate
            $date = new DateTime($birthdate);
            $month = (int)$date->format('n'); // 1-12
            $day = (int)$date->format('j');   // 1-31
            $year = (int)$date->format('Y');
            
            // Base rarity factors
            $monthRarity = $this->getMonthRarityFactor($month);
            $dayRarity = $this->getDayRarityFactor($month, $day);
            $specialDateRarity = $this->getSpecialDateRarity($month, $day);
            $leapYearRarity = $this->getLeapYearRarity($month, $day, $year);
            
            // Calculate final score (1-100 scale)
            $rawScore = $monthRarity + $dayRarity + $specialDateRarity + $leapYearRarity;
            $normalizedScore = min(100, max(1, $rawScore));
            
            return [
                'score' => $normalizedScore,
                'description' => $this->getRarityDescription($normalizedScore),
                'color' => $this->getRarityColor($normalizedScore),
                'explanation' => $this->getRarityExplanation($normalizedScore),
                'factors' => [
                    'month' => [
                        'value' => $monthRarity,
                        'description' => $this->getMonthFactorDescription($month)
                    ],
                    'day' => [
                        'value' => $dayRarity,
                        'description' => $this->getDayFactorDescription($month, $day)
                    ],
                    'special_date' => [
                        'value' => $specialDateRarity,
                        'description' => $this->getSpecialDateDescription($month, $day)
                    ],
                    'leap_year' => [
                        'value' => $leapYearRarity,
                        'description' => $this->getLeapYearDescription($month, $day, $year)
                    ]
                ]
            ];
            
        } catch (Exception $e) {
            $this->logger->error('Error calculating rarity score', [
                'birthdate' => $birthdate,
                'error' => $e->getMessage()
            ]);
            
            // Return default middle score in case of error
            return [
                'score' => 50,
                'description' => 'Common',
                'color' => '#C70039',
                'explanation' => 'Unable to calculate rarity score due to an error.',
                'factors' => []
            ];
        }
    }

    /**
     * Get rarity factor based on month
     * Some months have more births than others
     * 
     * @param int $month Month (1-12)
     * @return int Rarity factor
     */
    private function getMonthRarityFactor(int $month): int
    {
        // Birth rate by month (higher number = more common birth month)
        // Based on statistical data of birth frequencies
        $monthBirthRates = [
            1 => 40,  // January
            2 => 38,  // February
            3 => 42,  // March
            4 => 44,  // April
            5 => 45,  // May
            6 => 46,  // June
            7 => 50,  // July
            8 => 55,  // August (most common)
            9 => 53,  // September (second most common)
            10 => 48, // October
            11 => 42, // November
            12 => 41  // December
        ];
        
        // Invert the scale so less common months get higher rarity scores
        $maxRate = max($monthBirthRates);
        $invertedRate = $maxRate - $monthBirthRates[$month];
        
        // Scale to a reasonable range (0-25)
        return (int)(($invertedRate / $maxRate) * 25);
    }

    /**
     * Get rarity factor based on day of month
     * Some days have more births than others
     * 
     * @param int $month Month (1-12)
     * @param int $day Day (1-31)
     * @return int Rarity factor
     */
    private function getDayRarityFactor(int $month, int $day): int
    {
        // Days at the beginning/end of months tend to have fewer births
        // Days around holidays tend to have fewer births
        
        // Calculate days from middle of month (15th or 16th)
        $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, 2020); // Using 2020 as a leap year
        $midMonth = ceil($daysInMonth / 2);
        $distanceFromMid = abs($day - $midMonth);
        
        // Days further from mid-month are rarer
        $dayFactor = (int)(($distanceFromMid / $midMonth) * 20);
        
        // Check for specific rare days
        if ($month == 2 && $day == 29) {
            // February 29 (leap day) is very rare
            $dayFactor += 25;
        }
        
        return $dayFactor;
    }

    /**
     * Get rarity bonus for special dates
     * 
     * @param int $month Month (1-12)
     * @param int $day Day (1-31)
     * @return int Rarity bonus
     */
    private function getSpecialDateRarity(int $month, int $day): int
    {
        // Special dates that typically have fewer births
        $specialDates = [
            '1-1'   => 30, // New Year's Day
            '12-25' => 30, // Christmas
            '12-24' => 25, // Christmas Eve
            '12-31' => 25, // New Year's Eve
            '7-4'   => 20, // Independence Day (US)
            '10-31' => 15, // Halloween
            '2-14'  => 10, // Valentine's Day
            '11-11' => 10, // Veterans Day
            '3-17'  => 10, // St. Patrick's Day
            '5-5'   => 10, // Cinco de Mayo
            '4-1'   => 5,  // April Fool's Day
        ];
        
        $dateKey = $month . '-' . $day;
        return $specialDates[$dateKey] ?? 0;
    }

    /**
     * Get rarity bonus for leap year births
     * 
     * @param int $month Month (1-12)
     * @param int $day Day (1-31)
     * @param int $year Year
     * @return int Rarity bonus
     */
    private function getLeapYearRarity(int $month, int $day, int $year): int
    {
        // Check if birth year was a leap year
        $isLeapYear = date('L', strtotime("$year-01-01")) == 1;
        
        // February 29 already handled in getDayRarityFactor
        if ($isLeapYear && !($month == 2 && $day == 29)) {
            return 5; // Small bonus for being born in a leap year
        }
        
        return 0;
    }

    /**
     * Get a description of the rarity score
     * 
     * @param int $score The rarity score (1-100)
     * @return string Description of the rarity
     */
    private function getRarityDescription(int $score): string
    {
        if ($score >= 90) {
            return "Extremely Rare";
        } elseif ($score >= 75) {
            return "Very Rare";
        } elseif ($score >= 60) {
            return "Rare";
        } elseif ($score >= 40) {
            return "Uncommon";
        } elseif ($score >= 25) {
            return "Common";
        } else {
            return "Very Common";
        }
    }

    /**
     * Get a color code for the rarity score
     * 
     * @param int $score The rarity score (1-100)
     * @return string Hex color code
     */
    private function getRarityColor(int $score): string
    {
        if ($score >= 90) {
            return "#FF5733"; // Orange-red
        } elseif ($score >= 75) {
            return "#FFC300"; // Gold
        } elseif ($score >= 60) {
            return "#DAF7A6"; // Light green
        } elseif ($score >= 40) {
            return "#C70039"; // Crimson
        } elseif ($score >= 25) {
            return "#900C3F"; // Burgundy
        } else {
            return "#581845"; // Purple
        }
    }

    /**
     * Get a detailed explanation of the rarity score
     * 
     * @param int $score The rarity score
     * @return string Explanation text
     */
    private function getRarityExplanation(int $score): string
    {
        if ($score >= 90) {
            return "Your birthday is extremely rare! Only a tiny fraction of people share your birth date. This makes your birthday truly special and unique.";
        } elseif ($score >= 75) {
            return "Your birthday is very rare. It falls on a date that very few people share, making it quite special.";
        } elseif ($score >= 60) {
            return "Your birthday is rare. It occurs less frequently than most other birth dates, giving you a somewhat unique birthday.";
        } elseif ($score >= 40) {
            return "Your birthday is somewhat uncommon. While not extremely rare, it's less common than average.";
        } elseif ($score >= 25) {
            return "Your birthday is fairly common. Many people share this birth date, but it's not among the most common.";
        } else {
            return "Your birthday is very common. Many people share this birth date, as it falls during a popular time for births.";
        }
    }

    /**
     * Get description of month factor
     * 
     * @param int $month Month (1-12)
     * @return string Description
     */
    private function getMonthFactorDescription(int $month): string
    {
        $monthNames = [
            1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
            5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
            9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
        ];
        
        $commonMonths = [7, 8, 9]; // July, August, September
        $uncommonMonths = [1, 2, 11, 12]; // January, February, November, December
        
        if (in_array($month, $commonMonths)) {
            return "{$monthNames[$month]} is one of the most common birth months. Many babies are conceived in the fall and winter months, leading to summer and early fall births.";
        } elseif (in_array($month, $uncommonMonths)) {
            return "{$monthNames[$month]} is one of the less common birth months, which adds to your birthday's uniqueness.";
        } else {
            return "{$monthNames[$month]} is a moderately common birth month.";
        }
    }

    /**
     * Get description of day factor
     * 
     * @param int $month Month (1-12)
     * @param int $day Day (1-31)
     * @return string Description
     */
    private function getDayFactorDescription(int $month, int $day): string
    {
        $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, 2020); // Using 2020 as a leap year
        $midMonth = ceil($daysInMonth / 2);
        $distanceFromMid = abs($day - $midMonth);
        
        if ($month == 2 && $day == 29) {
            return "Being born on February 29 (Leap Day) is extremely rare! This date only occurs once every four years, making your birthday very special.";
        } elseif ($day == 1) {
            return "Being born on the first day of the month is somewhat uncommon and adds to your birthday's uniqueness.";
        } elseif ($day == $daysInMonth) {
            return "Being born on the last day of the month is somewhat uncommon and adds to your birthday's uniqueness.";
        } elseif ($distanceFromMid > ($daysInMonth / 3)) {
            return "Your birth day is further from the middle of the month, which tends to be slightly less common.";
        } else {
            return "Your birth day is close to the middle of the month, which is fairly common.";
        }
    }

    /**
     * Get description of special date factor
     * 
     * @param int $month Month (1-12)
     * @param int $day Day (1-31)
     * @return string Description
     */
    private function getSpecialDateDescription(int $month, int $day): string
    {
        // Special dates that typically have fewer births
        $specialDates = [
            '1-1'   => "New Year's Day",
            '12-25' => "Christmas Day",
            '12-24' => "Christmas Eve",
            '12-31' => "New Year's Eve",
            '7-4'   => "Independence Day (US)",
            '10-31' => "Halloween",
            '2-14'  => "Valentine's Day",
            '11-11' => "Veterans Day",
            '3-17'  => "St. Patrick's Day",
            '5-5'   => "Cinco de Mayo",
            '4-1'   => "April Fool's Day",
        ];
        
        $dateKey = $month . '-' . $day;
        
        if (isset($specialDates[$dateKey])) {
            return "Your birthday falls on {$specialDates[$dateKey]}, which is a special holiday! Births on holidays are less common, making your birthday more unique.";
        }
        
        // Check for proximity to holidays
        foreach ($specialDates as $key => $holiday) {
            list($hMonth, $hDay) = explode('-', $key);
            
            // If same month and within 1 day
            if ($month == $hMonth && abs($day - $hDay) <= 1) {
                return "Your birthday is very close to {$holiday}, which is interesting! Births near holidays are somewhat less common.";
            }
        }
        
        return "Your birthday doesn't fall on or near any major holidays or special dates in our database.";
    }

    /**
     * Get description of leap year factor
     * 
     * @param int $month Month (1-12)
     * @param int $day Day (1-31)
     * @param int $year Year
     * @return string Description
     */
    private function getLeapYearDescription(int $month, int $day, int $year): string
    {
        // February 29 already handled in getDayFactorDescription
        if ($month == 2 && $day == 29) {
            return "Being born on February 29 is extremely rare and only happens once every four years!";
        }
        
        // Check if birth year was a leap year
        $isLeapYear = date('L', strtotime("$year-01-01")) == 1;
        
        if ($isLeapYear) {
            return "You were born in a leap year ({$year}), which occurs only once every four years. This adds a small element of uniqueness to your birth date.";
        } else {
            return "You were not born in a leap year. Your birth year ({$year}) was a standard 365-day year.";
        }
    }

    /**
     * Validate date format
     * 
     * @param string $date Date string to validate
     * @param string $format Expected format
     * @return bool True if valid, false otherwise
     */
    private function validateDate(string $date, string $format = 'Y-m-d'): bool
    {
        $d = DateTime::createFromFormat($format, $date);
        return $d && $d->format($format) === $date;
    }

    /**
     * Get user's rarity score data
     * 
     * @param User $user The user object
     * @return array|null Rarity score data or null if no birthdate
     */
    public function getUserRarityData(User $user): ?array
    {
        if (empty($user->birthdate)) {
            return null;
        }

        return $this->calculateRarityScore($user->birthdate);
    }

    /**
     * Check if user has enough referrals to unlock rarity score
     * 
     * @param User $user The user object
     * @param int $requiredReferrals Number of required referrals
     * @return array Referral status data
     */
    public function getReferralStatus(User $user, int $requiredReferrals = 3): array
    {
        $referral = $this->referralRepository->createForUser($user->id, Referral::TYPE_RARITY_SCORE);
        
        return [
            'referral' => $referral,
            'referralUrl' => $referral->getReferralUrl(),
            'hasEnoughReferrals' => $referral->hasEnoughReferrals($requiredReferrals),
            'remainingReferrals' => max(0, $requiredReferrals - $referral->successful_referrals),
            'successfulReferrals' => $referral->successful_referrals
        ];
    }

    /**
     * Handle incoming referral
     * 
     * @param string $refCode The referral code
     * @param int $currentUserId The current user's ID
     * @return bool True if referral was processed successfully
     */
    public function handleReferral(string $refCode, int $currentUserId): bool
    {
        // Find the referral by code
        $referral = $this->referralRepository->findByCode($refCode);
        
        if (!$referral) {
            $this->logger->warning('Invalid referral code used', [
                'code' => $refCode,
                'user_id' => $currentUserId
            ]);
            return false;
        }
        
        // Don't allow self-referrals
        if ($referral->user_id == $currentUserId) {
            $this->logger->warning('Self-referral attempted', [
                'code' => $refCode,
                'user_id' => $currentUserId
            ]);
            return false;
        }
        
        // Track the referral
        $success = $referral->trackReferral($currentUserId);
        
        if ($success) {
            $this->logger->info('Referral processed successfully', [
                'code' => $refCode,
                'referrer_id' => $referral->user_id,
                'referee_id' => $currentUserId
            ]);
        }
        
        return $success;
    }
}