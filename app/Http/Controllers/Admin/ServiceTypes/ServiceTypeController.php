<?php

namespace App\Http\Controllers\Admin\ServiceTypes;

use App\Actions\ServiceType\CreateServiceType;
use App\Actions\ServiceType\UpdateServiceType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ServiceTypes\StoreServiceTypeRequest;
use App\Http\Requests\Admin\ServiceTypes\UpdateServiceTypeRequest;
use App\Models\Department;
use App\Models\ServiceCategory;
use App\Models\ServiceType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ServiceTypeController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', ServiceType::class);

        $query = ServiceType::query()
            ->with(['category', 'responsibleDepartment']);

        if ($request->filled('category')) {
            $query->where('category_id', $request->category);
        }

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'ilike', '%'.$request->search.'%')
                    ->orWhere('code', 'ilike', '%'.$request->search.'%');
            });
        }

        $serviceTypes = $query->orderBy('category_id')
            ->orderBy('id')
            ->paginate(15)
            ->withQueryString();

        $categories = ServiceCategory::orderBy('name')->get();

        return view('admin.service-types.index', compact('serviceTypes', 'categories'));
    }

    public function create(): View
    {
        $this->authorize('create', ServiceType::class);

        $categories = ServiceCategory::orderBy('name')->get();
        $departments = Department::orderBy('name')->get();

        return view('admin.service-types.create', compact('categories', 'departments'));
    }

    public function store(
        StoreServiceTypeRequest $request,
        CreateServiceType $createServiceType,
    ): RedirectResponse {
        $actor = $request->user();
        $createServiceType->handle($request->validated(), $actor, $request);

        return redirect()
            ->route('admin.service-types.index')
            ->with('success', 'Đã tạo dịch vụ công mới thành công.');
    }

    public function edit(ServiceType $serviceType): View
    {
        $this->authorize('update', $serviceType);

        $categories = ServiceCategory::orderBy('name')->get();
        $departments = Department::orderBy('name')->get();

        return view('admin.service-types.edit', compact('serviceType', 'categories', 'departments'));
    }

    public function show(ServiceType $serviceType): View
    {
        $this->authorize('view', $serviceType);

        $serviceType->load(['category', 'responsibleDepartment']);

        return view('admin.service-types.show', compact('serviceType'));
    }

    public function update(
        UpdateServiceTypeRequest $request,
        ServiceType $serviceType,
        UpdateServiceType $updateServiceType,
    ): RedirectResponse {
        $actor = $request->user();
        $updateServiceType->handle($serviceType, $request->validated(), $actor, $request);

        return redirect()
            ->route('admin.service-types.index')
            ->with('success', 'Đã cập nhật thông tin dịch vụ.');
    }

    public function destroy(Request $request, ServiceType $serviceType): RedirectResponse
    {
        $this->authorize('delete', $serviceType);

        $serviceType->delete();

        return redirect()
            ->route('admin.service-types.index')
            ->with('success', 'Đã xóa dịch vụ.');
    }
}
