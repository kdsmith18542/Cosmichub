<?php

namespace App\Repositories;

use App\Core\Repository\Repository;
use App\Models\CelebrityReport;

/**
 * Celebrity Report Repository for handling celebrity report data operations
 */
class CelebrityReportRepository extends Repository
{
    /**
     * @var string The model class
     */
    protected $model = CelebrityReport::class;
    
    // The $table property is inherited or derived from the model.

    /**
     * Find celebrity report by name
     * 
     * @param string $name The celebrity name
     * @return CelebrityReport|null
     */
    public function findByName(string $name): ?CelebrityReport
    {
        // Assuming findOneBy is a method in the base Repository that uses newQuery()
        // and maps the result to the model instance.
        return parent::findOneBy(['name' => $name]);
    }
    
    /**
     * Find celebrity reports by birth date
     * 
     * @param string $birthDate The birth date
     * @return array
     */
    /**
     * Find celebrity reports by birth date.
     *
     * @param string $birthDate The birth date (YYYY-MM-DD)
     * @return CelebrityReport[]
     */
    public function findByBirthDate(string $birthDate): array
    {
        // Assuming findBy is a method in the base Repository that uses newQuery()
        // and maps results to model instances.
        return parent::findBy(['birth_date' => $birthDate]);
    }
    
    /**
     * Find celebrity reports by category
     * 
     * @param string $category The celebrity category
     * @return array
     */
    /**
     * Find celebrity reports by category.
     *
     * @param string $category The celebrity category
     * @return CelebrityReport[]
     */
    public function findByCategory(string $category): array
    {
        // Assuming findBy is a method in the base Repository that uses newQuery()
        // and maps results to model instances.
        return parent::findBy(['category' => $category]);
    }
    
    /**
     * Find featured celebrity reports
     * 
     * @return array
     */
    /**
     * Find featured celebrity reports.
     *
     * @return CelebrityReport[]
     */
    public function findFeatured(): array
    {
        // Assuming findBy is a method in the base Repository that uses newQuery()
        // and maps results to model instances.
        return parent::findBy(['is_featured' => 1]);
    }
    
    /**
     * Get popular celebrity reports
     * 
     * @param int $limit The number of reports to retrieve
     * @return array
     */
    /**
     * Get popular celebrity reports.
     *
     * @param int $limit The number of reports to retrieve
     * @return CelebrityReport[]
     */
    public function getPopular(int $limit = 10): array
    {
        $results = $this->newQuery()
            ->orderBy('view_count', 'DESC')
            ->limit($limit)
            ->get();
        
        return array_map(fn($data) => $this->mapResultToModel($data), $results->all());
    }
    
    /**
     * Get recent celebrity reports
     * 
     * @param int $limit The number of reports to retrieve
     * @return array
     */
    /**
     * Get recent celebrity reports.
     *
     * @param int $limit The number of reports to retrieve
     * @return CelebrityReport[]
     */
    public function getRecent(int $limit = 10): array
    {
        $results = $this->newQuery()
            ->orderBy('created_at', 'DESC')
            ->limit($limit)
            ->get();

        return array_map(fn($data) => $this->mapResultToModel($data), $results->all());
    }
    
    /**
     * Search celebrity reports
     * 
     * @param string $search The search term
     * @return array
     */
    /**
     * Search celebrity reports.
     *
     * @param string $search The search term
     * @return CelebrityReport[]
     */
    public function search(string $search): array
    {
        $results = $this->newQuery()
            ->where(function ($query) use ($search) {
                $query->where('name', 'LIKE', "%{$search}%")
                      ->orWhere('description', 'LIKE', "%{$search}%") // Assuming description exists
                      ->orWhere('category', 'LIKE', "%{$search}%");
            })
            ->get();

        return array_map(fn($data) => $this->mapResultToModel($data), $results->all());
    }
    
    /**
     * Get celebrity reports with pagination
     * 
     * @param int $page The page number
     * @param int $perPage Items per page
     * @param string $category Optional category filter
     * @return array
     */
    /**
     * Get celebrity reports with pagination, optionally filtered by category.
     *
     * @param string|null $category Optional category filter
     * @param int $page The page number
     * @param int $perPage Items per page
     * @param array $columns
     * @return array With 'items', 'total', 'page', 'per_page', 'total_pages' keys.
     */
    public function paginateByCategory(?string $category = null, int $page = 1, int $perPage = 15, array $columns = ['*']): array
    {
        $query = $this->newQuery()->orderBy('name');
        if ($category) {
            $query->where('category', $category);
        }
        $paginatedResult = parent::paginate($page, $perPage, $columns, $query);

        return [
            'items' => array_map(fn($item) => $this->mapResultToModel($item), $paginatedResult['data']),
            'total' => $paginatedResult['total'],
            'page' => $paginatedResult['current_page'],
            'per_page' => $paginatedResult['per_page'],
            'total_pages' => $paginatedResult['last_page']
        ];
    }
    
    /**
     * Increment view count
     * 
     * @param int $id The celebrity report ID
     * @return bool
     */
    /**
     * Increment view count for a celebrity report.
     *
     * @param int $id The celebrity report ID
     * @return bool
     */
    public function incrementViewCount(int $id): bool
    {
        /** @var CelebrityReport $modelInstance */
        $modelInstance = $this->getModelInstance();
        $affectedRows = $this->newQuery()
            ->where($modelInstance->getKeyName(), $id) // Use getKeyName() for primary key column name
            ->increment('view_count');
        return $affectedRows > 0;
    }
    
    /**
     * Get celebrity report statistics
     * 
     * @return array
     */
    /**
     * Get celebrity report statistics.
     *
     * @return array
     */
    public function getStatistics(): array
    {
        $total = $this->newQuery()->count();
        $featured = $this->newQuery()->where('is_featured', 1)->count();
        
        $categoryResults = $this->newQuery()->distinct()->select('category')->get();
        // Assuming $categoryResults is a collection of objects/arrays with 'category' key/property
        $distinctCategories = $categoryResults->pluck('category')->unique()->count();
        
        $totalViews = $this->newQuery()->sum('view_count');
        
        return [
            'total' => $total,
            'featured' => $featured,
            'categories_count' => $distinctCategories,
            'total_views' => (int) $totalViews
        ];
    }
    
    /**
     * Get celebrities by birth month
     * 
     * @param int $month The birth month (1-12)
     * @return array
     */
    /**
     * Get celebrities by birth month.
     *
     * @param int $month The birth month (1-12)
     * @return CelebrityReport[]
     */
    public function getByBirthMonth(int $month): array
    {
        // Ensure month is within valid range
        if ($month < 1 || $month > 12) {
            return [];
        }
        $results = $this->newQuery()
            ->whereRaw('MONTH(birth_date) = ?', [$month]) // This is MySQL specific
            ->get();

        return array_map(fn($data) => $this->mapResultToModel($data), $results->all());
    }
    
    /**
     * Get celebrities born on this day in history
     * 
     * @param string $date Date in format 'MM-DD'
     * @return array
     */
    /**
     * Get celebrities born on this day in history.
     *
     * @param string|null $date Date in format 'MM-DD'. Defaults to current month-day.
     * @return CelebrityReport[]
     */
    public function getBornOnThisDay(?string $date = null): array
    {
        if (!$date) {
            $date = date('m-d');
        } elseif (!preg_match('/^(0[1-9]|1[0-2])-(0[1-9]|[12][0-9]|3[01])$/', $date)) {
            // Basic validation for MM-DD format
            return []; // Or throw an InvalidArgumentException
        }

        $results = $this->newQuery()
            ->whereRaw('DATE_FORMAT(birth_date, "%m-%d") = ?', [$date]) // MySQL specific
            ->get();

        return array_map(fn($data) => $this->mapResultToModel($data), $results->all());
    }
    
    /**
     * Toggle featured status
     * 
     * @param int $id The celebrity report ID
     * @return bool
     */
    public function toggleFeatured($id)
    {
        $report = $this->find($id);
        if (!$report) {
            return false;
        }
        
        $newStatus = $report['is_featured'] ? 0 : 1;
        return $this->update($id, ['is_featured' => $newStatus]);
    }
}