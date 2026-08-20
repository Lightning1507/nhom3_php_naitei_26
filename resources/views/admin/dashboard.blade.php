@extends('admin.layouts.app')

@section('title', 'Admin Dashboard')

@section('content')
    <section class="rounded-lg bg-white p-8 shadow-sm">
        <p class="text-sm font-medium uppercase tracking-wide text-sky-700">Internal site</p>
        <h1 class="mt-2 text-2xl font-semibold">Admin Dashboard</h1>
        <p class="mt-3 text-slate-600">
            The Laravel Blade SSR foundation is ready for Staff, Manager, and Super Admin features.
        </p>
        <div class="mt-6 flex flex-wrap gap-3">
            @can('viewAny', \App\Models\Application::class)
                <x-admin.button :href="route('admin.applications.index')">Quản lý hồ sơ dịch vụ công</x-admin.button>
            @endcan
            @can('viewAny', \App\Models\Department::class)
                <x-admin.button variant="secondary" :href="route('admin.departments.index')">Quản lý phòng ban</x-admin.button>
            @endcan
            @can('viewAny', \App\Models\ServiceCategory::class)
                <x-admin.button variant="secondary" :href="route('admin.service-categories.index')">Danh mục dịch vụ</x-admin.button>
            @endcan
            @can('viewAny', \App\Models\ServiceType::class)
                <x-admin.button variant="secondary" :href="route('admin.service-types.index')">Quản lý dịch vụ</x-admin.button>
            @endcan
        </div>
    </section>
@endsection
