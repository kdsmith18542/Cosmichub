<?php

namespace App\Core\Exceptions;

use Psr\Container\NotFoundExceptionInterface;

/**
 * Exception thrown when a requested entry is not found in the container
 */
class ContainerNotFoundException extends \Exception implements NotFoundExceptionInterface
{
    // No additional methods needed as this is just implementing the PSR-11 interface
}