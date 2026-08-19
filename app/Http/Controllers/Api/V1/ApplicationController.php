<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Application\CreateApplicationAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreApplicationRequest;
use App\Http\Resources\Api\V1\ApplicationResource;
use App\Http\Responses\ApiResponse;
use App\Models\Application;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApplicationController extends Controller
{
    public function store(StoreApplicationRequest $request, CreateApplicationAction $action): JsonResponse
    {
        $this->authorize('create', Application::class);

        $application = $action->execute(
            $request->user(),
            $request->serviceType(),
            $request->validated('form_data', []),
        );

        $application->load(['serviceType', 'documents']);

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
            ->paginate(min(max((int) $request->integer('per_page', 15), 1), 100));

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
        $this->authorize('view', $application);

        $application->load(['serviceType', 'documents']);

        return ApiResponse::success(
            'Application retrieved successfully',
            new ApplicationResource($application),
        );
    }
}
