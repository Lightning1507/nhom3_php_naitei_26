@extends('admin.layouts.app')

@section('title', $serviceCategory->name)

@section('content')
    <div class="mb-5 flex flex-wrap items-start justify-between gap-4">
        <div>
            <a class="text-sm font-semibold text-primary hover:underline" href="{{ route('admin.service-categories.index') }}">
                &larr; Danh sách danh mục
            </a>
            <div class="mt-2 flex flex-wrap items-center gap-3">
                <h1 class="text-2xl font-bold text-gray-950">{{ $serviceCategory->name }}</h1>
            </div>
            <p class="mt-1 font-mono text-sm font-semibold text-primary">{{ strtoupper($serviceCategory->code) }}</p>
        </div>

        <div class="flex flex-wrap gap-2">
            @can('update', $serviceCategory)
                <x-admin.button variant="secondary" :href="route('admin.service-categories.edit', $serviceCategory)">Chỉnh sửa</x-admin.button>
            @endcan
        </div>
    </div>

    <section class="admin-card mb-5" aria-labelledby="category-information-title">
        <div class="admin-card-body">
            <h2 id="category-information-title" class="text-lg font-bold text-gray-950">Thông tin danh mục</h2>
            <dl class="mt-4 grid gap-4 sm:grid-cols-2">
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">Mã danh mục</dt>
                    <dd class="mt-1 text-sm text-gray-900 font-mono">{{ strtoupper($serviceCategory->code) }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">Tên danh mục</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $serviceCategory->name }}</dd>
                </div>
                <div class="sm:col-span-2">
                    <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">Mô tả</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $serviceCategory->description ?: 'Không có mô tả' }}</dd>
                </div>
            </dl>
        </div>
    </section>

    <section class="admin-card" aria-labelledby="category-services-title">
        <div class="admin-card-body">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <h2 id="category-services-title" class="text-lg font-bold text-gray-950">Dịch vụ trực thuộc</h2>
                    <p class="mt-1 text-sm text-gray-500">{{ $serviceCategory->serviceTypes->count() }} dịch vụ</p>
                </div>
                @can('create', \App\Models\ServiceType::class)
                    <x-admin.button variant="secondary" :href="route('admin.service-types.create', ['category_id' => $serviceCategory->id])">Thêm dịch vụ</x-admin.button>
                @endcan
            </div>

            @if ($serviceCategory->serviceTypes->isEmpty())
                <p class="mt-4 rounded-xl bg-gray-50 px-3 py-4 text-sm text-gray-600">Chưa có dịch vụ nào thuộc danh mục này.</p>
            @else
                <div class="admin-table-wrap mt-4">
                    <table class="admin-table">
                        <caption class="sr-only">Danh sách dịch vụ</caption>
                        <thead>
                            <tr>
                                <th scope="col">Mã</th>
                                <th scope="col">Tên dịch vụ</th>
                                <th scope="col">Phòng ban</th>
                                <th scope="col">Phí</th>
                                <th scope="col">Trạng thái</th>
                                <th scope="col"><span class="sr-only">Thao tác</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($serviceCategory->serviceTypes as $service)
                                <tr>
                                    <td class="whitespace-nowrap font-mono font-semibold text-primary">{{ $service->code }}</td>
                                    <td>
                                        <p class="font-semibold text-gray-950">{{ $service->name }}</p>
                                    </td>
                                    <td>{{ $service->responsibleDepartment?->name ?: 'Chưa phân công' }}</td>
                                    <td class="whitespace-nowrap">{{ number_format($service->fee) }} đ</td>
                                    <td>
                                        @if($service->is_active)
                                            <x-admin.badge variant="success">Hoạt động</x-admin.badge>
                                        @else
                                            <x-admin.badge variant="warning">Tạm ngưng</x-admin.badge>
                                        @endif
                                    </td>
                                    <td class="whitespace-nowrap">
                                        <div class="flex justify-end gap-2">
                                            <x-admin.button variant="ghost" :href="route('admin.service-types.show', $service)">Chi tiết</x-admin.button>
                                        </div>
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
