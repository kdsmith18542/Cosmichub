<?php

namespace App\Core\Database;

use PDO;
use PDOStatement;

interface ConnectionInterface
{
    public function __construct(PDO $pdo, array $config = []);
    public function beginTransaction();
    public function commit();
    public function rollBack();
    public function transactionLevel();
    public function statement($query, $bindings = []);
    public function select($query, $bindings = [], $useReadPdo = true);
    public function selectOne($query, $bindings = [], $useReadPdo = true);
    public function insert($query, $bindings = []);
    public function update($query, $bindings = []);
    public function delete($query, $bindings = []);
    public function affectingStatement($query, $bindings = []);
    public function prepare($query);
    public function bindValues(PDOStatement $statement, $bindings);
    public function run($query, $bindings, \Closure $callback);
    public function getPdo();
    public function getReadPdo();
    public function setReadPdo(PDO $pdo);
    public function getName();
    public function getConfig();
    public function getDriverName();
    public function getDatabaseName();
    public function getTablePrefix();
    public function setTablePrefix($prefix);
    public function withTablePrefix(\Closure $callback);
    public function getQueryLog();
    public function enableQueryLog();
    public function disableQueryLog();
    public function logging();
    public function flushQueryLog();
    public function pretend(\Closure $callback);
    public function getElapsedTime();
    public function lastInsertId($name = null);
}