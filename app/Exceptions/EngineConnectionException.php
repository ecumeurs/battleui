<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Support\Facades\Log;

class EngineConnectionException extends Exception
{
    public function report(): void
    {
        Log::error("Engine Connection Failure: " . $this->getMessage());
    }

    public function render($request)
    {
        return response()->json([
            'request_id' => $request->header('X-Request-ID'),
            'message' => 'Connection to Game Engine failed',
            'success' => false,
            'data' => [],
            'meta' => [
                'error' => $this->getMessage(),
                'code' => 'ENGINE_UNREACHABLE'
            ]
        ], 503);
    }
}
