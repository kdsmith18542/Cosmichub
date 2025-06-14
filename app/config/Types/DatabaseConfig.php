<?php

namespace App\Config\Types;

class DatabaseConfig
{
    public string $driver;
    public string $database;
    public string $host;
    public int $port;
    public string $username;
    public string $password;
    public string $charset;
    public string $collation;
    public string $prefix;

    public function __construct(array $config)
    {
        $this->driver = $config['driver'] ?? 'sqlite';
        $this->database = $config['database'] ?? '';
        $this->host = $config['host'] ?? 'localhost';
        $this->port = $config['port'] ?? 3306;
        $this->username = $config['username'] ?? '';
        $this->password = $config['password'] ?? '';
        $this->charset = $config['charset'] ?? 'utf8mb4';
        $this->collation = $config['collation'] ?? 'utf8mb4_unicode_ci';
        $this->prefix = $config['prefix'] ?? '';
    }
}