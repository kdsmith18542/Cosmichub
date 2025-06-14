<?php

namespace App\Config\Types;

class SessionConfig
{
    public string $driver;
    public int $lifetime;
    public array $cookie;

    public function __construct(array $config)
    {
        $this->driver = $config['driver'] ?? 'file';
        $this->lifetime = $config['lifetime'] ?? 120;
        $this->cookie = [
            'path' => $config['cookie']['path'] ?? '/',
            'domain' => $config['cookie']['domain'] ?? null,
            'secure' => $config['cookie']['secure'] ?? false,
            'httponly' => $config['cookie']['httponly'] ?? true,
            'samesite' => $config['cookie']['samesite'] ?? 'Lax',
        ];
    }
}