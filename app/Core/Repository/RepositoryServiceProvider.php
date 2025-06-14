<?php

namespace App\Core\Repository;

use App\Core\ServiceProvider;
use App\Repositories\UserRepository;
use App\Repositories\ReportRepository;
use App\Repositories\ArchetypeRepository;
use App\Repositories\CelebrityReportRepository;
use App\Repositories\SubscriptionRepository;
use App\Repositories\CreditTransactionRepository;
use App\Repositories\DailyVibeRepository;
use App\Repositories\NotificationRepository;

/**
 * Repository Service Provider for registering repository services
 */
class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider
     * 
     * @return void
     */
    public function register()
    {
        // Register the base repository
        $this->app->bind(Repository::class, function ($app) {
            return new Repository(
                $app->make('App\Core\Database\DatabaseManager'),
                $app
            );
        });
        
        // Register specific repositories
        $this->registerRepositories();
    }
    
    /**
     * Register specific repositories
     * 
     * @return void
     */
    protected function registerRepositories()
    {
        $repositories = [
            'UserRepository' => UserRepository::class,
            'ReportRepository' => ReportRepository::class,
            'ArchetypeRepository' => ArchetypeRepository::class,
            'CelebrityReportRepository' => CelebrityReportRepository::class,
            'SubscriptionRepository' => SubscriptionRepository::class,
            'CreditRepository' => CreditRepository::class,
            'DailyVibeRepository' => DailyVibeRepository::class,
            'NotificationRepository' => NotificationRepository::class,
        ];
        
        foreach ($repositories as $alias => $class) {
            $this->app->bind($alias, function ($app) use ($class) {
                return new $class(
                    $app->make('App\Core\Database\DatabaseManager'),
                    $app
                );
            });
            
            $this->app->bind($class, function ($app) use ($class) {
                return new $class(
                    $app->make('App\Core\Database\DatabaseManager'),
                    $app
                );
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