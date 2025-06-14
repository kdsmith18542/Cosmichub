<?php

namespace App\Services\Providers;

use App\Core\Container\ServiceProvider;
use App\Services\GiftService;
use App\Services\EmailService;
use App\Services\PaymentService;
use App\Repositories\GiftRepository;
use App\Repositories\UserRepository;

class GiftServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->container->bind(GiftService::class, function($container) {
            return new GiftService(
                $container->resolve(EmailService::class),
                $container->resolve(PaymentService::class),
                $container->resolve(GiftRepository::class),
                $container->resolve(UserRepository::class)
            );
        });
    }
}