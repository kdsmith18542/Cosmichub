<?php

namespace App\Core\Contracts;

/**
 * Service Interface
 * 
 * Defines the contract that all services must implement
 * Provides a standard interface for service lifecycle management
 */
interface ServiceInterface
{
    /**
     * Initialize the service
     * 
     * This method is called when the service is first created
     * Use this to set up any required resources or configurations
     * 
     * @return void
     */
    public function initialize();
    
    /**
     * Boot the service
     * 
     * This method is called after all services have been registered
     * Use this to perform any setup that depends on other services
     * 
     * @return void
     */
    public function boot();
    
    /**
     * Get the service name
     * 
     * @return string
     */
    public function getName();
    
    /**
     * Get the service version
     * 
     * @return string
     */
    public function getVersion();
    
    /**
     * Check if the service is initialized
     * 
     * @return bool
     */
    public function isInitialized();
    
    /**
     * Check if the service is booted
     * 
     * @return bool
     */
    public function isBooted();
    
    /**
     * Get service dependencies
     * 
     * Returns an array of service names that this service depends on
     * 
     * @return array
     */
    public function getDependencies();
    
    /**
     * Get service configuration
     * 
     * @return array
     */
    public function getConfig();
    
    /**
     * Set service configuration
     * 
     * @param array $config
     * @return void
     */
    public function setConfig(array $config);
    
    /**
     * Destroy the service
     * 
     * Clean up any resources used by the service
     * 
     * @return void
     */
    public function destroy();
}