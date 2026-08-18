<?php

namespace App\Http\Controllers\Admin\Departments;

use App\Actions\Department\ChangeDepartmentLeader;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Departments\ChangeDepartmentLeaderRequest;
use App\Models\Department;
use App\Models\User;
use Illuminate\Http\RedirectResponse;

class DepartmentLeaderController extends Controller
{
    public function update(
        ChangeDepartmentLeaderRequest $request,
        Department $department,
        ChangeDepartmentLeader $changeDepartmentLeader,
    ): RedirectResponse {
        /** @var User $actor */
        $actor = $request->user();
        $validated = $request->validated();

        $changeDepartmentLeader->handle(
            $department,
            isset($validated['leader_id']) ? (int) $validated['leader_id'] : null,
            (int) $validated['version'],
            $actor,
            $request,
        );

        return redirect()
            ->route('admin.departments.show', $department)
            ->with('success', 'Đã cập nhật lãnh đạo phòng ban.');
    }
}
