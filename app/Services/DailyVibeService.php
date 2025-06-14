<?php

namespace App\Services;

use App\Core\Service\Service;
use App\Repositories\DailyVibeRepository;

/**
 * Daily Vibe Service for handling daily vibe business logic
 */
class DailyVibeService extends Service
{
    /**
     * @var DailyVibeRepository
     */
    protected $dailyVibeRepository;
    
    /**
     * Initialize the service
     */
    public function __construct($app)
    {
        parent::__construct($app);
        $this->dailyVibeRepository = $this->getRepository('DailyVibeRepository');
    }
    
    /**
     * Get today's vibe
     * 
     * @param string $zodiacSign Zodiac sign
     * @return array
     */
    public function getTodayVibe($zodiacSign = null)
    {
        try {
            $today = date('Y-m-d');
            
            if ($zodiacSign) {
                $vibe = $this->dailyVibeRepository->findByDateAndSign($today, $zodiacSign);
                
                if (!$vibe) {
                    // Generate vibe if it doesn't exist
                    $vibe = $this->generateDailyVibe($today, $zodiacSign);
                }
                
                return $this->success('Today\'s vibe retrieved successfully', $vibe);
            } else {
                $vibes = $this->dailyVibeRepository->findByDate($today);
                
                if (empty($vibes)) {
                    // Generate vibes for all signs if they don't exist
                    $vibes = $this->generateAllDailyVibes($today);
                }
                
                return $this->success('Today\'s vibes retrieved successfully', $vibes);
            }
            
        } catch (\Exception $e) {
            $this->log('error', 'Error retrieving today\'s vibe: ' . $e->getMessage());
            return $this->error('An error occurred while retrieving today\'s vibe');
        }
    }
    
    /**
     * Get vibe by date
     * 
     * @param string $date Date (Y-m-d format)
     * @param string $zodiacSign Zodiac sign
     * @return array
     */
    public function getVibeByDate($date, $zodiacSign = null)
    {
        try {
            if (!$this->validateDate($date)) {
                return $this->error('Invalid date format. Use Y-m-d format.');
            }
            
            if ($zodiacSign) {
                $vibe = $this->dailyVibeRepository->findByDateAndSign($date, $zodiacSign);
                
                if (!$vibe) {
                    // Generate vibe if it doesn't exist
                    $vibe = $this->generateDailyVibe($date, $zodiacSign);
                }
                
                return $this->success('Vibe retrieved successfully', $vibe);
            } else {
                $vibes = $this->dailyVibeRepository->findByDate($date);
                
                if (empty($vibes)) {
                    // Generate vibes for all signs if they don't exist
                    $vibes = $this->generateAllDailyVibes($date);
                }
                
                return $this->success('Vibes retrieved successfully', $vibes);
            }
            
        } catch (\Exception $e) {
            $this->log('error', 'Error retrieving vibe by date: ' . $e->getMessage(), ['date' => $date]);
            return $this->error('An error occurred while retrieving vibe');
        }
    }
    
    /**
     * Get vibes for a date range
     * 
     * @param string $startDate Start date (Y-m-d format)
     * @param string $endDate End date (Y-m-d format)
     * @param string $zodiacSign Zodiac sign
     * @return array
     */
    public function getVibesForDateRange($startDate, $endDate, $zodiacSign = null)
    {
        try {
            if (!$this->validateDate($startDate) || !$this->validateDate($endDate)) {
                return $this->error('Invalid date format. Use Y-m-d format.');
            }
            
            if (strtotime($endDate) < strtotime($startDate)) {
                return $this->error('End date cannot be before start date');
            }
            
            $vibes = $this->dailyVibeRepository->getVibesForDateRange($startDate, $endDate, $zodiacSign);
            return $this->success('Vibes retrieved successfully', $vibes);
            
        } catch (\Exception $e) {
            $this->log('error', 'Error retrieving vibes for date range: ' . $e->getMessage(), [
                'start_date' => $startDate,
                'end_date' => $endDate
            ]);
            return $this->error('An error occurred while retrieving vibes');
        }
    }
    
    /**
     * Get vibes by zodiac sign
     * 
     * @param string $zodiacSign Zodiac sign
     * @param int $limit Number of vibes to retrieve
     * @return array
     */
    public function getVibesByZodiacSign($zodiacSign, $limit = 10)
    {
        try {
            if (!$this->validateZodiacSign($zodiacSign)) {
                return $this->error('Invalid zodiac sign');
            }
            
            $vibes = $this->dailyVibeRepository->findByZodiacSign($zodiacSign, $limit);
            return $this->success('Vibes retrieved successfully', $vibes);
            
        } catch (\Exception $e) {
            $this->log('error', 'Error retrieving vibes by zodiac sign: ' . $e->getMessage(), ['zodiac_sign' => $zodiacSign]);
            return $this->error('An error occurred while retrieving vibes');
        }
    }
    
    /**
     * Get recent vibes
     * 
     * @param int $limit Number of vibes to retrieve
     * @return array
     */
    public function getRecentVibes($limit = 10)
    {
        try {
            $vibes = $this->dailyVibeRepository->getRecent($limit);
            return $this->success('Recent vibes retrieved successfully', $vibes);
            
        } catch (\Exception $e) {
            $this->log('error', 'Error retrieving recent vibes: ' . $e->getMessage());
            return $this->error('An error occurred while retrieving recent vibes');
        }
    }
    
    /**
     * Create or update daily vibe
     * 
     * @param array $data Daily vibe data
     * @return array
     */
    public function createOrUpdateDailyVibe($data)
    {
        try {
            // Validate required fields
            $validation = $this->validateDailyVibeData($data);
            if (!empty($validation)) {
                return $this->error('Validation failed', $validation);
            }
            
            // Check if vibe for this date and sign already exists
            $existing = $this->dailyVibeRepository->findByDateAndSign($data['date'], $data['zodiac_sign']);
            
            if ($existing) {
                // Update existing vibe
                $data['updated_at'] = date('Y-m-d H:i:s');
                $updated = $this->dailyVibeRepository->update($existing['id'], $data);
                
                if ($updated) {
                    $this->log('info', 'Daily vibe updated successfully', [
                        'date' => $data['date'],
                        'zodiac_sign' => $data['zodiac_sign']
                    ]);
                    return $this->success('Daily vibe updated successfully');
                }
                
                return $this->error('Failed to update daily vibe');
            } else {
                // Create new vibe
                $data['created_at'] = date('Y-m-d H:i:s');
                $vibe = $this->dailyVibeRepository->create($data);
                
                if ($vibe) {
                    $this->log('info', 'Daily vibe created successfully', [
                        'date' => $data['date'],
                        'zodiac_sign' => $data['zodiac_sign']
                    ]);
                    return $this->success('Daily vibe created successfully', $vibe);
                }
                
                return $this->error('Failed to create daily vibe');
            }
            
        } catch (\Exception $e) {
            $this->log('error', 'Error creating/updating daily vibe: ' . $e->getMessage());
            return $this->error('An error occurred while creating/updating daily vibe');
        }
    }
    
    /**
     * Get mood trends
     * 
     * @param string $zodiacSign Zodiac sign
     * @param int $days Number of days to analyze
     * @return array
     */
    public function getMoodTrends($zodiacSign, $days = 30)
    {
        try {
            if (!$this->validateZodiacSign($zodiacSign)) {
                return $this->error('Invalid zodiac sign');
            }
            
            $trends = $this->dailyVibeRepository->getMoodTrends($zodiacSign, $days);
            return $this->success('Mood trends retrieved successfully', $trends);
            
        } catch (\Exception $e) {
            $this->log('error', 'Error retrieving mood trends: ' . $e->getMessage(), ['zodiac_sign' => $zodiacSign]);
            return $this->error('An error occurred while retrieving mood trends');
        }
    }
    
    /**
     * Get energy trends
     * 
     * @param string $zodiacSign Zodiac sign
     * @param int $days Number of days to analyze
     * @return array
     */
    public function getEnergyTrends($zodiacSign, $days = 30)
    {
        try {
            if (!$this->validateZodiacSign($zodiacSign)) {
                return $this->error('Invalid zodiac sign');
            }
            
            $trends = $this->dailyVibeRepository->getEnergyTrends($zodiacSign, $days);
            return $this->success('Energy trends retrieved successfully', $trends);
            
        } catch (\Exception $e) {
            $this->log('error', 'Error retrieving energy trends: ' . $e->getMessage(), ['zodiac_sign' => $zodiacSign]);
            return $this->error('An error occurred while retrieving energy trends');
        }
    }
    
    /**
     * Get lucky numbers
     * 
     * @param string $date Date (Y-m-d format)
     * @param string $zodiacSign Zodiac sign
     * @return array
     */
    public function getLuckyNumbers($date, $zodiacSign)
    {
        try {
            if (!$this->validateDate($date)) {
                return $this->error('Invalid date format. Use Y-m-d format.');
            }
            
            if (!$this->validateZodiacSign($zodiacSign)) {
                return $this->error('Invalid zodiac sign');
            }
            
            $vibe = $this->dailyVibeRepository->findByDateAndSign($date, $zodiacSign);
            
            if (!$vibe) {
                // Generate vibe if it doesn't exist
                $vibe = $this->generateDailyVibe($date, $zodiacSign);
            }
            
            return $this->success('Lucky numbers retrieved successfully', [
                'lucky_numbers' => $vibe['lucky_numbers']
            ]);
            
        } catch (\Exception $e) {
            $this->log('error', 'Error retrieving lucky numbers: ' . $e->getMessage(), [
                'date' => $date,
                'zodiac_sign' => $zodiacSign
            ]);
            return $this->error('An error occurred while retrieving lucky numbers');
        }
    }
    
    /**
     * Get compatibility
     * 
     * @param string $date Date (Y-m-d format)
     * @param string $zodiacSign Zodiac sign
     * @return array
     */
    public function getCompatibility($date, $zodiacSign)
    {
        try {
            if (!$this->validateDate($date)) {
                return $this->error('Invalid date format. Use Y-m-d format.');
            }
            
            if (!$this->validateZodiacSign($zodiacSign)) {
                return $this->error('Invalid zodiac sign');
            }
            
            $vibe = $this->dailyVibeRepository->findByDateAndSign($date, $zodiacSign);
            
            if (!$vibe) {
                // Generate vibe if it doesn't exist
                $vibe = $this->generateDailyVibe($date, $zodiacSign);
            }
            
            return $this->success('Compatibility retrieved successfully', [
                'compatibility' => $vibe['compatibility']
            ]);
            
        } catch (\Exception $e) {
            $this->log('error', 'Error retrieving compatibility: ' . $e->getMessage(), [
                'date' => $date,
                'zodiac_sign' => $zodiacSign
            ]);
            return $this->error('An error occurred while retrieving compatibility');
        }
    }
    
    /**
     * Search daily vibes
     * 
     * @param string $search Search term
     * @return array
     */
    public function searchDailyVibes($search)
    {
        try {
            if (empty($search)) {
                return $this->error('Search term is required');
            }
            
            $vibes = $this->dailyVibeRepository->search($search);
            return $this->success('Search completed successfully', $vibes);
            
        } catch (\Exception $e) {
            $this->log('error', 'Error searching daily vibes: ' . $e->getMessage(), ['search' => $search]);
            return $this->error('An error occurred while searching daily vibes');
        }
    }
    
    /**
     * Get paginated daily vibes
     * 
     * @param int $page Page number
     * @param int $perPage Items per page
     * @return array
     */
    public function getPaginatedDailyVibes($page = 1, $perPage = 10)
    {
        try {
            $result = $this->dailyVibeRepository->paginate($page, $perPage);
            return $this->success('Daily vibes retrieved successfully', $result);
            
        } catch (\Exception $e) {
            $this->log('error', 'Error retrieving paginated daily vibes: ' . $e->getMessage());
            return $this->error('An error occurred while retrieving daily vibes');
        }
    }
    
    /**
     * Delete old daily vibes
     * 
     * @param int $days Number of days to keep
     * @return array
     */
    public function deleteOldDailyVibes($days = 90)
    {
        try {
            $deleted = $this->dailyVibeRepository->deleteOldVibes($days);
            
            return $this->success('Old daily vibes deleted successfully', [
                'deleted_count' => $deleted
            ]);
            
        } catch (\Exception $e) {
            $this->log('error', 'Error deleting old daily vibes: ' . $e->getMessage());
            return $this->error('An error occurred while deleting old daily vibes');
        }
    }
    
    /**
     * Generate daily vibe for a specific date and zodiac sign
     * 
     * @param string $date Date (Y-m-d format)
     * @param string $zodiacSign Zodiac sign
     * @return array
     */
    protected function generateDailyVibe($date, $zodiacSign)
    {
        // In a real application, this would use an algorithm or API to generate the vibe
        // For this example, we'll create a simple random vibe
        
        $moods = ['Happy', 'Reflective', 'Energetic', 'Calm', 'Creative', 'Focused', 'Relaxed', 'Inspired'];
        $energyLevels = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10];
        $luckyNumbers = [];
        
        // Generate 3 unique lucky numbers between 1 and 99
        while (count($luckyNumbers) < 3) {
            $num = rand(1, 99);
            if (!in_array($num, $luckyNumbers)) {
                $luckyNumbers[] = $num;
            }
        }
        
        $zodiacSigns = ['Aries', 'Taurus', 'Gemini', 'Cancer', 'Leo', 'Virgo', 'Libra', 'Scorpio', 'Sagittarius', 'Capricorn', 'Aquarius', 'Pisces'];
        $compatibleSigns = array_filter($zodiacSigns, function($sign) use ($zodiacSign) {
            return $sign !== $zodiacSign;
        });
        
        // Randomly select 2 compatible signs
        shuffle($compatibleSigns);
        $compatibility = array_slice($compatibleSigns, 0, 2);
        
        $vibeData = [
            'date' => $date,
            'zodiac_sign' => $zodiacSign,
            'mood' => $moods[array_rand($moods)],
            'energy_level' => $energyLevels[array_rand($energyLevels)],
            'description' => "Today is a good day for {$zodiacSign}. Focus on your goals and stay positive.",
            'lucky_numbers' => implode(', ', $luckyNumbers),
            'compatibility' => implode(', ', $compatibility),
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        // Save to database
        $vibe = $this->dailyVibeRepository->create($vibeData);
        
        return $vibe;
    }
    
    /**
     * Generate daily vibes for all zodiac signs for a specific date
     * 
     * @param string $date Date (Y-m-d format)
     * @return array
     */
    protected function generateAllDailyVibes($date)
    {
        $zodiacSigns = ['Aries', 'Taurus', 'Gemini', 'Cancer', 'Leo', 'Virgo', 'Libra', 'Scorpio', 'Sagittarius', 'Capricorn', 'Aquarius', 'Pisces'];
        $vibes = [];
        
        foreach ($zodiacSigns as $sign) {
            $vibes[] = $this->generateDailyVibe($date, $sign);
        }
        
        return $vibes;
    }
    
    /**
     * Validate daily vibe data
     * 
     * @param array $data Daily vibe data
     * @return array
     */
    protected function validateDailyVibeData($data)
    {
        $errors = [];
        
        if (empty($data['date'])) {
            $errors[] = 'Date is required';
        } elseif (!$this->validateDate($data['date'])) {
            $errors[] = 'Invalid date format. Use Y-m-d format.';
        }
        
        if (empty($data['zodiac_sign'])) {
            $errors[] = 'Zodiac sign is required';
        } elseif (!$this->validateZodiacSign($data['zodiac_sign'])) {
            $errors[] = 'Invalid zodiac sign';
        }
        
        if (empty($data['mood'])) {
            $errors[] = 'Mood is required';
        }
        
        if (!isset($data['energy_level'])) {
            $errors[] = 'Energy level is required';
        } elseif (!is_numeric($data['energy_level']) || $data['energy_level'] < 1 || $data['energy_level'] > 10) {
            $errors[] = 'Energy level must be a number between 1 and 10';
        }
        
        if (empty($data['description'])) {
            $errors[] = 'Description is required';
        }
        
        return $errors;
    }
    
    /**
     * Validate date format
     * 
     * @param string $date Date string
     * @return bool
     */
    protected function validateDate($date)
    {
        $d = \DateTime::createFromFormat('Y-m-d', $date);
        return $d && $d->format('Y-m-d') === $date;
    }
    
    /**
     * Validate zodiac sign
     * 
     * @param string $zodiacSign Zodiac sign
     * @return bool
     */
    protected function validateZodiacSign($zodiacSign)
    {
        $validSigns = ['Aries', 'Taurus', 'Gemini', 'Cancer', 'Leo', 'Virgo', 'Libra', 'Scorpio', 'Sagittarius', 'Capricorn', 'Aquarius', 'Pisces'];
        return in_array($zodiacSign, $validSigns);
    }
    
    /**
     * Get today's vibe for a specific user
     * 
     * @param int $userId User ID
     * @return array|null
     */
    public function getTodaysVibe($userId)
    {
        try {
            $today = date('Y-m-d');
            $vibe = $this->dailyVibeRepository->findByUserAndDate($userId, $today);
            return $vibe;
        } catch (\Exception $e) {
            $this->log('error', 'Error getting today\'s vibe for user: ' . $e->getMessage(), ['user_id' => $userId]);
            return null;
        }
    }
    
    /**
     * Get vibe history for a user
     * 
     * @param int $userId User ID
     * @param int $days Number of days to retrieve
     * @return array
     */
    public function getVibeHistory($userId, $days = 7)
    {
        try {
            return $this->dailyVibeRepository->getVibeHistoryForUser($userId, $days);
        } catch (\Exception $e) {
            $this->log('error', 'Error getting vibe history for user: ' . $e->getMessage(), ['user_id' => $userId]);
            return [];
        }
    }
    
    /**
     * Get streak count for a user
     * 
     * @param int $userId User ID
     * @return int
     */
    public function getStreakCount($userId)
    {
        try {
            return $this->dailyVibeRepository->getStreakCountForUser($userId);
        } catch (\Exception $e) {
            $this->log('error', 'Error getting streak count for user: ' . $e->getMessage(), ['user_id' => $userId]);
            return 0;
        }
    }
    
    /**
     * Save a vibe for a user
     * 
     * @param int $userId User ID
     * @param string $vibeText Vibe text
     * @return bool
     */
    public function saveVibe($userId, $vibeText)
    {
        try {
            $data = [
                'user_id' => $userId,
                'vibe_text' => $vibeText,
                'date' => date('Y-m-d'),
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            $result = $this->dailyVibeRepository->create($data);
            return $result !== false;
        } catch (\Exception $e) {
            $this->log('error', 'Error saving vibe for user: ' . $e->getMessage(), ['user_id' => $userId]);
            return false;
        }
    }
}