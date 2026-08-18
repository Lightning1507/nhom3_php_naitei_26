@extends('admin.layouts.app')

@section('title', 'Danh sách phòng ban')

@section('content')
    <div class="mb-5 flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-950">Phòng ban</h1>
            <p class="mt-1 text-sm text-gray-600">Cơ cấu phòng ban đang hoạt động trong phạm vi bạn được phép xem.</p>
        </div>

        @can('create', \App\Models\Department::class)
            <x-admin.button :href="route('admin.departments.create')">Tạo phòng ban</x-admin.button>
        @endcan
    </div>

    @if ($departments->isEmpty())
        <section class="admin-card" aria-labelledby="empty-departments-title">
            <div class="admin-card-body py-12 text-center">
                <h2 id="empty-departments-title" class="text-lg font-bold text-gray-950">Chưa có phòng ban hoạt động</h2>
                <p class="mx-auto mt-2 max-w-lg text-sm text-gray-600">
                    Phòng ban được tạo sẽ xuất hiện tại đây cùng thông tin lãnh đạo và số lượng thành viên.
                </p>
                @can('create', \App\Models\Department::class)
                    <x-admin.button class="mt-5" :href="route('admin.departments.create')">Tạo phòng ban đầu tiên</x-admin.button>
                @endcan
            </div>
        </section>
    @else
        <div class="admin-table-wrap">
            <table class="admin-table">
                <caption class="sr-only">Danh sách phòng ban đang hoạt động</caption>
                <thead>
                    <tr>
                        <th scope="col">Mã</th>
                        <th scope="col">Phòng ban</th>
                        <th scope="col">Lãnh đạo</th>
                        <th scope="col">Thành viên</th>
                        <th scope="col">Trạng thái</th>
                        <th scope="col"><span class="sr-only">Thao tác</span></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($departments as $department)
                        <tr>
                            <td class="whitespace-nowrap font-mono font-semibold text-primary">{{ $department->code }}</td>
                            <td>
                                <p class="font-semibold text-gray-950">{{ $department->name }}</p>
                                <p class="mt-1 max-w-md truncate text-xs text-gray-500">{{ $department->address ?: 'Chưa có địa chỉ' }}</p>
                            </td>
                            <td>
                                @if ($department->leader)
                                    <p class="font-medium">{{ $department->leader->name }}</p>
                                    @unless ($department->hasEligibleLeader())
                                        <x-admin.badge class="mt-1" variant="warning">Cần cập nhật</x-admin.badge>
                                    @endunless
                                @else
                                    <x-admin.badge variant="warning">Chưa có lãnh đạo</x-admin.badge>
                                @endif
                            </td>
                            <td>{{ $department->members_count }}</td>
                            <td><x-admin.badge variant="success">Hoạt động</x-admin.badge></td>
                            <td>
                                <div class="flex justify-end gap-2">
                                    <x-admin.button variant="ghost" :href="route('admin.departments.show', $department)">Chi tiết</x-admin.button>
                                    @can('update', $department)
                                        <x-admin.button variant="secondary" :href="route('admin.departments.edit', $department)">Sửa</x-admin.button>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if ($departments->hasPages())
            <div class="mt-5">{{ $departments->links() }}</div>
        @endif
    @endif
@endsection
