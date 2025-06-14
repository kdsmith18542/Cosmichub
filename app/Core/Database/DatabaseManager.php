<?php

namespace App\Core\Database;

use App\Core\Application;
use PDO;
use PDOException;
use App\Core\Database\ConnectionInterface;

/**
 * DatabaseManager class for managing database connections
 */
class DatabaseManager
{
    /**
     * @var Application The application instance
     */
    protected $app;
    
    /**
     * @var array The active connections
     */
    protected $connections = [];
    
    /**
     * @var string The default connection name
     */
    protected $default;
    
    /**
     * Create a new database manager instance
     * 
     * @param Application $app The application instance
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
        $this->default = $app->config('database.default', 'mysql');
        $this->pool = $this->app->make(ConnectionPool::class);
    }
    
    /**
     * Get a database connection
     * 
     * @param string|null $name The connection name
     * @return ConnectionInterface
     */
    public function connection($name = null)
    {
        $name = $name ?: $this->default;
        
        // If we already have a connection, return it
        if (isset($this->connections[$name])) {
            return $this->connections[$name];
        }
        
        // Get the connection configuration
        $config = $this->getConfig($name);
        
        // Create the connection
        return $this->connections[$name] = $this->makeConnection($config);
    }
    
    /**
     * Get the configuration for a connection
     * 
     * @param string $name The connection name
     * @return array
     * @throws \InvalidArgumentException
     */
    protected function getConfig($name)
    {
        $config = $this->app->config("database.connections.{$name}");
        
        if (is_null($config)) {
            throw new \InvalidArgumentException("Database connection [{$name}] not configured.");
        }
        
        return $config;
    }
    
    /**
     * Make a new database connection
     * 
     * @param array $config The connection configuration
     * @return ConnectionInterface
     * @throws \PDOException
     */
    protected function makeConnection(array $config)
    {
        $dsn = $this->getDsn($config);
        
        $options = $config['options'] ?? [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        
        try {
            $pdo = new PDO(
                $dsn,
                $config['username'] ?? '',
                $config['password'] ?? '',
                $options
            );
            
            if (isset($config['charset'])) {
                $pdo->prepare("SET NAMES '{$config['charset']}'");
            }
            
            if (isset($config['timezone'])) {
                $pdo->prepare("SET time_zone = '{$config['timezone']}'");
            }
            
            return new Connection($pdo, $config); // Connection class implements ConnectionInterface
        } catch (PDOException $e) {
            throw new PDOException(
                "Could not connect to database [{$config['database']}]: {$e->getMessage()}",
                (int) $e->getCode(),
                $e
            );
        }
    }
    
    /**
     * Get the DSN for a connection
     * 
     * @param array $config The connection configuration
     * @return string
     */
    protected function getDsn(array $config)
    {
        $driver = $config['driver'] ?? 'mysql';
        
        switch ($driver) {
            case 'mysql':
                return $this->getMysqlDsn($config);
            case 'sqlite':
                return $this->getSqliteDsn($config);
            case 'pgsql':
                return $this->getPostgresDsn($config);
            default:
                throw new \InvalidArgumentException("Unsupported database driver [{$driver}].");
        }
    }
    
    /**
     * Get the DSN for a MySQL connection
     * 
     * @param array $config The connection configuration
     * @return string
     */
    protected function getMysqlDsn(array $config)
    {
        $host = $config['host'] ?? 'localhost';
        $port = $config['port'] ?? 3306;
        $database = $config['database'] ?? '';
        
        return "mysql:host={$host};port={$port};dbname={$database}";
    }
    
    /**
     * Get the DSN for a SQLite connection
     * 
     * @param array $config The connection configuration
     * @return string
     */
    protected function getSqliteDsn(array $config)
    {
        $path = $config['database'] ?? '';
        
        if ($path === ':memory:') {
            return 'sqlite::memory:';
        }
        
        return "sqlite:{$path}";
    }
    
    /**
     * Get the DSN for a PostgreSQL connection
     * 
     * @param array $config The connection configuration
     * @return string
     */
    protected function getPostgresDsn(array $config)
    {
        $host = $config['host'] ?? 'localhost';
        $port = $config['port'] ?? 5432;
        $database = $config['database'] ?? '';
        
        return "pgsql:host={$host};port={$port};dbname={$database}";
    }
    
    /**
     * Disconnect from a given database
     * 
     * @param string|null $name The connection name
     * @return void
     */
    public function disconnect($name = null)
    {
        $name = $name ?: $this->default;
        
        unset($this->connections[$name]);
    }
    
    /**
     * Disconnect from all databases
     * 
     * @return void
     */
    public function disconnectAll()
    {
        foreach (array_keys($this->connections) as $name) {
            $this->disconnect($name);
        }
    }
    
    /**
     * Get the default connection name
     * 
     * @return string
     */
    public function getDefaultConnection()
    {
        return $this->default;
    }
    
    /**
     * Set the default connection name
     * 
     * @param string $name The connection name
     * @return $this
     */
    public function setDefaultConnection($name)
    {
        $this->default = $name;
        
        return $this;
    }
    
    /**
     * Get all connections
     * 
     * @return array
     */
    public function getConnections()
    {
        return $this->connections;
    }
    
    /**
     * Dynamically pass methods to the default connection
     * 
     * @param string $method The method name
     * @param array $parameters The method parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return $this->connection()->$method(...$parameters);
    }
}