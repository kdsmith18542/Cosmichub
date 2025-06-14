<?php

namespace App\Repositories;

use App\Core\Repository\Repository;
use App\Models\Shareable;


/**
 * Shareable Repository for handling shareable data operations
 */
class ShareableRepository extends Repository
{
    protected string $model = Shareable::class; // Changed from $modelClass

    /**
     * Find a shareable by ID.
     *
     * @param int $id
     * @return Shareable|null
     */
    public function findById(int $id): ?Shareable
    {
        // Assuming the base find() method returns a model instance or null
        return parent::find($id);
    }
    
    /**
     * Find shareables by user ID
     *
     * @param int $userId
     * @return Shareable[]
     */
    public function findByUserId(int $userId): array
    {
        $results = $this->newQuery()->where('user_id', $userId)->get();
        return array_map(fn($data) => new $this->model((array)$data), $results);
    }
    
    /**
     * Find public shareables
     *
     * @return Shareable[]
     */
    public function findPublic(): array
    {
        $results = $this->newQuery()->where('is_public', true)->get();
        return array_map(fn($data) => new $this->model((array)$data), $results);
    }
    
    /**
     * Find shareable by share URL
     *
     * @param string $shareUrl
     * @return Shareable|null
     */
    public function findByShareUrl(string $shareUrl): ?Shareable
    {
        return $this->newQuery()->where('share_url', $shareUrl)->first();
        // return $this->mapResultToModel($result); // first() returns model or null
    }
}