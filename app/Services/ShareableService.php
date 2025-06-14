<?php

namespace App\Services;

use App\Core\Service\Service;
use App\Repositories\ShareableRepository;

/**
 * Shareable Service for handling shareable content business logic
 */
class ShareableService extends Service
{
    /**
     * @var ShareableRepository
     */
    private $shareableRepository;
    
    /**
     * ShareableService constructor
     *
     * @param ShareableRepository $shareableRepository
     */
    public function __construct(ShareableRepository $shareableRepository)
    {
        $this->shareableRepository = $shareableRepository;
    }
    /**
     * Find a shareable by ID
     *
     * @param int $id
     * @return \App\Models\Shareable|null
     */
    public function findById(int $id)
    {
        return $this->shareableRepository->findById($id);
    }

    /**
     * Create a new shareable
     *
     * @param array $data
     * @return \App\Models\Shareable
     */
    public function create(array $data)
    {
        return $this->shareableRepository->create($data);
    }

    /**
     * Update a shareable
     *
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function update(int $id, array $data): bool
    {
        return $this->shareableRepository->update($id, $data);
    }

    /**
     * Delete a shareable
     *
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool
    {
        return $this->shareableRepository->delete($id);
    }
    
    /**
     * Find shareables by user ID
     *
     * @param int $userId
     * @return array
     */
    public function findByUserId(int $userId): array
    {
        return $this->shareableRepository->findByUserId($userId);
    }
    
    /**
     * Find public shareables
     *
     * @return array
     */
    public function findPublic(): array
    {
        return $this->shareableRepository->findPublic();
    }
    
    /**
     * Find shareable by share URL
     *
     * @param string $shareUrl
     * @return \App\Models\Shareable|null
     */
    public function findByShareUrl(string $shareUrl)
    {
        return $this->shareableRepository->findByShareUrl($shareUrl);
    }
}