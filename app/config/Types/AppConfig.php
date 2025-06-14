<?php

namespace App\Config\Types;

class AppConfig
{
    public string $name;
    public string $env;
    public bool $debug;
    public string $url;
    public string $timezone;
    public string $dateFormat;
    public string $timeFormat;

    public function __construct(array $config)
    {
        $this->name = $config['name'] ?? 'CosmicHub';
        $this->env = $config['env'] ?? 'production';
        $this->debug = $config['debug'] ?? false;
        $this->url = $config['url'] ?? 'http://cosmichub.local';
        $this->timezone = $config['timezone'] ?? 'UTC';
        $this->dateFormat = $config['date_format'] ?? 'm/d/Y';
        $this->timeFormat = $config['time_format'] ?? 'h:i A';
    }
}