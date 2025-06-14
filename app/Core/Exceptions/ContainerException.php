<?php

namespace App\Core\Exceptions;

use Psr\Container\ContainerExceptionInterface;

/**
 * Exception thrown when an error occurs during container operations
 */
class ContainerException extends \Exception implements ContainerExceptionInterface
{
    // No additional methods needed as this is just implementing the PSR-11 interface
}