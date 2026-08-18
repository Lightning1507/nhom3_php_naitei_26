@extends('admin.layouts.app')

@section('title', 'Tạo phòng ban')

@section('content')
    <div class="mx-auto max-w-3xl">
        <div class="mb-5">
            <a class="text-sm font-semibold text-primary hover:underline" href="{{ route('admin.departments.index') }}">
                &larr; Danh sách phòng ban
            </a>
            <h1 class="mt-2 text-2xl font-bold text-gray-950">Tạo phòng ban</h1>
            <p class="mt-1 text-sm text-gray-600">Khai báo thông tin nhận diện cơ bản. Lãnh đạo có thể được thiết lập sau.</p>
        </div>

        <section class="admin-card" aria-labelledby="department-form-title">
            <div class="admin-card-body">
                <h2 id="department-form-title" class="sr-only">Thông tin phòng ban mới</h2>
                @include('admin.departments.partials.form', [
                    'action' => route('admin.departments.store'),
                    'cancelUrl' => route('admin.departments.index'),
                    'submitLabel' => 'Tạo phòng ban',
                ])
            </div>
        </section>
    </div>
@endsection
