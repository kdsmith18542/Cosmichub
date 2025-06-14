<?php

namespace App\Core\Http;

use App\Core\ServiceProvider;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\Http\JsonResponse;
use App\Core\Http\RedirectResponse;

/**
 * HTTP Service Provider
 *
 * This service provider registers HTTP-related services including
 * request and response handling, following the refactoring plan
 * to improve HTTP layer abstraction.
 */
class HttpServiceProvider extends ServiceProvider
{
    /**
     * Register HTTP services
     *
     * @return void
     */
    public function register()
    {
        $this->registerRequest();
        $this->registerResponse();
        $this->registerJsonResponse();
        $this->registerRedirectResponse();
    }

    /**
     * Boot HTTP services
     *
     * @return void
     */
    public function boot()
    {
        // Set up global request instance
        $request = $this->container->get('request');
        $this->container->instance('current.request', $request);
    }

    /**
     * Register the request service
     *
     * @return void
     */
    protected function registerRequest()
    {
        $this->container->singleton('request', function ($container) {
            return Request::createFromGlobals();
        });

        $this->container->alias('request', Request::class);
    }

    /**
     * Register the response service
     *
     * @return void
     */
    protected function registerResponse()
    {
        $this->container->bind('response', function ($container) {
            return new Response();
        });

        $this->container->alias('response', Response::class);
    }

    /**
     * Register the JSON response service
     *
     * @return void
     */
    protected function registerJsonResponse()
    {
        $this->container->bind('response.json', function ($container) {
            return new JsonResponse();
        });

        $this->container->alias('response.json', JsonResponse::class);
    }

    /**
     * Register the redirect response service
     *
     * @return void
     */
    protected function registerRedirectResponse()
    {
        $this->container->bind('response.redirect', function ($container) {
            return new RedirectResponse();
        });

        $this->container->alias('response.redirect', RedirectResponse::class);
    }

    /**
     * Get the services provided by the provider
     *
     * @return array
     */
    public function provides()
    {
        return [
            'request',
            'response',
            'response.json',
            'response.redirect',
            Request::class,
            Response::class,
            JsonResponse::class,
            RedirectResponse::class,
        ];
    }
}