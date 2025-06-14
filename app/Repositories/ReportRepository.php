<?php

namespace App\Repositories;

use App\Core\Repository\Repository;
use App\Models\Report;
use DateTime;

/**
 * Report Repository for handling report data operations
 */
class ReportRepository extends Repository
{
    protected string $model = Report::class; // Changed from $modelClass

    /**
     * Find reports by user ID.
     *
     * @param int $userId The user ID.
     * @return Report[]
     */
    public function findByUserId(int $userId): array
    {
        $results = $this->newQuery()->where('user_id', $userId)->get();
        return array_map(fn($data) => new $this->model((array)$data), $results);
    }

    /**
     * Find reports by birth date.
     *
     * @param string $birthDate The birth date.
     * @return Report[]
     */
    public function findByBirthDate(string $birthDate): array
    {
        $results = $this->newQuery()->where('birth_date', $birthDate)->get();
        return array_map(fn($data) => new $this->model((array)$data), $results);
    }

    /**
     * Find reports by status.
     *
     * @param string $status The report status.
     * @return Report[]
     */
    public function findByStatus(string $status): array
    {
        $results = $this->newQuery()->where('status', $status)->get();
        return array_map(fn($data) => new $this->model((array)$data), $results);
    }
    
    /**
     * Get recent reports
     * 
     * @param int $limit The number of reports to retrieve
     * @return Report[]
     */
    public function getRecent(int $limit = 10): array
    {
        $results = $this->newQuery()
            ->orderBy('created_at', 'DESC')
            ->limit($limit)
            ->get();
        return array_map(fn($data) => new $this->model((array)$data), $results);
    }
    
    /**
     * Get reports statistics
     * 
     * @return array
     */
    public function getStatistics(): array
    {
        $total = $this->newQuery()->count();
        $pending = $this->newQuery()->where('status', Report::STATUS_PENDING)->count();
        $completed = $this->newQuery()->where('status', Report::STATUS_COMPLETED)->count();
        $failed = $this->newQuery()->where('status', Report::STATUS_FAILED)->count();

        return [
            'total' => $total,
            'pending' => $pending,
            'completed' => $completed,
            'failed' => $failed,
        ];
    }
    
    /**
     * Search reports
     * 
     * @param string $search The search term
     * @return Report[]
     */
    public function search(string $search): array
    {
        $results = $this->newQuery()
            ->where('title', 'LIKE', "%{$search}%") // Assuming 'title' is the correct field instead of 'name'
            ->orWhere('birth_date', 'LIKE', "%{$search}%")
            ->get();
        return array_map(fn($data) => new $this->model((array)$data), $results);
    }
    
    /**
     * Update report status
     * 
     * @param int $id The report ID
     * @param string $status The new status
     * @return bool
     */
    public function updateStatus(int $id, string $status): bool
    {
        return parent::update($id, ['status' => $status]);
    }
    
    /**
     * Delete old reports
     * 
     * @param int $days Number of days to keep
     * @return int Number of deleted reports
     */
    public function deleteOld(int $days = 365): int
    {
        $cutoffDate = date('Y-m-d H:i:s', strtotime("-{$days} days"));

        return $this->newQuery()
            ->where('created_at', '<', $cutoffDate)
            ->delete();
    }
    
    /**
     * Count reports by user ID
     *
     * @param int $userId
     * @return int
     */
    public function countByUserId(int $userId): int
    {
        return $this->newQuery()->where('user_id', $userId)->count();
    }
    
    /**
     * Count reports by user ID for this month
     *
     * @param int $userId
     * @return int
     */
    public function countByUserIdThisMonth(int $userId): int
    {
        $year = date('Y');
        $month = date('m');
        return $this->newQuery()->where('user_id', $userId)
            ->whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->count();
    }
    
    /**
     * Count completed reports by user ID
     *
     * @param int $userId
     * @return int
     */
    public function countCompletedByUserId(int $userId): int
    {
        return $this->newQuery()->where('user_id', $userId)
            ->where('status', Report::STATUS_COMPLETED)
            ->count();
    }
}