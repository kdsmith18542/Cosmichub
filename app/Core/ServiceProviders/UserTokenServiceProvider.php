<?php

namespace App\Core\ServiceProviders;

use App\Core\Container\ServiceProvider;
use App\Services\UserTokenService;
use App\Repositories\UserTokenRepository;

class UserTokenServiceProvider extends ServiceProvider
{
    /**
     * Register the service in the container
     *
     * @return void
     */
    public function register(): void
    {
        $this->container->bind(UserTokenRepository::class, function ($container) {
            return new UserTokenRepository();
        });
        
        $this->container->bind(UserTokenService::class, function ($container) {
            return new UserTokenService($container);
        });
    }
}