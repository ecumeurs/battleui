<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class StandardEnvelope
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 1. Handle Request (Unwrap Envelope)
        if ($request->is('api/v1/*') && $request->isJson()) {
            $payload = $request->json()->all();
            
            // Check if it follows the Standard Message Envelope
            if (isset($payload['request_id']) && array_key_exists('data', $payload)) {
                // Promoting request_id from envelope to header for consistency
                $request->headers->set('X-Request-ID', $payload['request_id']);
                
                // Replace request input with the 'data' content only
                // This makes it transparent to controllers and validators
                $request->replace(is_array($payload['data']) ? $payload['data'] : []);
            }
        }

        $response = $next($request);

        // Only wrap JSON responses for /api/v1 routes
        if ($request->is('api/v1/*') && $response->headers->get('Content-Type') === 'application/json') {
            $content = json_decode($response->getContent(), true);

            // If it's already wrapped (e.g. by exception handler), don't wrap again
            if (isset($content['request_id']) && isset($content['success'])) {
                return $response;
            }

            $requestId = $request->header('X-Request-ID') 
                ?? $request->input('request_id') 
                ?? (string) Str::uuid7();

            $wrapped = [
                'request_id' => $requestId,
                'message' => $content['message'] ?? ($response->isSuccessful() ? 'Operation successful' : 'Operation failed'),
                'success' => $response->isSuccessful(),
                'data' => $response->isSuccessful() ? ($content['data'] ?? $content ?? (object) []) : (object) [],
                'meta' => $content['meta'] ?? (object) [],
            ];

            // Specific handling for validation errors from Laravel
            if ($response->getStatusCode() === 422 && isset($content['errors'])) {
                $wrapped['message'] = 'Validation failed';
                $wrapped['meta']['errors'] = $content['errors'];
            }

            $response->setContent(json_encode($wrapped));
        }

        return $response;
    }
}
