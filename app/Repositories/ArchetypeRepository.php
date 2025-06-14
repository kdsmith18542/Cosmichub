<?php

namespace App\Repositories;

use App\Core\Repository\Repository;
use App\Models\Archetype;

/**
 * Archetype Repository for handling archetype data operations
 */
class ArchetypeRepository extends Repository
{
    /**
     * @var string The model class
     */
    protected $model = Archetype::class;
    
    // The $table property is inherited from the base Repository or derived from the model.
    // No need to define it here if it matches the model's table name.

    /**
     * Find archetype by name
     * 
     * @param string $name The archetype name
     * @return Archetype|null
     */
    public function findByName(string $name): ?Archetype
    {
        // Assuming findOneBy is a method in the base Repository that uses newQuery()
        // and maps the result to the model instance.
        return parent::findOneBy(['name' => $name]);
    }
    
    /**
     * Find archetype by slug
     * 
     * @param string $slug The archetype slug
     * @return Archetype|null
     */
    public function findBySlug(string $slug): ?Archetype
    {
        // Assuming findOneBy is a method in the base Repository that uses newQuery()
        // and maps the result to the model instance.
        return parent::findOneBy(['slug' => $slug]);
    }
    
    /**
     * Find archetypes by category
     * 
     * @param string $category The archetype category
     * @return array
     */
    /**
     * Find archetypes by category.
     *
     * @param string $category The archetype category
     * @return Archetype[]
     */
    public function findByCategory(string $category): array
    {
        // Assuming findBy is a method in the base Repository that uses newQuery()
        // and maps results to model instances.
        return parent::findBy(['category' => $category]);
    }
    
    /**
     * Find active archetypes
     * 
     * @return array
     */
    /**
     * Find active archetypes.
     *
     * @return Archetype[]
     */
    public function findActive(): array
    {
        // Assuming findBy is a method in the base Repository that uses newQuery()
        // and maps results to model instances.
        return parent::findBy(['is_active' => 1]);
    }
    
    /**
     * Find featured archetypes
     * 
     * @return array
     */
    /**
     * Find featured archetypes.
     *
     * @return Archetype[]
     */
    public function findFeatured(): array
    {
        // Assuming findBy is a method in the base Repository that uses newQuery()
        // and maps results to model instances.
        return parent::findBy(['is_featured' => 1]);
    }
    
    /**
     * Get random archetypes
     * 
     * @param int $limit The number of archetypes to retrieve
     * @return array
     */
    /**
     * Get random archetypes.
     *
     * @param int $limit The number of archetypes to retrieve
     * @return Archetype[]
     */
    public function getRandom(int $limit = 5): array
    {
        $results = $this->newQuery()
            ->where('is_active', 1)
            ->inRandomOrder() // Assuming the QueryBuilder supports this
            ->limit($limit)
            ->get();

        // The base Repository's get() method or a helper should handle mapping to model instances.
        // If $results is a collection of models, this is fine. If it's an array of arrays/stdClass, mapping is needed.
        // Assuming $results from Eloquent-like builder is a collection of models:
        return $results->all(); 
        // If $results is an array of arrays/stdClass from a more basic query builder:
        // return array_map(fn($data) => $this->mapResultToModel($data), $results);
    }
    
    /**
     * Search archetypes
     * 
     * @param string $search The search term
     * @return array
     */
    /**
     * Search archetypes.
     *
     * @param string $search The search term
     * @return Archetype[]
     */
    public function search(string $search): array
    {
        $results = $this->newQuery()
            ->where('is_active', 1)
            ->where(function ($query) use ($search) {
                $query->where('name', 'LIKE', "%{$search}%")
                      ->orWhere('description', 'LIKE', "%{$search}%")
                      ->orWhere('traits', 'LIKE', "%{$search}%");
            })
            ->get();

        // Assuming $results from Eloquent-like builder is a collection of models:
        return $results->all();
        // If $results is an array of arrays/stdClass from a more basic query builder:
        // return array_map(fn($data) => $this->mapResultToModel($data), $results);
    }
    
    /**
     * Get archetypes with pagination
     * 
     * @param int $page The page number
     * @param int $perPage Items per page
     * @return array
     */
    /**
     * Get active archetypes with pagination.
     *
     * @param int $page The page number
     * @param int $perPage Items per page
     * @param array $columns
     * @return array With 'items', 'total', 'page', 'per_page', 'total_pages' keys.
     */
    public function paginateActive(int $page = 1, int $perPage = 15, array $columns = ['*']): array
    {
        $query = $this->newQuery()->where('is_active', 1)->orderBy('name');
        $paginatedResult = parent::paginate($page, $perPage, $columns, $query);

        // Ensure the returned structure matches expectations if the base paginate is different.
        // The base paginate in `App\Core\Repository\Repository` returns an array with 'data', 'total', etc.
        // We need to map 'data' to 'items' and ensure model instances.
        return [
            'items' => array_map(fn($item) => $this->mapResultToModel($item), $paginatedResult['data']),
            'total' => $paginatedResult['total'],
            'page' => $paginatedResult['current_page'],
            'per_page' => $paginatedResult['per_page'],
            'total_pages' => $paginatedResult['last_page']
        ];
    }
    
    /**
     * Get archetype statistics
     * 
     * @return array
     */
    /**
     * Get archetype statistics.
     *
     * @return array
     */
    public function getStatistics(): array
    {
        $total = $this->newQuery()->count();
        $active = $this->newQuery()->where('is_active', 1)->count();
        $featured = $this->newQuery()->where('is_featured', 1)->count();
        
        // Assuming the QueryBuilder's get(['category']) returns a collection of objects/arrays
        // where each item has a 'category' property/key.
        $categoryResults = $this->newQuery()->distinct()->select('category')->get();
        
        // If $categoryResults is a collection of models/objects:
        $distinctCategories = $categoryResults->pluck('category')->unique()->count();
        // If $categoryResults is an array of arrays:
        // $distinctCategories = count(array_unique(array_column($categoryResults->all(), 'category')));
        
        return [
            'total' => $total,
            'active' => $active,
            'featured' => $featured,
            'categories_count' => $distinctCategories
        ];
    }
    
    /**
     * Toggle archetype active status
     * 
     * @param int $id The archetype ID
     * @return bool
     */
    /**
     * Toggle archetype active status.
     *
     * @param int $id The archetype ID
     * @return bool
     */
    public function toggleActive(int $id): bool
    {
        /** @var Archetype|null $archetype */
        $archetype = parent::find($id); // Use parent::find to get the model instance
        if (!$archetype) {
            return false;
        }
        $newStatus = $archetype->is_active ? 0 : 1; 
        return parent::update($id, ['is_active' => $newStatus]); // Use parent::update
    }
    
    /**
     * Toggle archetype featured status
     * 
     * @param int $id The archetype ID
     * @return bool
     */
    /**
     * Toggle archetype featured status.
     *
     * @param int $id The archetype ID
     * @return bool
     */
    public function toggleFeatured(int $id): bool
    {
        /** @var Archetype|null $archetype */
        $archetype = parent::find($id); // Use parent::find to get the model instance
        if (!$archetype) {
            return false;
        }
        $newStatus = $archetype->is_featured ? 0 : 1;
        return parent::update($id, ['is_featured' => $newStatus]); // Use parent::update
    }
}