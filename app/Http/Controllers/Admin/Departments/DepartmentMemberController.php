<?php

namespace App\Http\Controllers\Admin\Departments;

use App\Actions\Department\AddDepartmentMember;
use App\Actions\Department\RemoveDepartmentMember;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Departments\DestroyDepartmentMemberRequest;
use App\Http\Requests\Admin\Departments\StoreDepartmentMemberRequest;
use App\Models\Department;
use App\Models\User;
use Illuminate\Http\RedirectResponse;

class DepartmentMemberController extends Controller
{
    public function store(
        StoreDepartmentMemberRequest $request,
        Department $department,
        AddDepartmentMember $addDepartmentMember,
    ): RedirectResponse {
        /** @var User $actor */
        $actor = $request->user();
        $validated = $request->validated();

        $addDepartmentMember->handle(
            $department,
            (int) $validated['user_id'],
            (int) $validated['version'],
            $actor,
            $request,
        );

        return redirect()
            ->route('admin.departments.show', $department)
            ->with('success', 'Đã thêm thành viên vào phòng ban.');
    }

    public function destroy(
        DestroyDepartmentMemberRequest $request,
        Department $department,
        User $member,
        RemoveDepartmentMember $removeDepartmentMember,
    ): RedirectResponse {
        /** @var User $actor */
        $actor = $request->user();

        $removeDepartmentMember->handle(
            $department,
            $member,
            (int) $request->validated('version'),
            $actor,
            $request,
        );

        return redirect()
            ->route('admin.departments.show', $department)
            ->with('success', 'Đã gỡ thành viên khỏi phòng ban. Tài khoản người dùng vẫn được giữ nguyên.');
    }
}
