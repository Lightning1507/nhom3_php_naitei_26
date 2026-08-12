<?php

namespace Tests\Feature\Api\V1;

use Tests\TestCase;

class HealthCheckTest extends TestCase
{
    public function test_health_endpoint_returns_the_standard_api_response(): void
    {
        $response = $this->getJson('/api/v1/health');

        $response
            ->assertOk()
            ->assertExactJson([
                'success' => true,
                'message' => 'API is running',
                'data' => [
                    'status' => 'ok',
                ],
            ]);
    }
}
