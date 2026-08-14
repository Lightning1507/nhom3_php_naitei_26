<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\HealthCheckRequest;
use App\Http\Resources\Api\V1\HealthResource;
use App\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;

class HealthController extends Controller
{
    public function __invoke(HealthCheckRequest $request): JsonResponse
    {
        return ApiResponse::success(
            'API is running',
            HealthResource::make(['status' => 'ok'])->resolve($request),
        );
    }
}
