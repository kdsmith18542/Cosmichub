<?php

namespace App\Core\View;

use App\Core\ServiceProvider;
use App\Core\View as TwigView; // Alias the new View class

/**
 * Enhanced ViewServiceProvider for view management
 * 
 * This provider has been enhanced to support improved view configuration,
 * template engine integration, and better view compilation and caching.
 */
class ViewServiceProvider extends ServiceProvider
{
    /**
     * Services provided by this provider
     *
     * @var array
     */
    protected $provides = [
        'view',
        TwigView::class, // Use the aliased class
    ];
    
    /**
     * Singletons to register
     *
     * @var array
     */
    protected $singletons = [
        'view' => TwigView::class, // Bind 'view' to the new TwigView class
    ];
    
    /**
     * Aliases for services
     *
     * @var array
     */
    protected $aliases = [
        'view' => TwigView::class,
    ];
    
    /**
     * Register view services
     *
     * @return void
     */
    protected function registerServices()
    {
        // Register the new Twig-based View class as a singleton
        $this->singleton('view', function ($app) {
            return new TwigView();
        });
    }
    
    /**
     * Boot view services
     *
     * @return void
     */
    protected function bootServices()
    {
        // No specific boot logic needed for the simple TwigView integration
        // Twig environment is configured in the TwigView constructor
    }

    // Removed methods related to ViewFactory, View, compiler, and paths as they are handled by TwigView

}