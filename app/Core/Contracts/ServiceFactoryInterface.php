<?php

namespace App\Core\Contracts;

/**
 * Service Factory Interface
 * 
 * Defines the contract for creating and configuring services
 * Provides methods for service templates, builders, and configuration management
 */
interface ServiceFactoryInterface
{
    /**
     * Create a service instance
     * 
     * @param string $class
     * @param array $config
     * @param array $dependencies
     * @return mixed
     */
    public function create($class, array $config = [], array $dependencies = []);
    
    /**
     * Create a service from a template
     * 
     * @param string $template
     * @param array $config
     * @return mixed
     */
    public function createFromTemplate($template, array $config = []);
    
    /**
     * Register a service template
     * 
     * @param string $name
     * @param array $template
     * @return void
     */
    public function registerTemplate($name, array $template);
    
    /**
     * Get a service template
     * 
     * @param string $name
     * @return array|null
     */
    public function getTemplate($name);
    
    /**
     * Check if a template exists
     * 
     * @param string $name
     * @return bool
     */
    public function hasTemplate($name);
    
    /**
     * Get all registered templates
     * 
     * @return array
     */
    public function getTemplates();
    
    /**
     * Remove a template
     * 
     * @param string $name
     * @return void
     */
    public function removeTemplate($name);
    
    /**
     * Set default configuration
     * 
     * @param array $config
     * @return void
     */
    public function setDefaultConfig(array $config);
    
    /**
     * Get default configuration
     * 
     * @return array
     */
    public function getDefaultConfig();
    
    /**
     * Merge configuration with defaults
     * 
     * @param array $config
     * @return array
     */
    public function mergeConfig(array $config);
    
    /**
     * Resolve service dependencies
     * 
     * @param array $dependencies
     * @return array
     */
    public function resolveDependencies(array $dependencies);
    
    /**
     * Configure a service instance
     * 
     * @param mixed $service
     * @param array $config
     * @return mixed
     */
    public function configure($service, array $config);
    
    /**
     * Inject dependencies into a service
     * 
     * @param mixed $service
     * @param array $dependencies
     * @return mixed
     */
    public function injectDependencies($service, array $dependencies);
    
    /**
     * Get a service builder
     * 
     * @param string $class
     * @return ServiceBuilderInterface
     */
    public function builder($class);
    
    /**
     * Create a service builder
     * 
     * @param string $class
     * @return ServiceBuilderInterface
     */
    public function createBuilder($class);
    
    /**
     * Validate service configuration
     * 
     * @param array $config
     * @param array $rules
     * @return bool
     */
    public function validateConfig(array $config, array $rules = []);
    
    /**
     * Get configuration validation rules
     * 
     * @param string $class
     * @return array
     */
    public function getValidationRules($class);
    
    /**
     * Set configuration validation rules
     * 
     * @param string $class
     * @param array $rules
     * @return void
     */
    public function setValidationRules($class, array $rules);
    
    /**
     * Get the container instance
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
}