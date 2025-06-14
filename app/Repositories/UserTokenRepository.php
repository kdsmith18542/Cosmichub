<?php

namespace App\Repositories;

use App\Core\Repository\Repository;
use App\Models\UserToken;

/**
 * UserToken Repository for handling user token data operations
 */
class UserTokenRepository extends Repository
{
    protected string $modelClass = UserToken::class;

    /**
     * Create a new user token.
     *
     * @param array $data
     * @return UserToken|null
     */
    public function create(array $data): ?UserToken
    {
        // The parent::create method should return an instance of $this->modelClass
        // or null on failure. Casting to UserToken|null for clarity.
        return parent::create($data);
    }
    
    /**
     * Find tokens by user ID and type
     *
     * @param int $userId
     * @param string $type
     * @return array
     */
    /**
     * Find tokens by user ID and type.
     *
     * @param int $userId
     * @param string $type
     * @return UserToken[]
     */
    public function findByUserAndType(int $userId, string $type): array
    {
        $results = $this->newQuery()
            ->where('user_id', $userId)
            ->where('type', $type)
            ->get();

        return $this->mapResultsToModels($results);
    }
    
    /**
     * Delete old tokens for cleanup
     *
     * @param int $userId
     * @param string $type
     * @param string $beforeDate
     * @return bool
     */
    /**
     * Delete old tokens for cleanup.
     *
     * @param int $userId
     * @param string $type
     * @param string $beforeDate
     * @return bool
     */
    public function deleteOldTokens(int $userId, string $type, string $beforeDate): bool
    {
        $affectedRows = $this->newQuery()
            ->where('user_id', $userId)
            ->where('type', $type)
            ->where('created_at', '<', $beforeDate)
            ->delete();
        return $affectedRows > 0;
    }
    
    /**
     * Keep only the latest N tokens for a user
     *
     * @param int $userId
     * @param string $type
     * @param int $keepCount
     * @return bool
     */
    /**
     * Keep only the latest N tokens for a user.
     *
     * @param int $userId
     * @param string $type
     * @param int $keepCount
     * @return bool
     */
    public function keepLatestTokens(int $userId, string $type, int $keepCount = 5): bool
    {
        $tokens = $this->newQuery()
            ->where('user_id', $userId)
            ->where('type', $type)
            ->orderBy('created_at', 'desc')
            ->get();

        $mappedTokens = $this->mapResultsToModels($tokens);

        if (count($mappedTokens) > $keepCount) {
            // Get IDs of tokens to keep
            $idsToKeep = array_map(function(UserToken $token) {
                return $token->getKey(); // UserToken model uses static $primaryKey, getKey() should work if it inherits from a base model that uses it.
            }, array_slice($mappedTokens, 0, $keepCount));

            $query = $this->newQuery()
                ->where('user_id', $userId)
                ->where('type', $type);

            if (empty($idsToKeep)) { // Avoid whereNotIn with empty array if all tokens are to be deleted
                return $query->delete() > 0;
            }
            // UserToken model uses static $primaryKey, getModelInstance()->getKeyName() might not be ideal if UserToken doesn't extend the Eloquent-like Model.
            // Assuming 'id' is the primary key as per UserToken::$primaryKey.
            return $query->whereNotIn(UserToken::getPrimaryKeyName(), $idsToKeep)
                ->delete() > 0;
        }

        return true; // No tokens were deleted, or fewer tokens than keepCount existed
    }
    
    /**
     * Find a valid token by token value and type
     *
     * @param string $token
     * @param string $type
     * @return UserToken|null
     */
    /**
     * Find a valid token by token value and type.
     *
     * @param string $token
     * @param string $type
     * @return UserToken|null
     */
    public function findValidToken(string $token, string $type): ?UserToken
    {
        $result = $this->newQuery()
            ->where('token', $token)
            ->where('type', $type)
            ->where('expires_at', '>', date('Y-m-d H:i:s'))
            ->whereNull('used_at')
            ->first();

        return $this->mapResultToModel($result);
    }
    
    /**
     * Invalidate all unused tokens for a user by type
     *
     * @param int $userId
     * @param string $type
     * @return bool
     */
    /**
     * Invalidate all unused tokens for a user by type.
     *
     * @param int $userId
     * @param string $type
     * @return bool
     */
    public function invalidateUnusedTokensByUserAndType(int $userId, string $type): bool
    {
        $now = date('Y-m-d H:i:s');
        $affectedRows = $this->newQuery()
            ->where('user_id', $userId)
            ->where('type', $type)
            ->whereNull('used_at')
            ->update([
                'used_at' => $now,
                'invalidated_at' => $now,
                // 'updated_at' should be handled by the model's $timestamps or QueryBuilder if configured
            ]);
        return $affectedRows > 0;
    }
}