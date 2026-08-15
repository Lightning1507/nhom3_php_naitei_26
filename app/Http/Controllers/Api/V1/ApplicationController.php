<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Application\CreateApplicationAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreApplicationRequest;
use App\Http\Resources\Api\V1\ApplicationResource;
use App\Http\Responses\ApiResponse;
use App\Models\Application;
use App\Models\ServiceType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApplicationController extends Controller
{
    public function store(StoreApplicationRequest $request, CreateApplicationAction $action): JsonResponse
    {
        $serviceType = ServiceType::query()->findOrFail($request->validated('service_type_id'));

        $application = $action->execute(
            $request->user(),
            $serviceType,
            $request->validated('form_data', []),
        );

        return ApiResponse::success(
            'Application submitted successfully',
            new ApplicationResource($application),
            201,
        );
    }

    public function index(Request $request): JsonResponse
    {
        $applications = Application::query()
            ->where('citizen_id', $request->user()->id)
            ->with(['serviceType'])
            ->latest('submitted_at')
            ->paginate((int) $request->integer('per_page', 15));

        $payload = ApplicationResource::collection($applications)->response()->getData();

        return ApiResponse::success(
            'Applications retrieved successfully',
            [
                'data' => $payload->data,
                'links' => $payload->links,
                'meta' => $payload->meta,
            ],
        );
    }

    public function show(Application $application): JsonResponse
    {
        $application->load(['serviceType']);

        return ApiResponse::success(
            'Application retrieved successfully',
            new ApplicationResource($application),
        );
    }
}
