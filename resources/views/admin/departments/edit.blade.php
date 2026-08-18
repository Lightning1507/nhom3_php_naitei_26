@extends('admin.layouts.app')

@section('title', 'Chỉnh sửa '.$department->name)

@section('content')
    <div class="mx-auto max-w-3xl">
        <div class="mb-5">
            <a class="text-sm font-semibold text-primary hover:underline" href="{{ route('admin.departments.show', $department) }}">
                &larr; Chi tiết phòng ban
            </a>
            <h1 class="mt-2 text-2xl font-bold text-gray-950">Chỉnh sửa phòng ban</h1>
            <p class="mt-1 text-sm text-gray-600">
                Cập nhật thông tin nhận diện của <span class="font-semibold">{{ $department->code }}</span>.
            </p>
        </div>

        <section class="admin-card" aria-labelledby="department-form-title">
            <div class="admin-card-body">
                <h2 id="department-form-title" class="sr-only">Thông tin chỉnh sửa</h2>
                @include('admin.departments.partials.form', [
                    'action' => route('admin.departments.update', $department),
                    'cancelUrl' => route('admin.departments.show', $department),
                    'submitLabel' => 'Lưu thay đổi',
                ])
            </div>
        </section>
    </div>
@endsection
