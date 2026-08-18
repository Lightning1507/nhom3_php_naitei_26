@extends('admin.layouts.app')

@section('title', $department->name)

@section('content')
    <div class="mb-5 flex flex-wrap items-start justify-between gap-4">
        <div>
            <a class="text-sm font-semibold text-primary hover:underline" href="{{ route('admin.departments.index') }}">
                &larr; Danh sách phòng ban
            </a>
            <div class="mt-2 flex flex-wrap items-center gap-3">
                <h1 class="text-2xl font-bold text-gray-950">{{ $department->name }}</h1>
                <x-admin.badge :variant="$department->isArchived() ? 'neutral' : 'success'">
                    {{ $department->isArchived() ? 'Đã lưu trữ' : 'Hoạt động' }}
                </x-admin.badge>
            </div>
            <p class="mt-1 font-mono text-sm font-semibold text-primary">{{ $department->code }}</p>
        </div>

        @can('update', $department)
            <x-admin.button variant="secondary" :href="route('admin.departments.edit', $department)">Chỉnh sửa</x-admin.button>
        @endcan
    </div>

    @if ($department->leader_id && ! $department->hasEligibleLeader())
        <div class="mb-5 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900" role="status">
            Lãnh đạo hiện tại không còn đủ điều kiện hoạt động. Tham chiếu vẫn được giữ để tra cứu và cần được Super Admin cập nhật.
        </div>
    @endif

    <div class="grid gap-5 lg:grid-cols-3">
        <section class="admin-card lg:col-span-2" aria-labelledby="department-information-title">
            <div class="admin-card-body">
                <h2 id="department-information-title" class="text-lg font-bold text-gray-950">Thông tin phòng ban</h2>
                <dl class="mt-4 grid gap-4 sm:grid-cols-2">
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">Địa chỉ</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $department->address ?: 'Chưa cập nhật' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">Lãnh đạo</dt>
                        <dd class="mt-1 text-sm text-gray-900">
                            @if ($department->leader)
                                {{ $department->leader->name }}
                                <span class="block text-xs text-gray-500">{{ $department->leader->email }}</span>
                            @else
                                <x-admin.badge variant="warning">Chưa có lãnh đạo</x-admin.badge>
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">Ngày tạo</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $department->created_at?->format('d/m/Y H:i') }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">Phiên bản dữ liệu</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $department->lock_version }}</dd>
                    </div>
                </dl>
            </div>
        </section>

        <section class="admin-card" aria-labelledby="department-members-title">
            <div class="admin-card-body">
                <h2 id="department-members-title" class="text-lg font-bold text-gray-950">Thành viên</h2>
                <p class="mt-1 text-sm text-gray-500">{{ $department->members->count() }} quan hệ hiện tại</p>

                @if ($department->members->isEmpty())
                    <p class="mt-4 rounded-xl bg-gray-50 px-3 py-4 text-sm text-gray-600">Chưa có thành viên.</p>
                @else
                    <ul class="mt-4 divide-y divide-border">
                        @foreach ($department->members as $member)
                            <li class="py-3 first:pt-0 last:pb-0">
                                <div class="flex items-start justify-between gap-2">
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-semibold text-gray-950">{{ $member->name }}</p>
                                        <p class="truncate text-xs text-gray-500">{{ $member->email }}</p>
                                    </div>
                                    <x-admin.badge :variant="$member->isManager() ? 'manager' : 'staff'">
                                        {{ $member->isManager() ? 'Manager' : 'Staff' }}
                                    </x-admin.badge>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </section>
    </div>

    <section class="admin-card mt-5" aria-labelledby="department-services-title">
        <div class="admin-card-body">
            <div>
                <h2 id="department-services-title" class="text-lg font-bold text-gray-950">Dịch vụ liên kết</h2>
                <p class="mt-1 text-sm text-gray-500">Thông tin chỉ để tra cứu; F03 không cung cấp thao tác sửa dịch vụ.</p>
            </div>

            @if ($department->serviceTypes->isEmpty())
                <p class="mt-4 rounded-xl bg-gray-50 px-3 py-4 text-sm text-gray-600">Chưa có dịch vụ liên kết.</p>
            @else
                <div class="admin-table-wrap mt-4">
                    <table class="admin-table">
                        <caption class="sr-only">Dịch vụ do phòng ban phụ trách</caption>
                        <thead>
                            <tr>
                                <th scope="col">Mã</th>
                                <th scope="col">Tên dịch vụ</th>
                                <th scope="col">Trạng thái</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($department->serviceTypes as $serviceType)
                                <tr>
                                    <td class="font-mono font-semibold">{{ $serviceType->code }}</td>
                                    <td>{{ $serviceType->name }}</td>
                                    <td>
                                        <x-admin.badge :variant="$serviceType->is_active ? 'success' : 'neutral'">
                                            {{ $serviceType->is_active ? 'Hoạt động' : 'Không hoạt động' }}
                                        </x-admin.badge>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </section>
@endsection
