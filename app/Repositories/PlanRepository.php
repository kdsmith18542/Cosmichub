<?php

namespace App\Repositories;

use App\Core\Repository\Repository;
use App\Models\Plan;


/**
 * Plan Repository for handling plan data operations
 */
class PlanRepository extends Repository
{
    /**
     * @var string The model class
     */
    protected $model = Plan::class;
    
    /**
     * Find a plan by ID.
     *
     * @param int $id The ID of the plan.
     * @return Plan|null The found Plan object or null.
     */
    public function findById(int $id): ?Plan
    {
        return parent::find($id);
    }
    
    /**
     * Find active plans
     *
     * @return Plan[]
     */
    public function findActive(): array
    {
        $results = $this->newQuery()->where('is_active', true)->get();
        return array_map(fn($data) => new $this->model((array)$data), $results);
    }
    
    /**
     * Find active plans ordered by price
     *
     * @return Plan[]
     */
    public function findActivePlans(): array
    {
        $results = $this->newQuery()
            ->where('is_active', true)
            ->orderBy('price', 'asc')
            ->get();
        return array_map(fn($data) => new $this->model((array)$data), $results);
    }
    
    /**
     * Find plans by type
     *
     * @param string $type
     * @return Plan[]
     */
    public function findByType(string $type): array
    {
        $results = $this->newQuery()->where('type', $type)->get();
        return array_map(fn($data) => new $this->model((array)$data), $results);
    }
}