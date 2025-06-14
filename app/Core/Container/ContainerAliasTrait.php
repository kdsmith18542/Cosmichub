<?php

namespace App\Core\Container;

/**
 * Trait for container alias functionality
 */
trait ContainerAliasTrait
{
    /**
     * @var array The container's aliases
     */
    protected $aliases = [];
    
    /**
     * @var array The container's abstract aliases
     */
    protected $abstractAliases = [];
    
    /**
     * Register an alias for an abstract type
     * 
     * @param string $abstract The abstract type
     * @param string $alias The alias
     * @return void
     * 
     * @throws \LogicException
     */
    public function alias($abstract, $alias)
    {
        if ($alias === $abstract) {
            throw new \LogicException("[{$abstract}] is aliased to itself.");
        }
        
        $this->aliases[$alias] = $abstract;
        
        $this->abstractAliases[$abstract][] = $alias;
    }
    
    /**
     * Get the alias for an abstract if it exists
     * 
     * @param string $abstract The abstract type
     * @return string|null
     */
    protected function getAlias($abstract)
    {
        if (isset($this->aliases[$abstract])) {
            return $this->getAlias($this->aliases[$abstract]);
        }
        
        return $abstract;
    }
    
    /**
     * Remove all of the aliases for an abstract
     * 
     * @param string $abstract The abstract type
     * @return void
     */
    protected function removeAbstractAlias($abstract)
    {
        if (!isset($this->abstractAliases[$abstract])) {
            return;
        }
        
        foreach ($this->abstractAliases[$abstract] as $alias) {
            unset($this->aliases[$alias]);
        }
        
        unset($this->abstractAliases[$abstract]);
    }
    
    /**
     * Get all of the aliases
     * 
     * @return array
     */
    public function getAliases()
    {
        return $this->aliases;
    }
}