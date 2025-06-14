<?php

namespace App\Repositories;

use App\Core\Repository\Repository;
use App\Models\User;
use DateTime;

/**
 * User Repository for handling user data operations
 */
class UserRepository extends Repository
{
    protected string $model = User::class; // Changed from $modelClass

    /**
     * Find a user by email.
     *
     * @param string $email The user email.
     * @return User|null
     */
    public function findByEmail(string $email): ?User
    {
        return $this->newQuery()->where('email', $email)->first();
        // return $this->mapResultToModel($result);
    }

    /**
     * Find a user by username.
     *
     * @param string $username The username.
     * @return User|null
     */
    public function findByUsername(string $username): ?User
    {
        return $this->newQuery()->where('username', $username)->first();
        // return $this->mapResultToModel($result);
    }

    /**
     * Find users by status.
     *
     * @param string $status The user status.
     * @return User[]
     */
    public function findByStatus(string $status): array
    {
        $results = $this->newQuery()->where('status', $status)->get();
        return array_map(fn($data) => new $this->model((array)$data), $results);
    }

    /**
     * Get active users
     *
     * @return User[]
     */
    public function getActiveUsers(): array
    {
        return $this->findByStatus(User::STATUS_ACTIVE); // Assuming User model has STATUS_ACTIVE constant
    }

    /**
     * Get users with premium subscriptions.
     *
     * @return User[]
     */
    public function getPremiumUsers(): array
    {
        $results = $this->newQuery()
            ->join('subscriptions', 'users.id', '=', 'subscriptions.user_id')
            ->where('subscriptions.status', '=', 'active') // Consider using a constant if available
            ->where('subscriptions.plan_type', '!=', 'free') // Consider using a constant if available
            ->select('users.*')
            ->distinct()
            ->get();
        return array_map(fn($data) => new $this->model((array)$data), $results);
    }

    /**
     * Search users by name, email or username.
     *
     * @param string $searchTerm The search term.
     * @return User[]
     */
    public function search(string $searchTerm): array
    {
        $results = $this->newQuery()
            ->where('name', 'LIKE', "%{$searchTerm}%")
            ->orWhere('email', 'LIKE', "%{$searchTerm}%")
            ->orWhere('username', 'LIKE', "%{$searchTerm}%")
            ->get();
        return array_map(fn($data) => new $this->model((array)$data), $results);
    }

    /**
     * Get users registered in the last N days.
     *
     * @param int $days The number of days.
     * @return User[]
     */
    public function getRecentUsers(int $days = 7): array
    {
        $date = date('Y-m-d H:i:s', strtotime("-{$days} days"));

        $results = $this->newQuery()
            ->where('created_at', '>=', $date)
            ->orderBy('created_at', 'DESC')
            ->get();
        return array_map(fn($data) => new $this->model((array)$data), $results);
    }
    
    /**
     * Update user last login
     * 
     * @param int $userId The user ID
     * @return bool
     */
    public function updateLastLogin(int $userId): bool
    {
        $affectedRows = $this->newQuery()->where((new $this->model)->getKeyName(), $userId)->update([
            'last_login_at' => date('Y-m-d H:i:s'),
        ]);
        return $affectedRows > 0;
    }
    
    /**
     * Increment user login count
     * 
     * @param int $userId The user ID
     * @return bool
     */
    public function incrementLoginCount(int $userId): bool
    {
        $affectedRows = $this->newQuery()
            ->where((new $this->model)->getKeyName(), $userId)
            ->increment('login_count');
        return $affectedRows > 0;
    }
    
    /**
     * Get user statistics
     *
     * @return array{
     *  total: int,
     *  active: int,
     *  premium: int,
     *  recent: int,
     *  free: int
     * }
     */
    public function getStatistics(): array
    {
        $total = $this->newQuery()->count();
        $active = $this->newQuery()->where('status', User::STATUS_ACTIVE)->count(); // Assuming User model has STATUS_ACTIVE constant

        $premium = $this->newQuery()
            ->join('subscriptions', 'users.id', '=', 'subscriptions.user_id')
            ->where('subscriptions.status', '=', 'active') // Consider using a constant
            ->where('subscriptions.plan_type', '!=', 'free') // Consider using a constant
            ->distinct()
            ->count('users.id');

        $recentDate = date('Y-m-d H:i:s', strtotime('-7 days'));
        $recent = $this->newQuery()->where('created_at', '>=', $recentDate)->count();

        // This calculation for 'free' users might be an oversimplification.
        // It assumes all non-premium users are 'free'. A more accurate count might require
        // checking users not in the premium list or users with a 'free' plan explicitly.
        // For now, we keep the existing logic.
        $free = $total - $premium;

        return [
            'total' => (int) $total,
            'active' => (int) $active,
            'premium' => (int) $premium,
            'recent' => (int) $recent,
            'free' => (int) $free
        ];
    }
}