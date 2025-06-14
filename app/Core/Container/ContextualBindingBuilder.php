<?php

namespace App\Core\Container;

use App\Core\Container;

/**
 * Contextual binding builder for the dependency injection container
 * 
 * This class provides a fluent interface for defining contextual bindings
 * in the container, allowing different implementations to be injected
 * based on the context in which they are requested.
 */
class ContextualBindingBuilder
{
    /**
     * @var Container The container instance
     */
    protected $container;
    
    /**
     * @var string The concrete class being bound
     */
    protected $concrete;
    
    /**
     * @var string The abstract type being bound
     */
    protected $needs;
    
    /**
     * Create a new contextual binding builder
     * 
     * @param Container $container
     * @param string $concrete
     */
    public function __construct(Container $container, $concrete)
    {
        $this->container = $container;
        $this->concrete = $concrete;
    }
    
    /**
     * Define the abstract target of the contextual binding
     * 
     * @param string $abstract
     * @return $this
     */
    public function needs($abstract)
    {
        $this->needs = $abstract;
        
        return $this;
    }
    
    /**
     * Define the implementation for the contextual binding
     * 
     * @param \Closure|string $implementation
     * @return void
     */
    public function give($implementation)
    {
        $this->container->addContextualBinding(
            $this->concrete,
            $this->needs,
            $implementation
        );
    }
    
    /**
     * Define the tagged services to give for the contextual binding
     * 
     * @param string $tag
     * @return void
     */
    public function giveTagged($tag)
    {
        $this->give(function ($container) use ($tag) {
            return $container->tagged($tag);
        });
    }
}