<?php

namespace App\Core\Database;

use PDO;
use SplQueue;
use App\Core\Config;
use App\Core\Logger;

class ConnectionPool
{
    protected $config;
    protected $logger;
    protected $connections;
    protected $maxConnections;
    protected $minConnections;
    protected $idleTimeout;
    protected $lastUsed = [];

    public function __construct(Config $config, Logger $logger)
    {
        $this->config = $config;
        $this->logger = $logger;
        $this->connections = new SplQueue();
        $this->maxConnections = $this->config->get('database.pool.max_connections', 10);
        $this->minConnections = $this->config->get('database.pool.min_connections', 1);
        $this->idleTimeout = $this->config->get('database.pool.idle_timeout', 300); // 5 minutes

        $this->initializePool();
    }

    protected function initializePool()
    {
        for ($i = 0; $i < $this->minConnections; $i++) {
            $this->addConnectionToPool();
        }
    }

    protected function addConnectionToPool()
    {
        try {
            $pdo = $this->createPdoConnection();
            $connection = new Connection($pdo, $this->config->get('database.connections.' . $this->config->get('database.default')));
            $this->connections->enqueue($connection);
            $this->lastUsed[spl_object_hash($connection)] = time();
            $this->logger->debug("Added new connection to pool. Current size: " . $this->connections->count());
        } catch (\PDOException $e) {
            $this->logger->error("Failed to add connection to pool: " . $e->getMessage());
        }
    }

    protected function createPdoConnection(): PDO
    {
        $defaultConnection = $this->config->get('database.default', 'mysql');
        $config = $this->config->get("database.connections.{$defaultConnection}");

        $dsn = $this->buildDsn($config);
        $username = $config['username'] ?? null;
        $password = $config['password'] ?? null;
        $options = $config['options'] ?? [];

        $pdo = new PDO($dsn, $username, $password, $options);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        
        return $pdo;
    }

    protected function buildDsn(array $config): string
    {
        switch ($config['driver']) {
            case 'mysql':
                return "mysql:host={$config['host']};port={$config['port']};dbname={$config['database']};charset={$config['charset']}";
            case 'sqlite':
                return "sqlite:{$config['database']}";
            case 'pgsql':
                return "pgsql:host={$config['host']};port={$config['port']};dbname={$config['database']};user={$config['username']};password={$config['password']}";
            default:
                throw new \InvalidArgumentException("Unsupported database driver: {$config['driver']}");
        }
    }

    public function getConnection(): ConnectionInterface
    {
        $this->pruneIdleConnections();

        if (!$this->connections->isEmpty()) {
            $connection = $this->connections->dequeue();
            $this->logger->debug("Reusing connection from pool. Current size: " . $this->connections->count());
            return $connection;
        } elseif ($this->connections->count() < $this->maxConnections) {
            $this->addConnectionToPool();
            if (!$this->connections->isEmpty()) {
                $connection = $this->connections->dequeue();
                $this->logger->debug("Created new connection for use. Current size: " . $this->connections->count());
                return $connection;
            }
        }
        
        // Fallback if pool is exhausted and max connections reached
        $this->logger->warning("Connection pool exhausted. Creating a temporary connection.");
        return new Connection($this->createPdoConnection(), $this->config->get('database.connections.' . $this->config->get('database.default')));
    }

    public function releaseConnection(ConnectionInterface $connection)
    {
        if ($this->connections->count() < $this->maxConnections) {
            $this->connections->enqueue($connection);
            $this->lastUsed[spl_object_hash($connection)] = time();
            $this->logger->debug("Released connection to pool. Current size: " . $this->connections->count());
        } else {
            // Close the connection if the pool is full
            $this->logger->debug("Connection pool is full, closing connection.");
            unset($connection);
        }
    }

    protected function pruneIdleConnections()
    {
        $now = time();
        $tempQueue = new SplQueue();
        while (!$this->connections->isEmpty()) {
            $connection = $this->connections->dequeue();
            $hash = spl_object_hash($connection);
            if (($now - $this->lastUsed[$hash]) < $this->idleTimeout) {
                $tempQueue->enqueue($connection);
            } else {
                $this->logger->debug("Pruning idle connection. Current size: " . $this->connections->count());
                unset($this->lastUsed[$hash]);
                unset($connection);
            }
        }
        $this->connections = $tempQueue;
    }

    public function getCurrentPoolSize(): int
    {
        return $this->connections->count();
    }

    public function getMaxPoolSize(): int
    {
        return $this->maxConnections;
    }

    public function getMinPoolSize(): int
    {
        return $this->minConnections;
    }
}