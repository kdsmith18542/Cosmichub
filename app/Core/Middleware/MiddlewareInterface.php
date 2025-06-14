<?php

namespace App\Core\Middleware;

use App\Core\Http\Request;

/**
 * MiddlewareInterface for HTTP middleware
 */
interface MiddlewareInterface
{
    /**
     * Handle the request
     * 
     * @param Request $request The request
     * @param callable|null $next The next middleware
     * @return mixed
     */
    public function handle(Request $request, callable $next = null);
}