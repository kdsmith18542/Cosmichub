<?php

namespace App\Core\Database;

/**
 * Expression class for raw SQL expressions
 */
class Expression
{
    /**
     * @var string The value
     */
    protected $value;
    
    /**
     * Create a new raw expression instance
     * 
     * @param string $value The value
     */
    public function __construct($value)
    {
        $this->value = $value;
    }
    
    /**
     * Get the value of the expression
     * 
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }
    
    /**
     * Get the value of the expression
     * 
     * @return string
     */
    public function __toString()
    {
        return (string) $this->getValue();
    }
}