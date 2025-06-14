<?php

namespace App\Repositories;

use App\Core\Repository\Repository;
use App\Models\CreditPack;

/**
 * CreditPack Repository for handling credit pack data operations
 */
class CreditPackRepository extends Repository
{
    /**
     * @var string The model class
     */
    protected $model = CreditPack::class;
    
    // The $table property is inherited or derived from the model.
    
    /**
     * Find active credit packs
     *
     * @return CreditPack[]
     */
    /**
     * Find active credit packs, ordered by sort_order.
     *
     * @return CreditPack[]
     */
    public function findActive(): array
    {
        $results = $this->newQuery()
            ->where('is_active', true) // In SQL, true is often 1
            ->orderBy('sort_order', 'ASC') // Added ordering as per model's getActivePacks
            ->get();
        
        return array_map(fn($data) => $this->mapResultToModel($data), $results->all());
    }
    

}