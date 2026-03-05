<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;

trait ApiResponder
{
    /**
     * Retourne une réponse de succès unifiée
     */
    protected function success(mixed $data, string $message , int $code = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'errors' => '',
            'data'    => $data,
        ], $code);
    }

    /**
     * Retourne une réponse d'erreur unifiée
     */
    protected function error(string $message, int $code, mixed $details = null): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors'  => $details,
            'data' => '',
        ], $code);
    }
}