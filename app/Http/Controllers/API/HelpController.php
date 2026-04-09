<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\ApiDiscoveryService;
use Illuminate\Http\JsonResponse;

/**
 * @spec-link [[api_help_endpoint]]
 */
class HelpController extends Controller
{
    public function __construct(
        protected ApiDiscoveryService $discoveryService
    ) {}

    /**
     * Display a listing of all documented API endpoints.
     */
    public function index(): JsonResponse
    {
        $endpoints = $this->discoveryService->getEndpoints();

        return response()->json([
            'request_id' => request()->header('X-Request-ID', (string) str()->uuid()),
            'message' => 'API Documentation discovered from Atomic TRACEABLE Documentation (ATD) system.',
            'success' => true,
            'data' => [
                'version' => '1.0.0',
                'endpoints' => $endpoints
            ]
        ]);
    }
}
