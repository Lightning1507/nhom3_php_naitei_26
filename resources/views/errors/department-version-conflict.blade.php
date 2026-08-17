@extends('admin.layouts.app')

@section('title', 'Dữ liệu phòng ban đã thay đổi')

@section('content')
    <section class="admin-card mx-auto max-w-2xl" aria-labelledby="conflict-title">
        <div class="admin-card-body">
            <p class="text-sm font-semibold uppercase tracking-wide text-amber-700">Xung đột dữ liệu</p>
            <h1 id="conflict-title" class="mt-2 text-2xl font-bold text-gray-950">
                Dữ liệu phòng ban đã được cập nhật
            </h1>
            <p class="mt-3 text-sm leading-6 text-gray-600">
                {{ $message }} Thay đổi chưa được lưu nên dữ liệu hiện tại vẫn an toàn.
            </p>

            <div class="mt-6 flex flex-wrap gap-3">
                <a class="admin-btn admin-btn-primary" href="{{ url()->current() }}">
                    Tải lại dữ liệu
                </a>
                <a class="admin-btn admin-btn-secondary" href="{{ url('/admin/departments') }}">
                    Về danh sách phòng ban
                </a>
            </div>
        </div>
    </section>
@endsection
