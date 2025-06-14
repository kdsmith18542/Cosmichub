<?php

namespace App\Core\Service;

use App\Core\ServiceProvider;
use App\Services\UserService;
use App\Services\ReportService;
use App\Services\ArchetypeService;
use App\Services\CelebrityReportService;
use App\Services\SubscriptionService;
use App\Services\CreditService;
use App\Services\DailyVibeService;
use App\Services\NotificationService;
use App\Services\AnalyticsService;
use App\Services\GeminiService;
use App\Services\AuthService;

/**
 * Service Provider for registering business logic services
 */
class ServiceServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider
     * 
     * @return void
     */
    public function register()
    {
        // Register the base service
        $this->app->bind(Service::class, function ($app) {
            return new class($app) extends Service {};
        });
        
        // Register specific services
        $this->registerServices();
    }
    
    /**
     * Register specific services
     * 
     * @return void
     */
    protected function registerServices()
    {
        $services = [
            'UserService' => UserService::class,
            'ReportService' => ReportService::class,
            'ArchetypeService' => ArchetypeService::class,
            'CelebrityReportService' => CelebrityReportService::class,
            'SubscriptionService' => SubscriptionService::class,
            'CreditService' => CreditService::class,
            'DailyVibeService' => DailyVibeService::class,
            'NotificationService' => NotificationService::class,
            'AnalyticsService' => AnalyticsService::class,
            'GeminiService' => GeminiService::class,
            'AuthService' => AuthService::class,
        ];
        
        foreach ($services as $alias => $class) {
            $this->app->bind($alias, function ($app) use ($class) {
                return new $class($app);
            });
            
            $this->app->bind($class, function ($app) use ($class) {
                return new $class($app);
            });
        }
    }
    
    /**
     * Boot the service provider
     * 
     * @return void
     */
    public function boot()
    {
        // Boot logic here if needed
    }
}