@extends('admin.layouts.app')

@section('title', 'Quản lý Tài khoản - Admin')

@section('content')
<div class="space-y-6" x-data="{ showImportModal: false }">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Quản lý Tài khoản Người dùng</h1>
            <p class="mt-1 text-sm text-slate-500">Danh sách tất cả tài khoản Công dân (Citizen) và Cán bộ (Staff/Manager) trong hệ thống.</p>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <a href="{{ route('admin.users.import') }}" class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-emerald-700 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path>
                </svg>
                Import CSV
            </a>
        </div>
    </div>

    {{-- Filter Bar --}}
    <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
        <form method="GET" class="flex flex-col sm:flex-row gap-3">
            <div class="flex-1">
                <input
                    type="text"
                    name="search"
                    value="{{ request('search') }}"
                    placeholder="Tìm theo tên, email, CCCD, sđt..."
                    class="w-full rounded-lg border border-slate-300 px-3.5 py-2 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                >
            </div>
            <div>
                <select name="role" class="w-full rounded-lg border border-slate-300 px-3.5 py-2 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary">
                    <option value="">-- Tất cả vai trò --</option>
                    <option value="citizen" {{ request('role') === 'citizen' ? 'selected' : '' }}>Công dân (Citizen)</option>
                    <option value="staff" {{ request('role') === 'staff' ? 'selected' : '' }}>Cán bộ (Staff)</option>
                    <option value="manager" {{ request('role') === 'manager' ? 'selected' : '' }}>Trưởng phòng (Manager)</option>
                </select>
            </div>
            <button type="submit" class="rounded-lg bg-slate-800 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-900 transition">
                Lọc
            </button>
        </form>
    </div>

    {{-- Users Table --}}
    <div class="overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-sm">
        <table class="w-full text-left text-sm text-slate-600">
            <thead class="bg-slate-50 text-xs font-semibold uppercase text-slate-700 border-b border-slate-200">
                <tr>
                    <th class="px-6 py-3">Họ và Tên</th>
                    <th class="px-6 py-3">Email</th>
                    <th class="px-6 py-3">Vai trò</th>
                    <th class="px-6 py-3">CCCD / Mã định danh</th>
                    <th class="px-6 py-3">Số Điện Thoại</th>
                    <th class="px-6 py-3">Trạng thái</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200">
                @forelse($users ?? [] as $user)
                    <tr class="hover:bg-slate-50 transition">
                        <td class="px-6 py-4 font-semibold text-slate-900">{{ $user->name }}</td>
                        <td class="px-6 py-4">{{ $user->email }}</td>
                        <td class="px-6 py-4">
                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium
                                {{ $user->role->value === 'citizen' ? 'bg-blue-100 text-blue-800' : '' }}
                                {{ $user->role->value === 'staff' ? 'bg-purple-100 text-purple-800' : '' }}
                                {{ $user->role->value === 'manager' ? 'bg-amber-100 text-amber-800' : '' }}
                                {{ $user->role->value === 'super_admin' ? 'bg-emerald-100 text-emerald-800' : '' }}
                            ">
                                {{ strtoupper($user->role->value) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 font-mono text-xs">{{ $user->citizen_id ?? '-' }}</td>
                        <td class="px-6 py-4">{{ $user->phone ?? '-' }}</td>
                        <td class="px-6 py-4">
                            @if($user->is_active)
                                <span class="text-xs font-semibold text-emerald-700 bg-emerald-50 px-2 py-1 rounded">Hoạt động</span>
                            @else
                                <span class="text-xs font-semibold text-red-700 bg-red-50 px-2 py-1 rounded">Khóa</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-8 text-center text-slate-400">
                            Chưa có dữ liệu người dùng hiển thị. Vui lòng bấm <a href="{{ route('admin.users.import') }}" class="text-primary font-semibold underline">Import CSV</a> để nạp tài khoản.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
