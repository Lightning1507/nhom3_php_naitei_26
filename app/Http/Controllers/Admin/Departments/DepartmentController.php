<?php

namespace App\Http\Controllers\Admin\Departments;

use App\Actions\Department\CreateDepartment;
use App\Actions\Department\UpdateDepartment;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Departments\StoreDepartmentRequest;
use App\Http\Requests\Admin\Departments\UpdateDepartmentRequest;
use App\Models\Department;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DepartmentController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Department::class);

        /** @var User $actor */
        $actor = $request->user();
        $departments = Department::query()
            ->visibleTo($actor)
            ->with('leader')
            ->withStructureCounts()
            ->orderBy('code')
            ->orderBy('id')
            ->paginate(15)
            ->withQueryString();

        return view('admin.departments.index', compact('departments'));
    }

    public function create(): View
    {
        $this->authorize('create', Department::class);

        return view('admin.departments.create');
    }

    public function store(
        StoreDepartmentRequest $request,
        CreateDepartment $createDepartment,
    ): RedirectResponse {
        /** @var User $actor */
        $actor = $request->user();
        $department = $createDepartment->handle($request->validated(), $actor, $request);

        return redirect()
            ->route('admin.departments.show', $department)
            ->with('success', 'Đã tạo phòng ban thành công.');
    }

    public function show(Department $department): View
    {
        $this->authorize('view', $department);

        $department->load([
            'leader',
            'members' => fn ($query) => $query->orderBy('name'),
            'serviceTypes' => fn ($query) => $query->orderBy('name'),
        ]);

        return view('admin.departments.show', compact('department'));
    }

    public function edit(Department $department): View
    {
        $this->authorize('update', $department);

        return view('admin.departments.edit', compact('department'));
    }

    public function update(
        UpdateDepartmentRequest $request,
        Department $department,
        UpdateDepartment $updateDepartment,
    ): RedirectResponse {
        /** @var User $actor */
        $actor = $request->user();
        $updatedDepartment = $updateDepartment->handle(
            $department,
            $request->validated(),
            $actor,
            $request,
        );

        return redirect()
            ->route('admin.departments.show', $updatedDepartment)
            ->with('success', 'Đã cập nhật thông tin phòng ban.');
    }
}
