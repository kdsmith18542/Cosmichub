<?php

namespace App\Core\Contracts;

/**
 * Service Provider Interface
 * 
 * Defines the contract that all service providers must implement
 * Service providers are responsible for registering services and their dependencies
 */
interface ServiceProviderInterface
{
    /**
     * Register services in the container
     * 
     * This method is called during the registration phase
     * Use this to bind services, interfaces, and dependencies
     * 
     * @return void
     */
    public function register();
    
    /**
     * Boot the service provider
     * 
     * This method is called after all providers have been registered
     * Use this to perform any setup that depends on other services
     * 
     * @return void
     */
    public function boot();
    
    /**
     * Get the services provided by this provider
     * 
     * @return array
     */
    public function provides();
    
    /**
     * Check if the provider is deferred
     * 
     * Deferred providers are only loaded when their services are requested
     * 
     * @return bool
     */
    public function isDeferred();
    
    /**
     * Check if the provider has been registered
     * 
     * @return bool
     */
    public function isRegistered();
    
    /**
     * Check if the provider has been booted
     * 
     * @return bool
     */
    public function isBooted();
    
    /**
     * Get the provider name
     * 
     * @return string
     */
    public function getName();
    
    /**
     * Get the provider version
     * 
     * @return string
     */
    public function getVersion();
    
    /**
     * Get provider dependencies
     * 
     * Returns an array of provider names that this provider depends on
     * 
     * @return array
     */
    public function getDependencies();
    
    /**
     * Get provider configuration
     * 
     * @return array
     */
    public function getConfig();
    
    /**
     * Set provider configuration
     * 
     * @param array $config
     * @return void
     */
    public function setConfig(array $config);
}