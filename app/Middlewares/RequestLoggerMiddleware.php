<?php

namespace App\Middlewares;

use App\Core\Middleware\Middleware;
use App\Core\Http\Request;
use App\Core\Http\Response;
use Psr\Log\LoggerInterface;

class RequestLoggerMiddleware extends Middleware
{
    protected LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function handle(Request $request, callable $next): Response
    {
        // Log incoming request
        $this->logger->info('Incoming Request', [
            'method' => $request->getMethod(),
            'uri' => $request->getUri(),
            'headers' => $request->getHeaders(),
            'ip' => $request->getClientIp(),
        ]);

        // Process the request through the next middleware/controller
        $response = $next($request);

        // Log outgoing response
        $this->logger->info('Outgoing Response', [
            'status' => $response->getStatusCode(),
            'headers' => $response->getHeaders(),
            'content_length' => strlen($response->getContent()),
        ]);

        return $response;
    }
}