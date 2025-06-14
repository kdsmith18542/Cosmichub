<?php

namespace App\Core\Repository;

use App\Core\Database\Model; // We might need to adjust this if models don't all use this base

interface RepositoryInterface
{
    /**
     * Retrieve all records.
     *
     * @param array $columns
     * @return array
     */
    public function all(array $columns = ['*']);

    /**
     * Find a record by its primary key.
     *
     * @param mixed $id
     * @param array $columns
     * @return Model|null
     */
    public function find($id, array $columns = ['*']);

    /**
     * Find a record by its primary key or throw an exception.
     *
     * @param mixed $id
     * @param array $columns
     * @return Model
     * @throws \App\Core\Exceptions\NotFoundException
     */
    public function findOrFail($id, array $columns = ['*']);

    /**
     * Find a record by a specific column and value.
     *
     * @param string $field
     * @param mixed $value
     * @param array $columns
     * @return Model|null
     */
    public function findBy(string $field, $value, array $columns = ['*']);

    /**
     * Find all records by a specific column and value.
     *
     * @param string $field
     * @param mixed $value
     * @param array $columns
     * @return array
     */
    public function findAllBy(string $field, $value, array $columns = ['*']);

    /**
     * Create a new record in the database.
     *
     * @param array $data
     * @return Model
     */
    public function create(array $data);

    /**
     * Update a record in the database.
     *
     * @param mixed $id
     * @param array $data
     * @return bool
     */
    public function update($id, array $data);

    /**
     * Delete a record from the database.
     *
     * @param mixed $id
     * @return bool
     */
    public function delete($id);

    /**
     * Get records with pagination.
     *
     * @param int $perPage
     * @param array $columns
     * @return mixed // Should be a paginator instance or array
     */
    public function paginate(int $perPage = 15, array $columns = ['*']);

    /**
     * Get a new query builder instance for the repository's model.
     *
     * @return \App\Core\Database\QueryBuilder
     */
    public function newQuery();

    /**
     * Get the model instance.
     *
     * @return Model
     */
    public function getModel();
}