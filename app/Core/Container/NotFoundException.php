<?php

namespace App\Core\Container;

use Psr\Container\NotFoundExceptionInterface;

/**
 * Not found exception class implementing PSR-11 NotFoundExceptionInterface
 * 
 * This exception is thrown when the container cannot find a requested
 * service or binding.
 */
class NotFoundException extends ContainerException implements NotFoundExceptionInterface
{
    //
}