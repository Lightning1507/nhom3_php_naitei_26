@extends('admin.layouts.app')

@section('title', 'Danh sách dịch vụ công')

@section('content')
    <div class="mb-5 flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-950">Dịch vụ công</h1>
            <p class="mt-1 text-sm text-gray-600">Quản lý các dịch vụ công được cung cấp trên hệ thống.</p>
        </div>

        @can('create', \App\Models\ServiceType::class)
            <x-admin.button :href="route('admin.service-types.create')">Tạo dịch vụ</x-admin.button>
        @endcan
    </div>

    <!-- Filters -->
    <div class="mb-5 rounded-xl border border-border bg-white p-4">
        <form action="{{ route('admin.service-types.index') }}" method="GET" class="flex flex-wrap items-end gap-4">
            <div class="w-full sm:w-64">
                <label for="search" class="mb-1.5 block text-sm font-medium text-gray-700">Tìm kiếm</label>
                <input type="text" name="search" id="search" value="{{ request('search') }}" class="admin-input" placeholder="Mã hoặc Tên dịch vụ...">
            </div>
            
            <div class="w-full sm:w-64">
                <label for="category" class="mb-1.5 block text-sm font-medium text-gray-700">Danh mục</label>
                <select name="category" id="category" class="admin-select">
                    <option value="">Tất cả danh mục</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}" @selected(request('category') == $cat->id)>{{ $cat->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <x-admin.button type="submit">Lọc</x-admin.button>
                @if(request()->hasAny(['search', 'category']))
                    <x-admin.button variant="ghost" :href="route('admin.service-types.index')" class="ml-2">Xóa lọc</x-admin.button>
                @endif
            </div>
        </form>
    </div>

    @if ($serviceTypes->isEmpty())
        <section class="admin-card" aria-labelledby="empty-services-title">
            <div class="admin-card-body py-12 text-center">
                <h2 id="empty-services-title" class="text-lg font-bold text-gray-950">Chưa có dịch vụ nào</h2>
                <p class="mx-auto mt-2 max-w-lg text-sm text-gray-600">
                    Dịch vụ được tạo sẽ hiển thị tại đây.
                </p>
                @can('create', \App\Models\ServiceType::class)
                    <x-admin.button class="mt-5" :href="route('admin.service-types.create')">Tạo dịch vụ đầu tiên</x-admin.button>
                @endcan
            </div>
        </section>
    @else
        <div class="admin-table-wrap">
            <table class="admin-table">
                <caption class="sr-only">Danh sách dịch vụ</caption>
                <thead>
                    <tr>
                        <th scope="col">Mã</th>
                        <th scope="col">Tên dịch vụ</th>
                        <th scope="col">Danh mục</th>
                        <th scope="col">Phòng ban</th>
                        <th scope="col">Phí</th>
                        <th scope="col">Trạng thái</th>
                        <th scope="col"><span class="sr-only">Thao tác</span></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($serviceTypes as $service)
                        <tr>
                            <td class="whitespace-nowrap font-mono font-semibold text-primary">{{ $service->code }}</td>
                            <td>
                                <p class="font-semibold text-gray-950">{{ $service->name }}</p>
                            </td>
                            <td>{{ $service->category->name }}</td>
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
                                    @can('update', $service)
                                        <x-admin.button variant="secondary" :href="route('admin.service-types.edit', $service)">Sửa</x-admin.button>
                                    @endcan
                                    @can('delete', $service)
                                        <form method="POST" action="{{ route('admin.service-types.destroy', $service) }}" onsubmit="return confirm('Bạn có chắc chắn muốn xóa dịch vụ này?');" class="inline-block">
                                            @csrf
                                            @method('DELETE')
                                            <x-admin.button type="submit" variant="secondary" class="!text-danger">Xóa</x-admin.button>
                                        </form>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if ($serviceTypes->hasPages())
            <div class="mt-5">{{ $serviceTypes->links() }}</div>
        @endif
    @endif
@endsection
