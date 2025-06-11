<?php
/**
 * Compatibility Controller
 * 
 * Handles the Cosmic Connection compatibility report feature
 * This is a key viral growth feature from the project blueprint
 */

// Load required files
require_once __DIR__ . '/../libraries/Controller.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../helpers/auth.php';

class CompatibilityController extends \App\Libraries\Controller {
    
    /**
     * Show the compatibility report form
     */
    public function index()
    {
        // Require authentication
        if (!auth_check()) {
            redirect('/login?redirect=/compatibility');
            return;
        }
        $user = auth_user();
        $hasActiveSubscription = false;
        if ($user && method_exists($user, 'hasActiveSubscription')) {
            $hasActiveSubscription = $user->hasActiveSubscription();
        }
        // TODO: Fetch referral progress and referral URL as before
        $this->view('compatibility/index', [
            'title' => 'Cosmic Connection - Compatibility Report',
            'hasActiveSubscription' => $hasActiveSubscription,
        ]);
    }
    
    /**
     * Generate a compatibility report between two birth dates
     */
    public function generate()
    {
        // Require authentication and POST request
        if (!auth_check()) {
            $this->jsonResponse(['error' => 'Authentication required'], 401);
            return;
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('/compatibility');
            return;
        }
        
        try {
            // Get input data
            $person1_name = sanitize_input($_POST['person1_name'] ?? '');
            $person1_birth_date = sanitize_input($_POST['person1_birth_date'] ?? '');
            $person2_name = sanitize_input($_POST['person2_name'] ?? '');
            $person2_birth_date = sanitize_input($_POST['person2_birth_date'] ?? '');
            
            // Validate input
            if (empty($person1_name) || empty($person1_birth_date) || empty($person2_name) || empty($person2_birth_date)) {
                $this->jsonResponse(['error' => 'All fields are required'], 400);
                return;
            }
            
            // Validate dates
            if (!$this->isValidDate($person1_birth_date) || !$this->isValidDate($person2_birth_date)) {
                $this->jsonResponse(['error' => 'Invalid birth date format'], 400);
                return;
            }
            
            // Check if user has enough credits (costs 2 credits)
            $user = auth_user();
            if ($user->credits < 2) {
                $this->jsonResponse([
                    'error' => 'Insufficient credits. You need 2 credits to generate a compatibility report.',
                    'credits_needed' => 2,
                    'current_credits' => $user->credits
                ], 402);
                return;
            }
            
            // Deduct credits
            if (!$user->deductCredits(2, 'compatibility_report', [
                'person1_name' => $person1_name,
                'person1_birth_date' => $person1_birth_date,
                'person2_name' => $person2_name,
                'person2_birth_date' => $person2_birth_date
            ])) {
                $this->jsonResponse(['error' => 'Failed to process payment'], 500);
                return;
            }
            
            // Generate compatibility report
            $compatibilityData = $this->generateCompatibilityData($person1_name, $person1_birth_date, $person2_name, $person2_birth_date);
            
            // Return the compatibility report data
            $this->jsonResponse([
                'success' => true,
                'compatibility' => $compatibilityData,
                'remaining_credits' => $user->credits - 2
            ]);
            
        } catch (Exception $e) {
            error_log('Error generating compatibility report: ' . $e->getMessage());
            $this->jsonResponse(['error' => 'Failed to generate compatibility report. Please try again.'], 500);
        }
    }
    
    /**
     * Generate compatibility data between two people
     */
    private function generateCompatibilityData($person1_name, $person1_birth_date, $person2_name, $person2_birth_date)
    {
        // Parse birth dates
        $date1 = new DateTime($person1_birth_date);
        $date2 = new DateTime($person2_birth_date);
        
        // Get zodiac signs
        $zodiac1 = $this->getZodiacSign($date1->format('m'), $date1->format('d'));
        $zodiac2 = $this->getZodiacSign($date2->format('m'), $date2->format('d'));
        
        // Calculate compatibility score (1-100)
        $compatibilityScore = $this->calculateCompatibilityScore($zodiac1, $zodiac2, $date1, $date2);
        
        // Generate AI-powered compatibility analysis
        $analysis = $this->generateCompatibilityAnalysis($person1_name, $zodiac1, $person2_name, $zodiac2, $compatibilityScore);
        
        // Get element compatibility
        $elementCompatibility = $this->getElementCompatibility($zodiac1, $zodiac2);
        
        return [
            'person1' => [
                'name' => $person1_name,
                'birth_date' => $person1_birth_date,
                'zodiac_sign' => $zodiac1,
                'element' => $this->getZodiacElement($zodiac1)
            ],
            'person2' => [
                'name' => $person2_name,
                'birth_date' => $person2_birth_date,
                'zodiac_sign' => $zodiac2,
                'element' => $this->getZodiacElement($zodiac2)
            ],
            'compatibility_score' => $compatibilityScore,
            'compatibility_level' => $this->getCompatibilityLevel($compatibilityScore),
            'analysis' => $analysis,
            'element_compatibility' => $elementCompatibility,
            'strengths' => $this->getCompatibilityStrengths($zodiac1, $zodiac2),
            'challenges' => $this->getCompatibilityChallenges($zodiac1, $zodiac2),
            'advice' => $this->getCompatibilityAdvice($zodiac1, $zodiac2, $compatibilityScore)
        ];
    }
    
    /**
     * Calculate compatibility score based on zodiac signs and birth dates
     */
    private function calculateCompatibilityScore($zodiac1, $zodiac2, $date1, $date2)
    {
        $baseScore = 50; // Start with neutral compatibility
        
        // Zodiac compatibility matrix
        $compatibilityMatrix = [
            'Aries' => ['Leo' => 25, 'Sagittarius' => 25, 'Gemini' => 15, 'Aquarius' => 15, 'Libra' => -10, 'Cancer' => -15, 'Capricorn' => -15],
            'Taurus' => ['Virgo' => 25, 'Capricorn' => 25, 'Cancer' => 15, 'Pisces' => 15, 'Scorpio' => -10, 'Leo' => -15, 'Aquarius' => -15],
            'Gemini' => ['Libra' => 25, 'Aquarius' => 25, 'Aries' => 15, 'Leo' => 15, 'Sagittarius' => -10, 'Virgo' => -15, 'Pisces' => -15],
            'Cancer' => ['Scorpio' => 25, 'Pisces' => 25, 'Taurus' => 15, 'Virgo' => 15, 'Capricorn' => -10, 'Libra' => -15, 'Aries' => -15],
            'Leo' => ['Aries' => 25, 'Sagittarius' => 25, 'Gemini' => 15, 'Libra' => 15, 'Aquarius' => -10, 'Taurus' => -15, 'Scorpio' => -15],
            'Virgo' => ['Taurus' => 25, 'Capricorn' => 25, 'Cancer' => 15, 'Scorpio' => 15, 'Pisces' => -10, 'Gemini' => -15, 'Sagittarius' => -15],
            'Libra' => ['Gemini' => 25, 'Aquarius' => 25, 'Leo' => 15, 'Sagittarius' => 15, 'Aries' => -10, 'Cancer' => -15, 'Capricorn' => -15],
            'Scorpio' => ['Cancer' => 25, 'Pisces' => 25, 'Virgo' => 15, 'Capricorn' => 15, 'Taurus' => -10, 'Leo' => -15, 'Aquarius' => -15],
            'Sagittarius' => ['Aries' => 25, 'Leo' => 25, 'Libra' => 15, 'Aquarius' => 15, 'Gemini' => -10, 'Virgo' => -15, 'Pisces' => -15],
            'Capricorn' => ['Taurus' => 25, 'Virgo' => 25, 'Scorpio' => 15, 'Pisces' => 15, 'Cancer' => -10, 'Aries' => -15, 'Libra' => -15],
            'Aquarius' => ['Gemini' => 25, 'Libra' => 25, 'Aries' => 15, 'Sagittarius' => 15, 'Leo' => -10, 'Taurus' => -15, 'Scorpio' => -15],
            'Pisces' => ['Cancer' => 25, 'Scorpio' => 25, 'Taurus' => 15, 'Capricorn' => 15, 'Virgo' => -10, 'Gemini' => -15, 'Sagittarius' => -15]
        ];
        
        // Add zodiac compatibility bonus/penalty
        if (isset($compatibilityMatrix[$zodiac1][$zodiac2])) {
            $baseScore += $compatibilityMatrix[$zodiac1][$zodiac2];
        }
        
        // Add birth date proximity bonus (people born closer together often have better compatibility)
        $daysDifference = abs($date1->diff($date2)->days % 365);
        if ($daysDifference < 30) {
            $baseScore += 10;
        } elseif ($daysDifference > 300) {
            $baseScore += 5;
        }
        
        // Add some randomness for uniqueness (±10 points)
        $randomFactor = (crc32($zodiac1 . $zodiac2) % 21) - 10;
        $baseScore += $randomFactor;
        
        // Ensure score is between 1 and 100
        return max(1, min(100, $baseScore));
    }
    
    /**
     * Generate AI-powered compatibility analysis
     */
    private function generateCompatibilityAnalysis($person1_name, $zodiac1, $person2_name, $zodiac2, $score)
    {
        $templates = [
            'high' => [
                "The cosmic connection between {$person1_name} ({$zodiac1}) and {$person2_name} ({$zodiac2}) is truly remarkable. Your energies complement each other beautifully, creating a harmonious and dynamic partnership.",
                "The stars have aligned favorably for {$person1_name} and {$person2_name}. Your {$zodiac1}-{$zodiac2} combination creates a powerful synergy that can overcome any challenge.",
                "This is a match written in the stars! {$person1_name}'s {$zodiac1} nature perfectly balances {$person2_name}'s {$zodiac2} energy, creating a relationship full of growth and understanding."
            ],
            'medium' => [
                "The connection between {$person1_name} ({$zodiac1}) and {$person2_name} ({$zodiac2}) shows great potential. With understanding and effort, this partnership can flourish beautifully.",
                "Your cosmic compatibility reveals a balanced relationship. {$person1_name} and {$person2_name} bring different strengths that can create a well-rounded partnership.",
                "The universe sees promise in this {$zodiac1}-{$zodiac2} pairing. {$person1_name} and {$person2_name} have the foundation for a meaningful connection."
            ],
            'low' => [
                "The cosmic energies between {$person1_name} ({$zodiac1}) and {$person2_name} ({$zodiac2}) present interesting challenges. With patience and understanding, you can learn much from each other.",
                "This {$zodiac1}-{$zodiac2} combination brings together very different energies. {$person1_name} and {$person2_name} will need to work on communication and compromise.",
                "The stars suggest that {$person1_name} and {$person2_name} have different approaches to life. This can be a source of growth if both are willing to embrace the differences."
            ]
        ];
        
        $level = $score >= 70 ? 'high' : ($score >= 40 ? 'medium' : 'low');
        $templateIndex = crc32($person1_name . $person2_name) % count($templates[$level]);
        
        return $templates[$level][$templateIndex];
    }
    
    /**
     * Get zodiac sign from month and day
     */
    private function getZodiacSign($month, $day)
    {
        $month = (int)$month;
        $day = (int)$day;
        
        if (($month == 3 && $day >= 21) || ($month == 4 && $day <= 19)) return 'Aries';
        if (($month == 4 && $day >= 20) || ($month == 5 && $day <= 20)) return 'Taurus';
        if (($month == 5 && $day >= 21) || ($month == 6 && $day <= 20)) return 'Gemini';
        if (($month == 6 && $day >= 21) || ($month == 7 && $day <= 22)) return 'Cancer';
        if (($month == 7 && $day >= 23) || ($month == 8 && $day <= 22)) return 'Leo';
        if (($month == 8 && $day >= 23) || ($month == 9 && $day <= 22)) return 'Virgo';
        if (($month == 9 && $day >= 23) || ($month == 10 && $day <= 22)) return 'Libra';
        if (($month == 10 && $day >= 23) || ($month == 11 && $day <= 21)) return 'Scorpio';
        if (($month == 11 && $day >= 22) || ($month == 12 && $day <= 21)) return 'Sagittarius';
        if (($month == 12 && $day >= 22) || ($month == 1 && $day <= 19)) return 'Capricorn';
        if (($month == 1 && $day >= 20) || ($month == 2 && $day <= 18)) return 'Aquarius';
        return 'Pisces';
    }
    
    /**
     * Get zodiac element
     */
    private function getZodiacElement($zodiac)
    {
        $elements = [
            'Aries' => 'Fire', 'Leo' => 'Fire', 'Sagittarius' => 'Fire',
            'Taurus' => 'Earth', 'Virgo' => 'Earth', 'Capricorn' => 'Earth',
            'Gemini' => 'Air', 'Libra' => 'Air', 'Aquarius' => 'Air',
            'Cancer' => 'Water', 'Scorpio' => 'Water', 'Pisces' => 'Water'
        ];
        
        return $elements[$zodiac] ?? 'Unknown';
    }
    
    /**
     * Get compatibility level description
     */
    private function getCompatibilityLevel($score)
    {
        if ($score >= 80) return 'Exceptional';
        if ($score >= 70) return 'Excellent';
        if ($score >= 60) return 'Very Good';
        if ($score >= 50) return 'Good';
        if ($score >= 40) return 'Fair';
        if ($score >= 30) return 'Challenging';
        return 'Difficult';
    }
    
    /**
     * Get element compatibility description
     */
    private function getElementCompatibility($zodiac1, $zodiac2)
    {
        $element1 = $this->getZodiacElement($zodiac1);
        $element2 = $this->getZodiacElement($zodiac2);
        
        $compatibility = [
            'Fire-Fire' => 'Passionate and energetic, but may burn too bright',
            'Fire-Earth' => 'Fire can warm Earth, but may also scorch it',
            'Fire-Air' => 'Air feeds Fire, creating a dynamic and exciting combination',
            'Fire-Water' => 'Opposite elements that can either steam or extinguish',
            'Earth-Earth' => 'Stable and grounded, building something lasting together',
            'Earth-Air' => 'Different approaches, but can create beautiful gardens',
            'Earth-Water' => 'Nurturing combination that helps things grow',
            'Air-Air' => 'Mental connection and communication flow freely',
            'Air-Water' => 'Air can create waves, bringing movement to still waters',
            'Water-Water' => 'Deep emotional understanding and intuitive connection'
        ];
        
        $key1 = $element1 . '-' . $element2;
        $key2 = $element2 . '-' . $element1;
        
        return $compatibility[$key1] ?? $compatibility[$key2] ?? 'Unique elemental combination';
    }
    
    /**
     * Get compatibility strengths
     */
    private function getCompatibilityStrengths($zodiac1, $zodiac2)
    {
        // This would be expanded with detailed compatibility data
        return [
            'Shared values and life goals',
            'Complementary personality traits',
            'Strong communication potential',
            'Mutual respect and understanding'
        ];
    }
    
    /**
     * Get compatibility challenges
     */
    private function getCompatibilityChallenges($zodiac1, $zodiac2)
    {
        // This would be expanded with detailed compatibility data
        return [
            'Different communication styles',
            'Varying approaches to conflict resolution',
            'Different social needs and preferences'
        ];
    }
    
    /**
     * Get compatibility advice
     */
    private function getCompatibilityAdvice($zodiac1, $zodiac2, $score)
    {
        if ($score >= 70) {
            return 'Focus on maintaining open communication and celebrating your natural harmony. Your connection is strong, so nurture it with appreciation and shared experiences.';
        } elseif ($score >= 40) {
            return 'Work on understanding each other\'s perspectives and finding common ground. Your differences can be strengths if you approach them with patience and curiosity.';
        } else {
            return 'This relationship will require extra effort and understanding. Focus on clear communication, compromise, and appreciating what each person brings to the partnership.';
        }
    }
    
    /**
     * Process a referral conversion when someone uses a referral link
     */
    private function processReferralConversion($referralId)
    {
        try {
            $referral = ReferralModel::findByToken($referralId);
            if ($referral && $referral->type === 'compatibility') {
                ReferralConversionModel::create([
                    'referral_id' => $referral->id,
                    'referred_user_id' => auth_user()->id,
                    'type' => 'compatibility',
                    'converted_at' => date('Y-m-d H:i:s')
                ]);
            }
        } catch (Exception $e) {
            error_log('Error processing referral conversion: ' . $e->getMessage());
        }
    }
    
    /**
     * Generate premium cosmic analysis (unlocked after 3 referrals)
     */
    private function generatePremiumAnalysis($person1_name, $zodiac1, $person2_name, $zodiac2)
    {
        // Advanced compatibility insights
        return [
            'karmic_connection' => $this->getKarmicConnection($zodiac1, $zodiac2),
            'soulmate_potential' => $this->getSoulmatePotential($zodiac1, $zodiac2),
            'long_term_compatibility' => $this->getLongTermCompatibility($zodiac1, $zodiac2)
        ];
    }
    
    /**
     * Validate date format
     */
    private function isValidDate($date)
    {
        $d = DateTime::createFromFormat('Y-m-d', $date);
        return $d && $d->format('Y-m-d') === $date;
    }
}