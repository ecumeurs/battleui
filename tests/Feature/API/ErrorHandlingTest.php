<?php

namespace Tests\Feature\API;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use TiMacDonald\Log\LogFake;

class ErrorHandlingTest extends TestCase
{
    /** @test */
    public function it_returns_json_envelope_for_404_not_found()
    {
        $response = $this->getJson('/api/v1/non-existent-route-' . Str::random(10));

        $response->assertStatus(404);
        $response->assertJsonStructure([
            'request_id',
            'message',
            'success',
            'data',
            'meta'
        ]);
        $this->assertFalse($response->json('success'));
    }

    /** @test */
    public function it_returns_json_envelope_for_500_internal_error()
    {
        config(['app.debug' => false]);

        // We'll use the temporary test route added to api.php for this
        $response = $this->getJson('/api/v1/test-error');

        $response->assertStatus(500);
        $response->assertJsonStructure([
            'request_id',
            'message',
            'success',
            'data',
            'meta'
        ]);
        $this->assertFalse($response->json('success'));
        $this->assertEquals('Internal Server Error', $response->json('message'));
        $this->assertEmpty((array)$response->json('data'));
    }

    /** @test */
    public function it_includes_provided_request_id_in_error_response()
    {
        $requestId = (string) Str::uuid7();
        $response = $this->withHeaders([
            'X-Request-ID' => $requestId
        ])->getJson('/api/v1/non-existent-route');

        $response->assertStatus(404);
        $this->assertEquals($requestId, $response->json('request_id'));
    }

    /** @test */
    public function it_logs_error_in_tracing_compliant_format()
    {
        Log::spy();

        $requestId = (string) Str::uuid7();
        $refId = substr($requestId, 0, 8);

        $this->withHeaders([
            'X-Request-ID' => $requestId
        ])->getJson('/api/v1/test-error');

        Log::shouldHaveReceived('error')
            ->atLeast()->once()
            ->withArgs(function ($message, $context) use ($refId) {
                return str_contains($message, "[{$refId}]") && 
                       str_contains($message, "[/api/v1/test-error]");
            });
    }
}
