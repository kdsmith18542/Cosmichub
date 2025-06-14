<?php

/**
 * Autoloader Configuration
 * 
 * Configuration for the enhanced autoloader system.
 * This complements Composer's autoloader with additional features.
 */

return [
    /*
    |--------------------------------------------------------------------------
    | PSR-4 Namespace Mappings
    |--------------------------------------------------------------------------
    |
    | Additional PSR-4 namespace to directory mappings that complement
    | Composer's autoloader. These are useful for custom libraries or
    | development-specific mappings.
    |
    */
    'psr4' => [
        // Example: 'Custom\\Library\\' => [base_path('custom/library')],
        // 'Dev\\Tools\\' => [base_path('dev-tools')],
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Class Map
    |--------------------------------------------------------------------------
    |
    | Direct class to file mappings for faster loading of specific classes.
    | This is useful for classes that don't follow PSR-4 conventions or
    | for performance-critical classes.
    |
    */
    'classmap' => [
        // Example: 'LegacyClass' => base_path('legacy/LegacyClass.php'),
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Fallback Directories
    |--------------------------------------------------------------------------
    |
    | Directories to search for classes that couldn't be found using PSR-4
    | or class map. These are searched as a last resort.
    |
    */
    'fallback_dirs' => [
        // Example: base_path('legacy'),
        // base_path('vendor-custom'),
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Debug Mode
    |--------------------------------------------------------------------------
    |
    | When enabled, the autoloader will log detailed information about
    | class loading attempts. This is inherited from app.debug by default.
    |
    */
    'debug' => env('AUTOLOADER_DEBUG', null), // null means inherit from app.debug
    
    /*
    |--------------------------------------------------------------------------
    | Performance Settings
    |--------------------------------------------------------------------------
    |
    | Settings to optimize autoloader performance.
    |
    */
    'performance' => [
        // Enable path caching for faster subsequent loads
        'cache_paths' => true,
        
        // Maximum number of paths to cache
        'cache_limit' => 1000,
        
        // Clear cache on each request (useful for development)
        'clear_cache_on_request' => env('APP_ENV') === 'development',
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Integration Settings
    |--------------------------------------------------------------------------
    |
    | Settings for integrating with other autoloaders and systems.
    |
    */
    'integration' => [
        // Whether to register before Composer's autoloader
        'prepend_to_stack' => false,
        
        // Whether to automatically register on boot
        'auto_register' => false,
        
        // Whether to load from composer.json automatically
        'load_from_composer' => false,
    ],
];