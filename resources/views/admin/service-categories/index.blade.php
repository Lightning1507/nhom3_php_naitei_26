@extends('admin.layouts.app')

@section('title', 'Danh sách danh mục dịch vụ')

@section('content')
    <div class="mb-5 flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-950">Danh mục dịch vụ</h1>
            <p class="mt-1 text-sm text-gray-600">Quản lý các danh mục phân loại dịch vụ công.</p>
        </div>

        @can('create', \App\Models\ServiceCategory::class)
            <x-admin.button :href="route('admin.service-categories.create')">Tạo danh mục</x-admin.button>
        @endcan
    </div>

    @if ($categories->isEmpty())
        <section class="admin-card" aria-labelledby="empty-categories-title">
            <div class="admin-card-body py-12 text-center">
                <h2 id="empty-categories-title" class="text-lg font-bold text-gray-950">Chưa có danh mục nào</h2>
                <p class="mx-auto mt-2 max-w-lg text-sm text-gray-600">
                    Danh mục được tạo sẽ xuất hiện tại đây để sử dụng khi tạo dịch vụ công.
                </p>
                @can('create', \App\Models\ServiceCategory::class)
                    <x-admin.button class="mt-5" :href="route('admin.service-categories.create')">Tạo danh mục đầu tiên</x-admin.button>
                @endcan
            </div>
        </section>
    @else
        <div class="admin-table-wrap">
            <table class="admin-table">
                <caption class="sr-only">Danh sách danh mục dịch vụ</caption>
                <thead>
                    <tr>
                        <th scope="col">Mã</th>
                        <th scope="col">Tên danh mục</th>
                        <th scope="col">Số dịch vụ</th>
                        <th scope="col"><span class="sr-only">Thao tác</span></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($categories as $category)
                        <tr>
                            <td class="whitespace-nowrap font-mono font-semibold text-primary">{{ $category->code }}</td>
                            <td>
                                <p class="font-semibold text-gray-950">{{ $category->name }}</p>
                                @if($category->description)
                                    <p class="mt-1 max-w-md truncate text-xs text-gray-500">{{ $category->description }}</p>
                                @endif
                            </td>
                            <td>{{ $category->service_types_count ?? 0 }}</td>
                            <td>
                                <div class="flex justify-end gap-2">
                                    @can('update', $category)
                                        <x-admin.button variant="secondary" :href="route('admin.service-categories.edit', $category)">Sửa</x-admin.button>
                                    @endcan
                                    @can('delete', $category)
                                        <form method="POST" action="{{ route('admin.service-categories.destroy', $category) }}" onsubmit="return confirm('Bạn có chắc chắn muốn xóa danh mục này?');" class="inline-block">
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

        @if ($categories->hasPages())
            <div class="mt-5">{{ $categories->links() }}</div>
        @endif
    @endif
@endsection
