<?php

namespace App\Core\Container;

use Psr\Container\ContainerExceptionInterface;

/**
 * Container exception class implementing PSR-11 ContainerExceptionInterface
 * 
 * This exception is thrown when the container encounters an error during
 * dependency resolution or service instantiation.
 */
class ContainerException extends \Exception implements ContainerExceptionInterface
{
    //
}