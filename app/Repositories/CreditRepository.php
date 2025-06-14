<?php

namespace App\Repositories;

use App\Core\Repository\Repository;
use App\Models\Credit;
use App\Models\CreditPack;

/**
 * Credit Repository for handling credit data operations
 */
class CreditRepository extends Repository
{
    /**
     * @var string The model class
     */
    protected $model = Credit::class;
    
    // The $table property is inherited or derived from the model.
    
    /**
     * Find credits by user ID
     * 
     * @param int $userId The user ID
     * @return array
     */
    /**
     * Find credits by user ID.
     *
     * @param int $userId The user ID
     * @return Credit[]
     */
    public function findByUserId(int $userId): array
    {
        // Assuming findBy is a method in the base Repository that uses newQuery()
        // and maps results to model instances.
        return parent::findBy(['user_id' => $userId]);
    }
    
    /**
     * Get user's credit balance
     * 
     * @param int $userId The user ID
     * @return int
     */
    /**
     * Get user's credit balance.
     *
     * @param int $userId The user ID
     * @return int
     */
    public function getUserBalance(int $userId): int
    {
        $credits = $this->newQuery()
            ->where('user_id', $userId)
            ->sum('amount');
            
        return (int) $credits;
    }
    
    /**
     * Add credits to user
     * 
     * @param int $userId The user ID
     * @param int $amount Credit amount
     * @param string $type Credit type (purchase, bonus, refund, etc.)
     * @param string $description Optional description
     * @return Credit|bool
     */
    /**
     * Add credits to user.
     *
     * @param int $userId The user ID
     * @param int $amount Credit amount
     * @param string $type Credit type (purchase, bonus, refund, etc.)
     * @param string|null $description Optional description
     * @return Credit|false Returns the created Credit model instance or false on failure.
     */
    public function addCredits(int $userId, int $amount, string $type = 'purchase', ?string $description = null)
    {
        // The base 'create' method should handle timestamps if the model is configured for it.
        return parent::create([
            'user_id' => $userId,
            'amount' => abs($amount), // Ensure positive amount
            'type' => $type,
            'description' => $description,
        ]);
    }
    
    /**
     * Deduct credits from user
     * 
     * @param int $userId The user ID
     * @param int $amount Credit amount to deduct
     * @param string $type Credit type (usage, refund, etc.)
     * @param string $description Optional description
     * @return Credit|bool
     */
    /**
     * Deduct credits from user.
     *
     * @param int $userId The user ID
     * @param int $amount Credit amount to deduct
     * @param string $type Credit type (usage, refund, etc.)
     * @param string|null $description Optional description
     * @return Credit|false Returns the created Credit model instance or false on failure (e.g. insufficient balance).
     */
    public function deductCredits(int $userId, int $amount, string $type = 'usage', ?string $description = null)
    {
        // Check if user has enough credits
        $balance = $this->getUserBalance($userId);
        if ($balance < abs($amount)) { // Ensure we compare with positive amount
            return false;
        }
        
        // The base 'create' method should handle timestamps if the model is configured for it.
        return parent::create([
            'user_id' => $userId,
            'amount' => -abs($amount), // Ensure negative amount
            'type' => $type,
            'description' => $description,
        ]);
    }
    
    /**
     * Check if user has enough credits
     * 
     * @param int $userId The user ID
     * @param int $amount Required amount
     * @return bool
     */
    /**
     * Check if user has enough credits.
     *
     * @param int $userId The user ID
     * @param int $amount Required amount
     * @return bool
     */
    public function hasEnoughCredits(int $userId, int $amount): bool
    {
        $balance = $this->getUserBalance($userId);
        return $balance >= abs($amount); // Ensure we compare with positive amount
    }
    
    /**
     * Get user's credit history
     * 
     * @param int $userId The user ID
     * @param int $limit Optional limit
     * @return array
     */
    /**
     * Get user's credit history.
     *
     * @param int $userId The user ID
     * @param int $limit Optional limit
     * @return Credit[]
     */
    public function getUserHistory(int $userId, int $limit = 50): array
    {
        $results = $this->newQuery()
            ->where('user_id', $userId)
            ->orderBy('created_at', 'DESC')
            ->limit($limit)
            ->get();
        
        return array_map(fn($data) => $this->mapResultToModel($data), $results->all());
    }
    
    /**
     * Get credit transactions by type
     * 
     * @param string $type Credit type
     * @param int $limit Optional limit
     * @return array
     */
    /**
     * Get credit transactions by type.
     *
     * @param string $type Credit type
     * @param int|null $limit Optional limit
     * @return Credit[]
     */
    public function getByType(string $type, ?int $limit = null): array
    {
        $query = $this->newQuery()->where('type', $type);
        
        if ($limit) {
            $query->limit($limit);
        }
        
        $results = $query->orderBy('created_at', 'DESC')->get();
        return array_map(fn($data) => $this->mapResultToModel($data), $results->all());
    }
    
    /**
     * Get credit statistics
     * 
     * @return array
     */
    /**
     * Get credit statistics.
     *
     * @return array
     */
    public function getStatistics(): array
    {
        $totalCreditsIssued = $this->newQuery()->where('amount', '>', 0)->sum('amount');
        $totalCreditsUsed = abs($this->newQuery()->where('amount', '<', 0)->sum('amount'));
        $totalTransactions = $this->newQuery()->count();
        
        // For distinct user count, it's better to use ->distinct()->count('user_id') if supported
        // or a subquery/raw query for better performance if the DB engine requires it.
        // Assuming the current builder handles distinct count on a column correctly.
        $uniqueUsers = $this->newQuery()->distinct()->count('user_id'); 
        
        $typeStatsResults = $this->newQuery()
            ->select('type')
            ->selectRaw('COUNT(*) as count')
            ->selectRaw('SUM(amount) as total_amount')
            ->groupBy('type')
            ->get();
            
        // Convert collection of stdClass/array to simple array if needed by consumers
        $typeStats = $typeStatsResults->all(); 

        return [
            'total_issued' => (int) $totalCreditsIssued,
            'total_used' => (int) $totalCreditsUsed,
            'total_transactions' => $totalTransactions,
            'unique_users' => $uniqueUsers,
            'types' => $typeStats // This will be an array of objects/arrays
        ];
    }
    
    /**
     * Get top credit users
     * 
     * @param int $limit Number of users to retrieve
     * @return array
     */
    /**
     * Get top credit users (users with the highest net credit balance).
     *
     * @param int $limit Number of users to retrieve
     * @return array Array of objects/arrays with user_id and total_credits.
     */
    public function getTopUsers(int $limit = 10): array
    {
        // This returns raw data, not Credit model instances.
        // Mapping to a specific DTO or User model might be needed depending on usage.
        $results = $this->newQuery()
            ->select('user_id')
            ->selectRaw('SUM(amount) as total_credits')
            ->groupBy('user_id')
            ->orderBy('total_credits', 'DESC')
            ->limit($limit)
            ->get();
        return $results; // Returns array of objects/arrays with user_id and total_credits
    }
    
    /**
     * Get credit usage over time
     * 
     * @param string $period Period for statistics (day, week, month)
     * @param int $limit Number of periods to retrieve
     * @return array
     */
    public function getUsageOverTime($period = 'day', $limit = 30)
    {
        $dateFormat = match($period) {
            'week' => '%Y-%u',
            'month' => '%Y-%m',
            default => '%Y-%m-%d'
        };
        
        return $this->query
            ->selectRaw("DATE_FORMAT(created_at, '{$dateFormat}') as period")
            ->selectRaw('SUM(CASE WHEN amount > 0 THEN amount ELSE 0 END) as credits_added')
            ->selectRaw('SUM(CASE WHEN amount < 0 THEN ABS(amount) ELSE 0 END) as credits_used')
            ->selectRaw('COUNT(*) as transactions')
            ->groupBy('period')
            ->orderBy('period', 'DESC')
            ->limit($limit)
            ->get();
    }
    
    /**
     * Transfer credits between users
     * 
     * @param int $fromUserId Source user ID
     * @param int $toUserId Destination user ID
     * @param int $amount Amount to transfer
     * @param string $description Optional description
     * @return bool
     */
    public function transferCredits($fromUserId, $toUserId, $amount, $description = null)
    {
        // Check if source user has enough credits
        if (!$this->hasEnoughCredits($fromUserId, $amount)) {
            return false;
        }
        
        // Deduct from source user
        $deduction = $this->deductCredits($fromUserId, $amount, 'transfer_out', $description);
        if (!$deduction) {
            return false;
        }
        
        // Add to destination user
        $addition = $this->addCredits($toUserId, $amount, 'transfer_in', $description);
        if (!$addition) {
            // Rollback deduction if addition fails
            $this->addCredits($fromUserId, $amount, 'transfer_rollback', 'Rollback failed transfer');
            return false;
        }
        
        return true;
    }
    
    /**
     * Get credits with pagination
     * 
     * @param int $page The page number
     * @param int $perPage Items per page
     * @param int $userId Optional user filter
     * @param string $type Optional type filter
     * @return array
     */
    public function paginate($page = 1, $perPage = 10, $userId = null, $type = null)
    {
        $offset = ($page - 1) * $perPage;
        
        $query = $this->query;
        
        if ($userId) {
            $query = $query->where('user_id', $userId);
        }
        
        if ($type) {
            $query = $query->where('type', $type);
        }
        
        $items = $query
            ->orderBy('created_at', 'DESC')
            ->limit($perPage)
            ->offset($offset)
            ->get();
            
        $totalQuery = $this->query;
        if ($userId) {
            $totalQuery = $totalQuery->where('user_id', $userId);
        }
        if ($type) {
            $totalQuery = $totalQuery->where('type', $type);
        }
        $total = $totalQuery->count();
        
        return [
            'items' => $items,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => ceil($total / $perPage)
        ];
    }
}