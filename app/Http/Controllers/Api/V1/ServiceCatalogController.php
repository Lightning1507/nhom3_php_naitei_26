<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\ServiceTypeResource;
use App\Models\ServiceCategory;
use App\Models\ServiceType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ServiceCatalogController extends Controller
{
    /**
     * Display a listing of the active public services.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = ServiceType::query()
            ->with('category')
            ->where('is_active', true);

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ilike', '%'.$search.'%')
                    ->orWhere('description', 'ilike', '%'.$search.'%');
            });
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->input('category_id'));
        }

        $services = $query->paginate($request->input('per_page', 15));

        return ServiceTypeResource::collection($services);
    }

    /**
     * Display the specified public service details.
     */
    public function show(ServiceType $service): ServiceTypeResource
    {
        $service->load('category');

        return new ServiceTypeResource($service);
    }

    /**
     * Display a listing of categories for the catalog sidebar.
     */
    public function categories(): JsonResponse
    {
        $categories = ServiceCategory::select('id', 'name', 'code', 'description')->get();

        return response()->json(['data' => $categories]);
    }
}
