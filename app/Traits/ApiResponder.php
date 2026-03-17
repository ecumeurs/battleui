<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;

/**
 * Unified API Response trait
 * @spec-link [[api_standard_envelope]]
 * @spec-link [[api_request_id]]
 */
trait ApiResponder
{
    /**
     * Standardized success response
     * @spec-link [[api_standard_envelope]]
     */
    protected function success(mixed $data, string $message = 'Success', int $code = 200, array $meta = []): JsonResponse
    {
        return response()->json([
            'request_id' => $this->resolveRequestId(),
            'message'    => $message,
            'success'    => true,
            'data'       => $data,
            'meta'       => (object) $meta,
        ], $code);
    }

    /**
     * Standardized error response
     * @spec-link [[api_standard_envelope]]
     */
    protected function error(string $message, int $code = 400, mixed $details = null, array $meta = []): JsonResponse
    {
        return response()->json([
            'request_id' => $this->resolveRequestId(),
            'message'    => $message,
            'success'    => false,
            'data'       => $details ?? (object) [],
            'meta'       => (object) $meta,
        ], $code);
    }

    /**
     * Resolves the request ID according to [[api_request_id]] priority:
     * 1. JSON Payload 'request_id'
     * 2. HTTP Header 'X-Request-ID'
     * 3. Fresh UUIDv7
     * 
     * @spec-link [[api_request_id]]
     */
    protected function resolveRequestId(): string
    {
        $request = request();
        
        return $request->input('request_id') 
            ?? $request->header('X-Request-ID') 
            ?? (string) \Illuminate\Support\Str::uuid7();
    }
}