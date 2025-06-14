<?php

namespace App\Core\Repository;

use App\Core\Database\QueryBuilder;
use App\Core\Database\DatabaseManager;
use App\Core\Database\Model; // Corrected to App\Core\Database\Model as per interface and general structure
use App\Core\Application;
use App\Core\Traits\Loggable;
use App\Core\Repository\RepositoryInterface;
use App\Core\Exceptions\NotFoundException; // Assuming this will be created or exists

/**
 * Base Repository class for data access layer
 */
abstract class Repository implements RepositoryInterface
{
    use Loggable;
    /**
     * @var DatabaseManager The database manager instance
     */
    protected $db;
    
    /**
     * @var QueryBuilder The query builder instance
     */
    protected $query;
    
    /**
     * @var string The table name
     */
    protected $table;
    
    /**
     * @var string The model class
     */
    protected $model;
    
    /**
     * @var Application The application instance
     */
    protected $app;
    
    /**
     * Create a new repository instance
     * 
     * @param DatabaseManager $db The database manager
     * @param Application $app The application instance
     */
    public function __construct(DatabaseManager $db, Application $app)
    {
        $this->db = $db;
        $this->app = $app;
        $this->query = $this->db->table($this->getTable());
    }
    
    /**
     * Get the table name
     * 
     * @return string
     */
    protected function getTable()
    {
        if ($this->table) {
            return $this->table;
        }
        
        if ($this->model) {
            $model = new $this->model();
            return $model->getTable();
        }
        
        throw new \Exception('Table name not defined in repository');
    }
    
    /**
     * Find a record by ID
     * 
     * @param mixed $id The record ID
     * @return Model|null
     */
    public function find($id, array $columns = ['*'])
    {
        $result = $this->query->select($columns)->where($this->getModel()->getPrimaryKey(), $id)->first();
        
        if (!$result) {
            return null;
        }
        
        return $this->model ? new $this->model($result) : $result;
    }
    
    public function findOrFail($id): Model
    {
        $model = $this->find($id);
        if (!$model) {
            $modelName = $this->model ? (new \ReflectionClass($this->model))->getShortName() : 'Record';
            throw NotFoundException::model($modelName, $id);
        }
        return $model;
    }
    
    /**
     * Get all records
     * 
     * @param array $columns The columns to select
     * @return array
     */
    public function all(array $columns = ['*'])
    {
        $results = $this->query->select($columns)->get();
        
        if (!$this->model) {
            return $results;
        }
        
        return array_map(function($result) {
            return new $this->model($result);
        }, $results);
    }
    
    /**
     * Get records with pagination
     * 
     * @param int $perPage The number of records per page
     * @param int $page The page number
     * @return array
     */
    public function paginate(int $perPage = 15, array $columns = ['*'])
    {
        // Assuming $this->query->paginate() is compatible with Laravel-style paginators
        // or a similar structure that the application uses.
        // The `request()->input('page', 1)` is a common way to get current page for Laravel.
        // If not using Laravel's request helper, this needs to be adapted.
        $currentPage = isset($_GET['page']) ? (int)$_GET['page'] : 1; // Basic pagination page retrieval
        
        $query = $this->query->select($columns);
        $total = (clone $query)->count(); // Clone to avoid affecting the main query for results

        $results = $query->limit($perPage)
                         ->offset(($currentPage - 1) * $perPage)
                         ->get();

        $items = $this->model ? array_map(function ($item) {
            return new $this->model($item);
        }, $results) : $results;

        // This structure is a common way to return pagination data.
        // Adjust if your application uses a specific Paginator class or format.
        return [
            'data' => $items,
            'total' => $total,
            'per_page' => $perPage,
            'current_page' => $currentPage,
            'last_page' => ceil($total / $perPage),
            'from' => $total > 0 ? (($currentPage - 1) * $perPage) + 1 : 0,
            'to' => $total > 0 ? min($total, $currentPage * $perPage) : 0,
        ];
    }
    
    /**
     * Create a new record
     * 
     * @param array $data The record data
     * @return Model|array
     */
    public function create(array $data): Model // Adjusted return type to Model as per interface
    {
        $modelInstance = $this->getModel();
        if ($modelInstance->usesTimestamps()) {
            $now = date('Y-m-d H:i:s');
            $createdAtField = $modelInstance->getCreatedAtColumn();
            $updatedAtField = $modelInstance->getUpdatedAtColumn();
            if (empty($data[$createdAtField])) {
                $data[$createdAtField] = $now;
            }
            if (empty($data[$updatedAtField])) {
                $data[$updatedAtField] = $now;
            }
        }

        // Assuming QueryBuilder's insertGetId returns the last inserted ID.
        $id = $this->query->insertGetId($data);
        
        // Fetch the newly created record to return a full Model instance
        return $this->findOrFail($id);
    }
    
    /**
     * Update a record
     * 
     * @param mixed $id The record ID
     * @param array $data The update data
     * @return bool
     */
    public function update($id, array $data): bool
    {
        $modelInstance = $this->getModel();
        if ($modelInstance->usesTimestamps()) {
            $updatedAtField = $modelInstance->getUpdatedAtColumn();
            if (empty($data[$updatedAtField])) {
                $data[$updatedAtField] = date('Y-m-d H:i:s');
            }
        }
        $affectedRows = $this->query->where($modelInstance->getPrimaryKey(), $id)->update($data);
        return $affectedRows > 0;
    }
    
    /**
     * Delete a record
     * 
     * @param mixed $id The record ID
     * @return bool
     */
    public function delete($id): bool
    {
        $affectedRows = $this->query->where($this->getModel()->getPrimaryKey(), $id)->delete();
        return $affectedRows > 0;
    }

    /**
     * Get the model instance.
     *
     * @return Model
     */
    public function getModel()
    {
        if (!$this->model) {
            // This case should ideally not happen if repositories are tied to models.
            // Or, a generic stdClass or array-based model could be returned.
            // For now, throwing an exception or returning a basic object.
            throw new \Exception('Model not defined for this repository.');
        }
        return new $this->model();
    }

    /**
     * Find a record by a specific column and value.
     *
     * @param string $field
     * @param mixed $value
     * @param array $columns
     * @return Model|null
     */
    public function findBy(string $field, $value, array $columns = ['*'])
    {
        $result = $this->query->select($columns)->where($field, $value)->first();
        
        if (!$result) {
            return null;
        }
        
        return $this->model ? new $this->model($result) : $result;
    }

    /**
     * Find all records by a specific column and value.
     *
     * @param string $field
     * @param mixed $value
     * @param array $columns
     * @return array
     */
    public function findAllBy(string $field, $value, array $columns = ['*'])
    {
        $results = $this->query->select($columns)->where($field, $value)->get();
        
        if (!$this->model) {
            return $results;
        }
        
        return array_map(function($result) {
            return new $this->model($result);
        }, $results);
    }

    /**
     * Get a new query builder instance for the repository's model.
     *
     * @return \App\Core\Database\QueryBuilder
     */
    public function newQuery()
    {
        // Reset query builder to a fresh state for the table
        $this->query = $this->db->table($this->getTable());
        return $this->query;
    }
}

    
    /**
     * Count records
     * 
     * @return int
     */
    public function count()
    {
        return $this->query->count();
    }
    
    /**
     * Find records by criteria
     * 
     * @param array $criteria The search criteria
     * @return array
     */
    public function findBy(array $criteria)
    {
        $query = $this->query;
        
        foreach ($criteria as $column => $value) {
            $query = $query->where($column, $value);
        }
        
        $results = $query->get();
        
        if (!$this->model) {
            return $results;
        }
        
        return array_map(function($result) {
            return new $this->model($result);
        }, $results);
    }
    
    /**
     * Find one record by criteria
     * 
     * @param array $criteria The search criteria
     * @return Model|array|null
     */
    public function findOneBy(array $criteria)
    {
        $query = $this->query;
        
        foreach ($criteria as $column => $value) {
            $query = $query->where($column, $value);
        }
        
        $result = $query->first();
        
        if (!$result) {
            return null;
        }
        
        return $this->model ? new $this->model($result) : $result;
    }
    
    /**
     * Check if a record exists
     * 
     * @param mixed $id The record ID
     * @return bool
     */
    public function exists($id)
    {
        return $this->query->where('id', $id)->exists();
    }
    
    /**
     * Get a fresh query builder instance
     * 
     * @return QueryBuilder
     */
    public function newQuery()
    {
        return $this->db->table($this->getTable());
    }
    
    /**
     * Execute a custom query
     * 
     * @param \Closure $callback The query callback
     * @return mixed
     */
    public function query(\Closure $callback)
    {
        return $callback($this->newQuery());
    }
}