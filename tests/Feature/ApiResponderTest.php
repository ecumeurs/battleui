<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Traits\ApiResponder;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ApiResponderTest extends TestCase
{
    /**
     * Helper class to use the trait
     */
    private $responder;

    protected function setUp(): void
    {
        parent::setUp();
        $this->responder = new class {
            use ApiResponder;
            // Expose protected methods for testing
            public function testSuccess(...$args) { return $this->success(...$args); }
            public function testError(...$args) { return $this->error(...$args); }
            public function testResolveRequestId() { return $this->resolveRequestId(); }
        };
    }

    /** @test */
    public function it_returns_standard_success_envelope()
    {
        $data = ['foo' => 'bar'];
        $response = $this->responder->testSuccess($data, 'Test Message');
        $content = json_decode($response->getContent(), true);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertTrue($content['success']);
        $this->assertEquals('Test Message', $content['message']);
        $this->assertEquals($data, $content['data']);
        $this->assertArrayHasKey('request_id', $content);
        $this->assertArrayHasKey('meta', $content);
        $this->assertEquals([], $content['meta']);
    }

    /** @test */
    public function it_returns_standard_error_envelope()
    {
        $details = ['error' => 'specific detail'];
        $response = $this->responder->testError('Error Message', 400, $details);
        $content = json_decode($response->getContent(), true);

        $this->assertEquals(400, $response->getStatusCode());
        $this->assertFalse($content['success']);
        $this->assertEquals('Error Message', $content['message']);
        $this->assertEquals($details, $content['data']);
        $this->assertArrayHasKey('request_id', $content);
    }

    /** @test */
    public function it_resolves_request_id_from_payload()
    {
        $requestId = (string) Str::uuid7();
        $request = Request::create('/test', 'POST', ['request_id' => $requestId]);
        $this->app->instance('request', $request);

        $resolvedId = $this->responder->testResolveRequestId();
        $this->assertEquals($requestId, $resolvedId);
    }

    /** @test */
    public function it_resolves_request_id_from_header()
    {
        $requestId = (string) Str::uuid7();
        $request = Request::create('/test', 'GET');
        $request->headers->set('X-Request-ID', $requestId);
        $this->app->instance('request', $request);

        $resolvedId = $this->responder->testResolveRequestId();
        $this->assertEquals($requestId, $resolvedId);
    }

    /** @test */
    public function it_generates_fresh_uuid7_if_missing()
    {
        $request = Request::create('/test', 'GET');
        $this->app->instance('request', $request);

        $resolvedId = $this->responder->testResolveRequestId();
        $this->assertTrue(Str::isUuid($resolvedId));
        // Simple check for UUIDv7 (timestamp based)
        $this->assertEquals('7', $resolvedId[14]);
    }
}
