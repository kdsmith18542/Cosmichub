<?php

namespace App\Repositories;

use App\Core\Repository\Repository;
use App\Models\CreditTransaction;

/**
 * CreditTransaction Repository for handling credit transaction data operations
 */
class CreditTransactionRepository extends Repository
{
    /**
     * @var string The model class
     */
    protected $model = CreditTransaction::class;
    
    // The $table property is inherited or derived from the model.
    
    /**
     * Find transactions by user ID.
     *
     * @param int $userId
     * @param int|null $limit
     * @param int|null $offset
     * @return CreditTransaction[] An array of CreditTransaction objects.
     */
    public function findByUserId(int $userId, ?int $limit = null, ?int $offset = null): array
    {
        $query = $this->newQuery()->where('user_id', $userId)->orderBy('created_at', 'desc');
        
        if ($limit !== null) {
            $query->limit($limit);
        }
        
        if ($offset !== null) {
            $query->offset($offset);
        }
        
        $results = $query->get();
        
        return array_map(fn($data) => $this->mapResultToModel($data), $results->all());
    }
    
    /**
     * Count transactions by user ID
     *
     * @param int $userId
     * @return int
     */
    public function countByUserId(int $userId): int
    {
        return $this->newQuery()->where('user_id', $userId)->count();
    }
    
    /**
     * Get total credits by user ID
     *
     * @param int $userId
     * @return float
     */
    public function getTotalCreditsByUserId(int $userId): float
    {
        return (float) $this->newQuery()->where('user_id', $userId)
            ->where('type', CreditTransaction::TYPE_CREDIT)
            ->sum('amount');
    }
    
    /**
     * Get total debits by user ID
     *
     * @param int $userId
     * @return float
     */
    public function getTotalDebitsByUserId(int $userId): float
    {
        return (float) $this->newQuery()->where('user_id', $userId)
            ->where('type', CreditTransaction::TYPE_DEBIT)
            ->sum('amount');
    }
    
    /**
     * Get credits earned this month by user ID
     *
     * @param int $userId
     * @return float
     */
    public function getCreditsEarnedThisMonth(int $userId): float
    {
        return (float) $this->newQuery()->where('user_id', $userId)
            ->where('type', CreditTransaction::TYPE_CREDIT)
            ->whereYear('created_at', date('Y'))
            ->whereMonth('created_at', date('m'))
            ->sum('amount');
    }
    
    /**
     * Find transaction by reference ID and type
     *
     * @param string $referenceId
     * @param string $referenceType
     * @return CreditTransaction|null
     */
    /**
     * Find a single transaction by reference ID and type.
     *
     * @param string $referenceId
     * @param string $referenceType
     * @return CreditTransaction|null
     */
    public function findByReferenceId(string $referenceId, string $referenceType): ?CreditTransaction
    {
        $result = $this->newQuery()->where('reference_id', $referenceId)
            ->where('reference_type', $referenceType)
            ->first();
        
        return $result ? $this->mapResultToModel($result) : null;
    }
    
    /**
     * Find transactions by type
     *
     * @param string $type
     * @return array
     */
    /**
     * Find transactions by type
     *
     * @param string $type
     * @return CreditTransaction[]
     */
    public function findByType(string $type): array
    {
        $results = $this->newQuery()->where('type', $type)->get();
        
        return array_map(fn($data) => $this->mapResultToModel($data), $results->all());
    }
    
    /**
     * Find transactions by reference ID and type
     *
     * @param string $referenceId
     * @param string $referenceType
     * @return array
     */
    /**
     * Find transactions by reference ID and type
     *
     * @param string $referenceId
     * @param string $referenceType
     * @return CreditTransaction[]
     */
    public function findByReference(string $referenceId, string $referenceType): array
    {
        $results = $this->newQuery()->where('reference_id', $referenceId)
            ->where('reference_type', $referenceType)
            ->get();
        
        return array_map(fn($data) => $this->mapResultToModel($data), $results->all());
    }
    

}