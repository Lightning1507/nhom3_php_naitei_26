@extends('admin.layouts.app')

@section('title', 'Tạo danh mục dịch vụ')

@section('content')
    <div class="mx-auto max-w-3xl">
        <div class="mb-5">
            <a class="text-sm font-semibold text-primary hover:underline" href="{{ route('admin.service-categories.index') }}">
                &larr; Danh sách danh mục
            </a>
            <h1 class="mt-2 text-2xl font-bold text-gray-950">Tạo danh mục</h1>
            <p class="mt-1 text-sm text-gray-600">Thêm mới một danh mục dịch vụ công vào hệ thống.</p>
        </div>

        <section class="admin-card" aria-labelledby="category-form-title">
            <div class="admin-card-body">
                <h2 id="category-form-title" class="sr-only">Thông tin danh mục mới</h2>
                @include('admin.service-categories.partials.form', [
                    'action' => route('admin.service-categories.store'),
                    'cancelUrl' => route('admin.service-categories.index'),
                    'submitLabel' => 'Tạo danh mục',
                ])
            </div>
        </section>
    </div>
@endsection
