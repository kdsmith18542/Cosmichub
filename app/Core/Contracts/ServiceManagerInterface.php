<?php

namespace App\Core\Contracts;

/**
 * Service Manager Interface
 * 
 * Defines the contract for managing services and service providers
 * Handles service registration, booting, and lifecycle management
 */
interface ServiceManagerInterface
{
    /**
     * Register a service provider
     * 
     * @param ServiceProviderInterface|string $provider
     * @param array $options
     * @return ServiceProviderInterface
     */
    public function register($provider, array $options = []);
    
    /**
     * Boot all registered service providers
     * 
     * @return void
     */
    public function boot();
    
    /**
     * Get a service from the container
     * 
     * @param string $name
     * @param array $parameters
     * @return mixed
     */
    public function get($name, array $parameters = []);
    
    /**
     * Check if a service exists
     * 
     * @param string $name
     * @return bool
     */
    public function has($name);
    
    /**
     * Register multiple service providers
     * 
     * @param array $providers
     * @return void
     */
    public function registerProviders(array $providers);
    
    /**
     * Get all registered providers
     * 
     * @return array
     */
    public function getProviders();
    
    /**
     * Get a specific provider
     * 
     * @param string $name
     * @return ServiceProviderInterface|null
     */
    public function getProvider($name);
    
    /**
     * Check if a provider is registered
     * 
     * @param string $name
     * @return bool
     */
    public function hasProvider($name);
    
    /**
     * Check if the manager has been booted
     * 
     * @return bool
     */
    public function isBooted();
    
    /**
     * Get all registered services
     * 
     * @return array
     */
    public function getServices();
    
    /**
     * Register a deferred service
     * 
     * @param string $service
     * @param string $provider
     * @return void
     */
    public function addDeferredService($service, $provider);
    
    /**
     * Load a deferred service
     * 
     * @param string $service
     * @return void
     */
    public function loadDeferredService($service);
    
    /**
     * Check if a service is deferred
     * 
     * @param string $service
     * @return bool
     */
    public function isDeferredService($service);
    
    /**
     * Get all deferred services
     * 
     * @return array
     */
    public function getDeferredServices();
    
    /**
     * Flush all providers and services
     * 
     * @return void
     */
    public function flush();
    
    /**
     * Get the underlying container
     * 
     * @return ContainerInterface
     */
    public function getContainer();
    
    /**
     * Set the container instance
     * 
     * @param ContainerInterface $container
     * @return void
     */
    public function setContainer(ContainerInterface $container);
    
    /**
     * Add a lifecycle hook
     * 
     * @param string $event
     * @param callable $callback
     * @return void
     */
    public function addHook($event, callable $callback);
    
    /**
     * Execute lifecycle hooks
     * 
     * @param string $event
     * @param array $parameters
     * @return void
     */
    public function executeHooks($event, array $parameters = []);
    
    /**
     * Get service statistics
     * 
     * @return array
     */
    public function getStatistics();
    
    /**
     * Reset service statistics
     * 
     * @return void
     */
    public function resetStatistics();
}