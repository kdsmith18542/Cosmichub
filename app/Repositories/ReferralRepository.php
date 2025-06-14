<?php

namespace App\Repositories;

use App\Models\Referral;
use App\Core\Repository\Repository;


class ReferralRepository extends Repository
{
    protected string $model = Referral::class; // Changed from $modelClass

    /**
     * Create a new referral for a user.
     *
     * @param int $userId
     * @param string $type
     * @param array $data
     * @return Referral|null
     */
    public function createForUser(int $userId, string $type, array $data = []): ?Referral
    {
        $code = $this->generateUniqueCode();
        $referralData = array_merge([
            'user_id' => $userId,
            'code' => $code,
            'type' => $type,
            // Assuming 'expires_at' is fillable or handled by the model's mutators/accessors
            // If Referral::TYPE_ONE_TIME is 'one-time', ensure 'expires_at' is correctly formatted for DB
            'expires_at' => $type === Referral::TYPE_ONE_TIME ? date('Y-m-d H:i:s', strtotime('+30 days')) : null,
        ], $data);

        // Ensure $referralData keys match $fillable in Referral model
        return $this->create($referralData); // create() should return the model instance or null
    }

    /**
     * Find a referral by its code.
     *
     * @param string $code
     * @return Referral|null
     */
    public function findByCode(string $code): ?Referral
    {
        return $this->newQuery()->where('code', $code)->first();
        // return $this->mapResultToModel($result); // first() returns model or null
    }

    /**
     * Find referrals by user ID and type.
     *
     * @param int $userId
     * @param string|null $type
     * @return Referral[]
     */
    public function findByUserAndType(int $userId, ?string $type = null): array
    {
        $query = $this->newQuery()->where('user_id', $userId);
        if ($type) {
            $query->where('type', $type);
        }
        $results = $query->get();
        return array_map(fn($data) => new $this->model((array)$data), $results);
    }
    
    private function generateUniqueCode(int $length = 8): string
    {
        // Basic unique code generation, consider a more robust solution for production
        do {
            $code = substr(str_shuffle(str_repeat('0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ', $length)), 0, $length);
        } while ($this->newQuery()->where('code', $code)->exists());

        return $code;
    }
}