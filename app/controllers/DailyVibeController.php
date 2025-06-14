<?php
/**
 * Daily Vibe Controller
 * 
 * Handles the daily cosmic vibe check-in feature
 */

// Load required files
// Removed: Using autoloading and new Core\Controller\Controller architecture
// Removed: Using autoloading for models
// - DailyVibe model
// - User model

use App\Core\Request;
use App\Core\Response;
use App\Services\DailyVibeService;
use App\Services\UserService;
use Psr\Log\LoggerInterface;

class DailyVibeController extends \App\Core\Controller\Controller {
    /**
     * @var string Default layout file
     */
    protected $layout = 'layouts/main';
    
    /**
     * @var DailyVibeService
     */
    protected $dailyVibeService;
    
    /**
     * @var UserService
     */
    protected $userService;
    
    /**
     * Constructor
     */
    protected $logger;

    public function __construct($app, LoggerInterface $logger)
    {
        parent::__construct($app);
        $this->dailyVibeService = $this->app->make('DailyVibeService');
        $this->userService = $this->app->make('UserService');
        $this->logger = $logger;
    }
    
    /**
     * Show today's daily vibe or form to generate it
     */
    public function index(Request $request): Response {
        // Require authentication
        $userId = $request->getSession('user_id');
        if (!$userId) {
            return $this->redirect('/login?redirect=/daily-vibe');
        }
        // Get today's vibe if it exists
        $todaysVibe = $this->dailyVibeService->getTodaysVibe($userId);
        $vibeHistory = $this->dailyVibeService->getVibeHistory($userId, 7);
        $streakCount = $this->dailyVibeService->getStreakCount($userId);
        $data = [
            'pageTitle' => 'Your Daily Cosmic Vibe',
            'todaysVibe' => $todaysVibe,
            'vibeHistory' => $vibeHistory,
            'user' => (object)['id' => $userId],
            'streakCount' => $streakCount
        ];
        
        return $this->view('daily-vibe/index', $data);
    }
    
    /**
     * Generate a new daily vibe for the user
     */
    public function generate(Request $request): Response {
        // Require authentication and POST request
        $userId = $request->getSession('user_id');
        if (!$userId || !$request->isPost()) {
            return $this->jsonResponse(['error' => 'Unauthorized'], 401);
        }
        
        // Validate CSRF token
        $csrfToken = $request->input('csrf_token');
        if (!$csrfToken || $csrfToken !== $request->getSession('csrf_token')) {
            return $this->jsonResponse(['error' => 'Invalid request'], 400);
        }
        // Check if already generated today
        $existingVibe = $this->dailyVibeService->getTodaysVibe($userId);
        if ($existingVibe) {
            return $this->jsonResponse([
                'success' => true,
                'vibe' => $existingVibe,
                'message' => 'You\'ve already checked in today!',
                'isNew' => false
            ]);
        }
        
        try {
            // Generate a new vibe
            $vibeText = $this->generateVibeText($userId);
            
            // Save the vibe
            $saved = $this->dailyVibeService->saveVibe($userId, $vibeText);
            
            if ($saved) {
                $vibe = $this->dailyVibeService->getTodaysVibe($userId);
                return $this->jsonResponse([
                    'success' => true,
                    'vibe' => $vibe,
                    'message' => 'Your daily cosmic vibe is ready!',
                    'isNew' => true
                ]);
            } else {
                throw new Exception('Failed to save daily vibe');
        }
    } catch (Exception $e) {
        $this->logger->error('Error generating daily vibe: ' . $e->getMessage());
            return $this->jsonResponse(['error' => 'Failed to generate your daily vibe. Please try again.'], 500);
        }
    }
    
    /**
     * Generate the daily vibe text based on user's profile and current astrological conditions
     */
    private function generateVibeText($userId) {
        // Get user's birth date for personalization
        $user = $this->userService->getUserById($userId);
        $birthDate = $user->birth_date ?? null;
        
        // Get current moon phase and astrological data
        $moonPhase = $this->getCurrentMoonPhase();
        $currentSign = $this->getCurrentZodiacSign();
        
        // Vibe templates based on different factors
        $templates = [
            // General vibes
            "The cosmos whispers that today is a day for {action}. {emoji} {zodiac} energy is strong, so {advice}.",
            "Your cosmic forecast suggests {outlook}. The {moon_phase} moon illuminates {focus_area}.",
            "The stars align to bring you {energy} energy today. Focus on {focus_area} and watch for {opportunity}.",
            "{zodiac} season is upon us, and it's bringing {energy} your way. {advice} {emoji}",
            "Today's celestial configuration highlights {focus_area}. The {moon_phase} moon suggests {moon_advice}.",
            
            // More specific based on moon phase
            "With the moon in its {moon_phase} phase, it's an ideal time to {action}. {zodiac} energy supports {focus_area}.",
            "The {moon_phase} moon brings {energy} energy. {advice} {emoji}",
            
            // More personalized based on user's sign (if we have birth date)
            function() use ($birthDate) {
                if (!$birthDate) return null;
                $zodiac = $this->getZodiacSign(new DateTime($birthDate));
                return "Hey {user_zodiac}, today's cosmic weather is perfect for {action}. The stars suggest {advice} {emoji}";
            },
            
            // More personalized based on day of week
            function() {
                $day = date('l');
                return "Happy {$day}! The stars say it's a great day for {action}. {zodiac} energy is strong, so {advice} {emoji}";
            }
        ];
        
        // Select a random template
        $template = $templates[array_rand($templates)];
        if (is_callable($template)) {
            $template = $template();
        }
        
        // Fill in the placeholders
        $replacements = [
            '{action}' => $this->getRandomAction(),
            '{advice}' => $this->getRandomAdvice(),
            '{energy}' => $this->getRandomEnergy(),
            '{focus_area}' => $this->getRandomFocusArea(),
            '{opportunity}' => $this->getRandomOpportunity(),
            '{outlook}' => $this->getRandomOutlook(),
            '{moon_phase}' => $moonPhase,
            '{zodiac}' => $currentSign,
            '{emoji}' => $this->getRandomEmoji(),
            '{user_zodiac}' => $birthDate ? $this->getZodiacSign(new DateTime($birthDate)) : 'Stargazer'
        ];
        
        // Additional replacements for moon phase specific advice
        $moonAdvice = [
            'new' => 'setting new intentions and planting seeds for the future',
            'waxing crescent' => 'taking initial steps toward your goals',
            'first quarter' => 'addressing challenges and making adjustments',
            'waxing gibbous' => 'refining and perfecting your plans',
            'full' => 'releasing what no longer serves you',
            'waning gibbous' => 'expressing gratitude and sharing your abundance',
            'last quarter' => 'letting go of what no longer serves you',
            'waning crescent' => 'resting and preparing for the new cycle'
        ];
        
        $replacements['{moon_advice}'] = $moonAdvice[strtolower(str_replace(' ', '_', $moonPhase))] ?? 'staying open to cosmic guidance';
        
        // Replace placeholders in the template
        $vibeText = str_replace(
            array_keys($replacements),
            array_values($replacements),
            $template
        );
        
        // Capitalize first letter and ensure proper punctuation
        $vibeText = ucfirst(trim($vibeText));
        if (!in_array(substr($vibeText, -1), ['.', '!', '?'])) {
            $vibeText .= '.';
        }
        
        return $vibeText;
    }
    
    /**
     * Helper methods for vibe generation
     */
    private function getCurrentMoonPhase() {
        $phases = [
            'New Moon', 'Waxing Crescent', 'First Quarter', 'Waxing Gibbous',
            'Full Moon', 'Waning Gibbous', 'Last Quarter', 'Waning Crescent'
        ];
        
        // Simple approximation - in a real app, you'd calculate the actual moon phase
        $dayOfCycle = (int)date('j') % 29.53; // Approximate lunar cycle
        $phaseIndex = (int)($dayOfCycle / (29.53 / 8)) % 8;
        
        return $phases[$phaseIndex] ?? 'New Moon';
    }
    
    private function getCurrentZodiacSign() {
        $signs = [
            ['name' => 'Aries', 'start' => '03-21', 'end' => '04-19'],
            ['name' => 'Taurus', 'start' => '04-20', 'end' => '05-20'],
            ['name' => 'Gemini', 'start' => '05-21', 'end' => '06-20'],
            ['name' => 'Cancer', 'start' => '06-21', 'end' => '07-22'],
            ['name' => 'Leo', 'start' => '07-23', 'end' => '08-22'],
            ['name' => 'Virgo', 'start' => '08-23', 'end' => '09-22'],
            ['name' => 'Libra', 'start' => '09-23', 'end' => '10-22'],
            ['name' => 'Scorpio', 'start' => '10-23', 'end' => '11-21'],
            ['name' => 'Sagittarius', 'start' => '11-22', 'end' => '12-21'],
            ['name' => 'Capricorn', 'start' => '12-22', 'end' => '01-19'],
            ['name' => 'Aquarius', 'start' => '01-20', 'end' => '02-18'],
            ['name' => 'Pisces', 'start' => '02-19', 'end' => '03-20']
        ];
        
        $today = date('m-d');
        
        foreach ($signs as $sign) {
            if ($today >= $sign['start'] && $today <= $sign['end']) {
                return $sign['name'];
            }
        }
        
        return 'Aries'; // Default
    }
    
    private function getZodiacSign($date) {
        $month = (int)$date->format('n');
        $day = (int)$date->format('j');
        
        $signs = [
            ['name' => 'Aries', 'start' => [3, 21], 'end' => [4, 19]],
            ['name' => 'Taurus', 'start' => [4, 20], 'end' => [5, 20]],
            ['name' => 'Gemini', 'start' => [5, 21], 'end' => [6, 20]],
            ['name' => 'Cancer', 'start' => [6, 21], 'end' => [7, 22]],
            ['name' => 'Leo', 'start' => [7, 23], 'end' => [8, 22]],
            ['name' => 'Virgo', 'start' => [8, 23], 'end' => [9, 22]],
            ['name' => 'Libra', 'start' => [9, 23], 'end' => [10, 22]],
            ['name' => 'Scorpio', 'start' => [10, 23], 'end' => [11, 21]],
            ['name' => 'Sagittarius', 'start' => [11, 22], 'end' => [12, 21]],
            ['name' => 'Capricorn', 'start' => [12, 22], 'end' => [12, 31]],
            ['name' => 'Capricorn', 'start' => [1, 1], 'end' => [1, 19]],
            ['name' => 'Aquarius', 'start' => [1, 20], 'end' => [2, 18]],
            ['name' => 'Pisces', 'start' => [2, 19], 'end' => [3, 20]]
        ];
        
        foreach ($signs as $sign) {
            $startMonth = $sign['start'][0];
            $startDay = $sign['start'][1];
            $endMonth = $sign['end'][0];
            $endDay = $sign['end'][1];
            
            if (($month === $startMonth && $day >= $startDay) || 
                ($month === $endMonth && $day <= $endDay)) {
                return $sign['name'];
            }
        }
        
        return 'Aries'; // Default
    }
    
    private function getRandomAction() {
        $actions = [
            'embracing change', 'trying something new', 'trusting your intuition',
            'expressing your creativity', 'seeking balance', 'practicing gratitude',
            'setting intentions', 'releasing what no longer serves you', 'nurturing relationships',
            'prioritizing self-care', 'taking inspired action', 'listening to your inner voice'
        ];
        return $actions[array_rand($actions)];
    }
    
    private function getRandomAdvice() {
        $advice = [
            'trust the process', 'stay open to unexpected opportunities', 'listen to your intuition',
            'be gentle with yourself', 'embrace the unknown', 'speak your truth',
            'set healthy boundaries', 'follow your curiosity', 'honor your needs',
            'celebrate small wins', 'let go of perfectionism', 'trust your journey'
        ];
        return $advice[array_rand($advice)];
    }
    
    private function getRandomEnergy() {
        $energies = [
            'dynamic', 'nurturing', 'creative', 'transformative', 'harmonious',
            'intuitive', 'grounded', 'expansive', 'mystical', 'playful'
        ];
        return $energies[array_rand($energies)];
    }
    
    private function getRandomFocusArea() {
        $areas = [
            'personal growth', 'relationships', 'career goals', 'creative projects',
            'spiritual development', 'health and wellness', 'financial abundance',
            'self-expression', 'home and family', 'community connections'
        ];
        return $areas[array_rand($areas)];
    }
    
    private function getRandomOpportunity() {
        $opportunities = [
            'unexpected connections', 'creative inspiration', 'healing moments',
            'new perspectives', 'meaningful conversations', 'synchronicities',
            'breakthroughs', 'moments of clarity', 'chances to grow'
        ];
        return $opportunities[array_rand($opportunities)];
    }
    
    private function getRandomOutlook() {
        $outlooks = [
            'a day of new beginnings', 'a time for reflection', 'a period of growth',
            'a moment to pause and realign', 'an opportunity for transformation',
            'a chance to connect with your intuition', 'a day to embrace change',
            'a time to trust the journey', 'an opportunity for self-discovery'
        ];
        return $outlooks[array_rand($outlooks)];
    }
    
    private function getRandomEmoji() {
        $emojis = ['✨', '🌙', '⭐', '🌟', '💫', '🔮', '🌌', '🌠', '☄️', '🌕', '🌖', '🌗', '🌘', '🌑', '🌒', '🌓', '🌔'];
        return $emojis[array_rand($emojis)];
    }
    
    /**
     * Get user's vibe history
     */
    public function history(Request $request): Response {
        // Require authentication
        $userId = $request->getSession('user_id');
        if (!$userId) {
            return $this->redirect('/login?redirect=/daily-vibe/history');
        }
        
        // Get vibe history (last 30 days)
        $vibeHistory = $this->dailyVibeService->getVibeHistory($userId, 30);
        
        $data = [
            'pageTitle' => 'Your Vibe History',
            'vibeHistory' => $vibeHistory
        ];
        
        return $this->view('daily-vibe/history', $data);
    }
}
