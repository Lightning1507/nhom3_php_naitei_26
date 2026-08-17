<?php

namespace Tests\Feature\Api\V1\Auth;

use App\Http\Responses\ApiResponse;
use Tests\TestCase;

class AuthResponseEnvelopeTest extends TestCase
{
    public function test_success_response_uses_standard_api_envelope(): void
    {
        $response = ApiResponse::success('Done.', ['id' => 1]);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame([
            'success' => true,
            'message' => 'Done.',
            'data' => ['id' => 1],
        ], $response->getData(true));
    }

    public function test_error_response_uses_standard_api_envelope(): void
    {
        $response = ApiResponse::error('Denied.', ['role' => ['Forbidden.']], 403);

        $this->assertSame(403, $response->getStatusCode());
        $this->assertSame([
            'success' => false,
            'message' => 'Denied.',
            'errors' => ['role' => ['Forbidden.']],
        ], $response->getData(true));
    }
}
