<?php

namespace App\Models;

use Psr\Log\LoggerInterface;

class DailyVibe extends Model {
    /**
     * The logger instance.
     *
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * Constructor.
     *
     * @param LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger)
    {
        parent::__construct();
        $this->logger = $logger;
    }
    protected $table = 'daily_vibes';
    
    /**
     * Get today's vibe for a user
     */
    public function getTodaysVibe($userId) {
        try {
            $today = date('Y-m-d');
            $result = $this->query(
                "SELECT * FROM {$this->table} WHERE user_id = :user_id AND date = :date LIMIT 1",
                ['user_id' => $userId, 'date' => $today]
            );
            
            return !empty($result) ? $result[0] : null;
        } catch (\Exception $e) {
            $this->logger->error('Error getting today\'s vibe: ' . $e->getMessage(), ['exception' => $e]);
            return null;
        }
    }
    
    /**
     * Save a new daily vibe
     */
    public function saveVibe($userId, $vibeText, $date = null) {
        try {
            $date = $date ?: date('Y-m-d');
            
            // Check if vibe already exists for this date
            $existing = $this->getTodaysVibe($userId);
            
            if ($existing) {
                // Update existing vibe
                return $this->query(
                    "UPDATE {$this->table} SET vibe_text = :vibe_text WHERE id = :id",
                    ['vibe_text' => $vibeText, 'id' => $existing->id]
                );
            } else {
                // Insert new vibe
                return $this->query(
                    "INSERT INTO {$this->table} (user_id, vibe_text, date) VALUES (:user_id, :vibe_text, :date)",
                    ['user_id' => $userId, 'vibe_text' => $vibeText, 'date' => $date]
                );
            }
        } catch (\Exception $e) {
            $this->logger->error('Error saving daily vibe: ' . $e->getMessage(), ['exception' => $e]);
            return false;
        }
    }
    
    /**
     * Get user's vibe history
     */
    public function getVibeHistory($userId, $limit = 30) {
        try {
            return $this->query(
                "SELECT * FROM {$this->table} 
                WHERE user_id = :user_id 
                ORDER BY date DESC 
                LIMIT :limit",
                ['user_id' => $userId, 'limit' => (int)$limit],
                \PDO::FETCH_OBJ
            );
        } catch (\Exception $e) {
            $this->logger->error('Error getting vibe history: ' . $e->getMessage(), ['exception' => $e]);
            return [];
        }
    }
    
    /**
     * Get user's current daily vibe streak
     */
    public function getStreakCount($userId) {
        try {
            $streak = 0;
            $date = new \DateTime();
            while (true) {
                $result = $this->query(
                    "SELECT * FROM {$this->table} WHERE user_id = :user_id AND date = :date LIMIT 1",
                    ['user_id' => $userId, 'date' => $date->format('Y-m-d')]
                );
                if (!empty($result)) {
                    $streak++;
                    $date->modify('-1 day');
                } else {
                    break;
                }
            }
            return $streak;
        } catch (\Exception $e) {
            $this->logger->error('Error calculating streak: ' . $e->getMessage(), ['exception' => $e]);
            return 0;
        }
    }
}
