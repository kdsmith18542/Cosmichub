<?php

namespace App\Repositories;

use App\Core\Repository\Repository;
use App\Models\DailyVibe;

/**
 * Daily Vibe Repository for handling daily vibe data operations
 */
class DailyVibeRepository extends Repository
{
    /**
     * @var string The model class
     */
    protected $model = DailyVibe::class;
    
    /**
     * Find daily vibe by date.
     * 
     * @param string $date The date (Y-m-d format).
     * @return DailyVibe|null The found daily vibe or null.
     */
    public function findByDate(string $date): ?DailyVibe
    {
        $result = $this->newQuery()->where('date', $date)->first();
        return $result ? $this->mapResultToModel($result) : null;
    }
    
    /**
     * Get today's daily vibe
     * 
     * @return DailyVibe|null
     */
    public function getToday(): ?DailyVibe
    {
        return $this->findByDate(date('Y-m-d'));
    }
    
    /**
     * Get daily vibe for a specific zodiac sign
     * 
     * @param string $date The date
     * @param string $zodiacSign The zodiac sign
     * @return DailyVibe|null
     */
    public function getByDateAndSign(string $date, string $zodiacSign): ?DailyVibe
    {
        $result = $this->newQuery()
            ->where('date', $date)
            ->where('zodiac_sign', $zodiacSign)
            ->first();
        return $result ? $this->mapResultToModel($result) : null;
    }
    
    /**
     * Get daily vibes for date range
     * 
     * @param string $startDate Start date
     * @param string $endDate End date
     * @return array
     */
    /**
     * Get daily vibes for date range
     * 
     * @param string $startDate Start date
     * @param string $endDate End date
     * @return DailyVibe[]
     */
    public function getByDateRange(string $startDate, string $endDate): array
    {
        $results = $this->newQuery()
            ->where('date', '>=', $startDate)
            ->where('date', '<=', $endDate)
            ->orderBy('date', 'DESC')
            ->get();
        return $this->mapResultsToModels($results);
    }
    
    /**
     * Get recent daily vibes
     * 
     * @param int $limit Number of vibes to retrieve
     * @return array
     */
    /**
     * Get recent daily vibes
     * 
     * @param int $limit Number of vibes to retrieve
     * @return DailyVibe[]
     */
    public function getRecent(int $limit = 7): array
    {
        $results = $this->newQuery()
            ->orderBy('date', 'DESC')
            ->limit($limit)
            ->get();
        return $this->mapResultsToModels($results);
    }
    
    /**
     * Get daily vibes by zodiac sign
     * 
     * @param string $zodiacSign The zodiac sign
     * @param int $limit Number of vibes to retrieve
     * @return array
     */
    /**
     * Get daily vibes by zodiac sign
     * 
     * @param string $zodiacSign The zodiac sign
     * @param int $limit Number of vibes to retrieve
     * @return DailyVibe[]
     */
    public function getByZodiacSign(string $zodiacSign, int $limit = 30): array
    {
        $results = $this->newQuery()
            ->where('zodiac_sign', $zodiacSign)
            ->orderBy('date', 'DESC')
            ->limit($limit)
            ->get();
        return $this->mapResultsToModels($results);
    }
    
    /**
     * Get all zodiac signs for a specific date
     * 
     * @param string $date The date
     * @return array
     */
    /**
     * Get all zodiac signs for a specific date
     * 
     * @param string $date The date
     * @return DailyVibe[]
     */
    public function getAllSignsForDate(string $date): array
    {
        $results = $this->newQuery()
            ->where('date', $date)
            ->orderBy('zodiac_sign')
            ->get();
        return $this->mapResultsToModels($results);
    }
    
    /**
     * Create or update daily vibe
     * 
     * @param string $date The date
     * @param string $zodiacSign The zodiac sign
     * @param array $data Vibe data
     * @return DailyVibe|bool
     */
    /**
     * Create or update daily vibe
     * 
     * @param string $date The date
     * @param string $zodiacSign The zodiac sign
     * @param array $data Vibe data (e.g., ['vibe_text' => 'Great day!', 'mood_rating' => 5]).
     *                    The `DailyVibe` model should have these attributes in its `$fillable` property.
     * @return DailyVibe|null The created or updated daily vibe, or null on failure.
     */
    public function createOrUpdate(string $date, string $zodiacSign, array $data): ?DailyVibe
    {
        $existing = $this->getByDateAndSign($date, $zodiacSign);

        if ($existing) {
            // Ensure $data does not contain primary key or immutable fields like date/zodiac_sign if they shouldn't be updated.
            $updated = parent::update($existing->getKey(), $data);
            // Re-fetch to get the model with updated attributes.
            return $updated ? $this->findByDateAndSign($date, $zodiacSign) : null;
        } else {
            $createData = array_merge($data, ['date' => $date, 'zodiac_sign' => $zodiacSign]);
            // Assuming parent::create returns the newly created model instance.
            // If DailyVibe model does not have $fillable property set, this might not work as expected for mass assignment.
            return parent::create($createData);
        }
    }
    
    /**
     * Get daily vibe statistics
     * 
     * @return array
     */
    public function getStatistics(): array
    {
        $total = $this->newQuery()->count();
        
        $distinctZodiacSignsResult = $this->newQuery()
                                       ->selectRaw('COUNT(DISTINCT zodiac_sign) as distinct_signs_count')
                                       ->first();
        $distinctZodiacSigns = $distinctZodiacSignsResult ? (int)$distinctZodiacSignsResult->distinct_signs_count : 0;

        $mostRecentVibeStdClass = $this->newQuery()->orderBy('date', 'DESC')->first();
        $mostRecentVibe = $mostRecentVibeStdClass ? $this->mapResultToModel($mostRecentVibeStdClass) : null;

        $oldestVibeStdClass = $this->newQuery()->orderBy('date', 'ASC')->first();
        $oldestVibe = $oldestVibeStdClass ? $this->mapResultToModel($oldestVibeStdClass) : null;

        return [
            'total_vibes' => $total,
            'distinct_zodiac_signs' => $distinctZodiacSigns,
            'most_recent_vibe_date' => $mostRecentVibe ? $mostRecentVibe->date : null,
            'oldest_vibe_date' => $oldestVibe ? $oldestVibe->date : null,
        ];
        // This might need adjustment based on the actual QueryBuilder capabilities.
        // Assuming it works or a workaround like `count(DB::raw('DISTINCT date'))` is available if using a global DB facade.
        // For the custom builder, it might be $this->newQuery()->distinct()->count('date'); or similar.
        // For now, assuming the custom builder handles `distinct('column_name')->count()` as counting distinct values of that column.
        $uniqueDates = $this->newQuery()->distinct('date')->count(); 
        $zodiacSigns = $this->newQuery()->distinct('zodiac_sign')->count();
        
        $moodStats = $this->newQuery()
            ->select('mood')
            ->selectRaw('COUNT(*) as count')
            ->groupBy('mood')
            ->get();
            
        $energyStats = $this->newQuery()
            ->select('energy_level')
            ->selectRaw('COUNT(*) as count')
            ->groupBy('energy_level')
            ->get();
            
        return [
            'total' => $total,
            'unique_dates' => $uniqueDates, // This might return total rows if distinct('column') isn't properly supported for count
            'zodiac_signs' => $zodiacSigns, // Same as above
            'mood_distribution' => $moodStats,
            'energy_distribution' => $energyStats
        ];
    }
    
    /**
     * Get mood trends over time
     * 
     * @param string $zodiacSign Optional zodiac sign filter
     * @param int $days Number of days to analyze
     * @return array
     */
    /**
     * Get mood trends over time
     * 
     * @param string|null $zodiacSign Optional zodiac sign filter
     * @param int $days Number of days to analyze
     * @return array
     */
    public function getMoodTrends(?string $zodiacSign = null, int $days = 30): array
    {
        $startDate = date('Y-m-d', strtotime("-{$days} days"));
        
        $query = $this->newQuery()
            ->select('date', 'mood')
            ->selectRaw('COUNT(*) as count')
            ->where('date', '>=', $startDate)
            ->groupBy('date', 'mood')
            ->orderBy('date');
            
        if ($zodiacSign) {
            $query->where('zodiac_sign', $zodiacSign);
        }
        
        // Results are likely raw data, not full model instances here
        return $query->get();
    }
    
    /**
     * Get energy level trends
     * 
     * @param string $zodiacSign Optional zodiac sign filter
     * @param int $days Number of days to analyze
     * @return array
     */
    public function getEnergyTrends($zodiacSign = null, $days = 30)
    {
        $startDate = date('Y-m-d', strtotime("-{$days} days"));
        
        $query = $this->query
            ->select('date')
            ->selectRaw('AVG(energy_level) as avg_energy')
            ->where('date', '>=', $startDate)
            ->groupBy('date')
            ->orderBy('date');
            
        if ($zodiacSign) {
            $query = $query->where('zodiac_sign', $zodiacSign);
        }
        
        return $query->get();
    }
    
    /**
     * Get lucky numbers for date and sign
     * 
     * @param string $date The date
     * @param string $zodiacSign The zodiac sign
     * @return array
     */
    public function getLuckyNumbers($date, $zodiacSign)
    {
        $vibe = $this->getByDateAndSign($date, $zodiacSign);
        
        if ($vibe && isset($vibe['lucky_numbers'])) {
            return json_decode($vibe['lucky_numbers'], true) ?: [];
        }
        
        return [];
    }
    
    /**
     * Get compatibility info for date and sign
     * 
     * @param string $date The date
     * @param string $zodiacSign The zodiac sign
     * @return array
     */
    public function getCompatibility($date, $zodiacSign)
    {
        $vibe = $this->getByDateAndSign($date, $zodiacSign);
        
        if ($vibe && isset($vibe['compatibility'])) {
            return json_decode($vibe['compatibility'], true) ?: [];
        }
        
        return [];
    }
    
    /**
     * Search daily vibes
     * 
     * @param string $search The search term
     * @return array
     */
    public function search($search)
    {
        return $this->query
            ->where('message', 'LIKE', "%{$search}%")
            ->orWhere('advice', 'LIKE', "%{$search}%")
            ->orWhere('mood', 'LIKE', "%{$search}%")
            ->orderBy('date', 'DESC')
            ->get();
    }
    
    /**
     * Get daily vibes with pagination
     * 
     * @param int $page The page number
     * @param int $perPage Items per page
     * @param string $zodiacSign Optional zodiac sign filter
     * @return array
     */
    public function paginate($page = 1, $perPage = 10, $zodiacSign = null)
    {
        $offset = ($page - 1) * $perPage;
        
        $query = $this->query;
        
        if ($zodiacSign) {
            $query = $query->where('zodiac_sign', $zodiacSign);
        }
        
        $items = $query
            ->orderBy('date', 'DESC')
            ->limit($perPage)
            ->offset($offset)
            ->get();
            
        $totalQuery = $this->query;
        if ($zodiacSign) {
            $totalQuery = $totalQuery->where('zodiac_sign', $zodiacSign);
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
    
    /**
     * Delete old daily vibes
     * 
     * @param int $daysOld Number of days old to delete
     * @return int Number of deleted records
     */
    public function deleteOld($daysOld = 365)
    {
        $cutoffDate = date('Y-m-d', strtotime("-{$daysOld} days"));
        
        return $this->query
            ->where('date', '<', $cutoffDate)
            ->delete();
    }
}