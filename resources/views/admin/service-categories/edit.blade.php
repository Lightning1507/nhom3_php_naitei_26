@extends('admin.layouts.app')

@section('title', 'Sửa danh mục dịch vụ')

@section('content')
    <div class="mx-auto max-w-3xl">
        <div class="mb-5">
            <a class="text-sm font-semibold text-primary hover:underline" href="{{ route('admin.service-categories.index') }}">
                &larr; Danh sách danh mục
            </a>
            <h1 class="mt-2 text-2xl font-bold text-gray-950">Sửa danh mục: {{ $serviceCategory->name }}</h1>
            <p class="mt-1 text-sm text-gray-600">Cập nhật thông tin chi tiết của danh mục.</p>
        </div>

        <section class="admin-card" aria-labelledby="category-form-title">
            <div class="admin-card-body">
                <h2 id="category-form-title" class="sr-only">Cập nhật thông tin danh mục</h2>
                @include('admin.service-categories.partials.form', [
                    'action' => route('admin.service-categories.update', $serviceCategory),
                    'method' => 'PUT',
                    'category' => $serviceCategory,
                    'cancelUrl' => route('admin.service-categories.index'),
                    'submitLabel' => 'Lưu thay đổi',
                ])
            </div>
        </section>
    </div>
@endsection
