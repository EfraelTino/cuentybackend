<?php

use Fruitcake\Cors\HandleCors;

class CorsMiddleware {
    public function __invoke($request, $response, $next) {
        $response = $next($request, $response);
        
        // Configura los encabezados CORS
        $response = $response
            ->withHeader('Access-Control-Allow-Origin', 'http://localhost:5173')
            ->withHeader('Access-Control-Allow-Methods', 'GET, POST, OPTIONS, PUT, PATCH')
            ->withHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization');
        
        return $response;
    }
}
