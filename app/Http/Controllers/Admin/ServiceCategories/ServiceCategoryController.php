<?php

namespace App\Http\Controllers\Admin\ServiceCategories;

use App\Actions\ServiceCategory\CreateServiceCategory;
use App\Actions\ServiceCategory\UpdateServiceCategory;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ServiceCategories\StoreServiceCategoryRequest;
use App\Http\Requests\Admin\ServiceCategories\UpdateServiceCategoryRequest;
use App\Models\ServiceCategory;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ServiceCategoryController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', ServiceCategory::class);

        /** @var User $actor */
        $actor = $request->user();
        $categories = ServiceCategory::query()
            ->withCount('serviceTypes')
            ->orderBy('code')
            ->orderBy('id')
            ->paginate(15)
            ->withQueryString();

        return view('admin.service-categories.index', compact('categories'));
    }

    public function create(): View
    {
        $this->authorize('create', ServiceCategory::class);

        return view('admin.service-categories.create');
    }

    public function show(ServiceCategory $serviceCategory): View
    {
        $this->authorize('view', $serviceCategory);

        $serviceCategory->load('serviceTypes');

        return view('admin.service-categories.show', compact('serviceCategory'));
    }

    public function store(
        StoreServiceCategoryRequest $request,
        CreateServiceCategory $createServiceCategory,
    ): RedirectResponse {
        /** @var User $actor */
        $actor = $request->user();
        $category = $createServiceCategory->handle($request->validated(), $actor, $request);

        return redirect()
            ->route('admin.service-categories.index')
            ->with('success', 'Đã tạo danh mục dịch vụ thành công.');
    }

    public function edit(ServiceCategory $serviceCategory): View
    {
        $this->authorize('update', $serviceCategory);

        return view('admin.service-categories.edit', compact('serviceCategory'));
    }

    public function update(
        UpdateServiceCategoryRequest $request,
        ServiceCategory $serviceCategory,
        UpdateServiceCategory $updateServiceCategory,
    ): RedirectResponse {
        /** @var User $actor */
        $actor = $request->user();
        $updatedCategory = $updateServiceCategory->handle(
            $serviceCategory,
            $request->validated(),
            $actor,
            $request,
        );

        return redirect()
            ->route('admin.service-categories.index')
            ->with('success', 'Đã cập nhật thông tin danh mục dịch vụ.');
    }

    public function destroy(Request $request, ServiceCategory $serviceCategory): RedirectResponse
    {
        $this->authorize('delete', $serviceCategory);

        if ($serviceCategory->serviceTypes()->exists()) {
            return back()->with('error', 'Không thể xóa danh mục đang có dịch vụ liên kết.');
        }

        $serviceCategory->delete();

        return redirect()
            ->route('admin.service-categories.index')
            ->with('success', 'Đã xóa danh mục dịch vụ.');
    }
}
