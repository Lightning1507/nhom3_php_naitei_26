@extends('admin.layouts.app')

@section('title', 'Import Tài khoản từ CSV - Admin')

@section('content')
<div class="space-y-6" x-data="{ activeTab: '{{ session('import_type', 'citizen') }}' }">
    <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Import Tài khoản từ CSV</h1>
            <p class="mt-1 text-sm text-slate-500">Thêm hàng loạt tài khoản Công dân hoặc Cán bộ hệ thống từ tệp dữ liệu CSV.</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.dashboard') }}" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50">
                Quay lại Dashboard
            </a>
        </div>
    </div>

    {{-- Import Result Report Card --}}
    @if (session('report'))
        @php
            $report = session('report');
            $data = $report['data'] ?? [];
            $total = $data['total_rows'] ?? 0;
            $success = $data['success_count'] ?? 0;
            $failure = $data['failure_count'] ?? 0;
            $errors = $data['errors'] ?? [];
        @endphp
        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-bold text-slate-900 flex items-center gap-2">
                <span>📊 Báo cáo kết quả Import</span>
                <span class="text-xs px-2.5 py-1 rounded-full font-semibold {{ $failure > 0 ? 'bg-amber-100 text-amber-800' : 'bg-emerald-100 text-emerald-800' }}">
                    {{ session('import_type') === 'staff' ? 'Tài khoản Staff' : 'Tài khoản Citizen' }}
                </span>
            </h2>

            <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-3">
                <div class="rounded-lg bg-slate-50 p-4 border border-slate-100">
                    <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Tổng số bản ghi</span>
                    <p class="mt-1 text-2xl font-extrabold text-slate-800">{{ $total }}</p>
                </div>
                <div class="rounded-lg bg-emerald-50 p-4 border border-emerald-100">
                    <span class="text-xs font-semibold text-emerald-600 uppercase tracking-wider">Thành công</span>
                    <p class="mt-1 text-2xl font-extrabold text-emerald-700">{{ $success }}</p>
                </div>
                <div class="rounded-lg bg-red-50 p-4 border border-red-100">
                    <span class="text-xs font-semibold text-red-600 uppercase tracking-wider">Thất bại / Lỗi</span>
                    <p class="mt-1 text-2xl font-extrabold text-red-700">{{ $failure }}</p>
                </div>
            </div>

            @if ($failure > 0 && !empty($errors))
                <div class="mt-6">
                    <h3 class="text-sm font-bold text-red-800 mb-2">Chi tiết các dòng bị lỗi ({{ count($errors) }}):</h3>
                    <div class="overflow-x-auto rounded-lg border border-slate-200">
                        <table class="w-full text-left text-sm text-slate-600">
                            <thead class="bg-slate-100 text-xs font-semibold uppercase text-slate-700">
                                <tr>
                                    <th class="px-4 py-3">Số Dòng CSV</th>
                                    <th class="px-4 py-3">Trường Dữ Liệu</th>
                                    <th class="px-4 py-3">Nguyên Nhân Lỗi</th>
                                    <th class="px-4 py-3">Dữ Liệu Gốc</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200 bg-white">
                                @foreach ($errors as $err)
                                    <tr class="hover:bg-red-50/50 transition">
                                        <td class="px-4 py-3 font-mono font-bold text-red-600">Dòng {{ $err['line_number'] ?? 'N/A' }}</td>
                                        <td class="px-4 py-3 font-semibold text-slate-900">{{ $err['field'] ?? 'General' }}</td>
                                        <td class="px-4 py-3 text-red-700">{{ $err['message'] ?? '' }}</td>
                                        <td class="px-4 py-3 font-mono text-xs text-slate-500 max-w-xs truncate">
                                            @if(isset($err['raw_data']) && is_array($err['raw_data']))
                                                {{ json_encode($err['raw_data'], JSON_UNESCAPED_UNICODE) }}
                                            @else
                                                -
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>
    @endif

    {{-- Tabs Selection --}}
    <div class="border-b border-slate-200">
        <nav class="-mb-px flex space-x-8" aria-label="Tabs">
            <button
                @click="activeTab = 'citizen'"
                :class="activeTab === 'citizen' ? 'border-primary text-primary font-bold' : 'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-700'"
                class="whitespace-nowrap border-b-2 py-4 px-1 text-sm font-medium transition"
            >
                👥 Import Công dân (Citizen)
            </button>

            <button
                @click="activeTab = 'staff'"
                :class="activeTab === 'staff' ? 'border-primary text-primary font-bold' : 'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-700'"
                class="whitespace-nowrap border-b-2 py-4 px-1 text-sm font-medium transition"
            >
                👔 Import Cán bộ (Staff / Manager)
            </button>
        </nav>
    </div>

    {{-- Citizen Import Form --}}
    <div x-show="activeTab === 'citizen'" class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        <h2 class="text-lg font-bold text-slate-900">Import Danh sách Công dân (Citizen)</h2>
        <p class="mt-1 text-sm text-slate-500">Tải lên tệp CSV chứa tài khoản Công dân. Hệ thống sẽ tự động xác thực và tạo tài khoản.</p>

        <form action="{{ route('admin.users.import.citizens') }}" method="POST" enctype="multipart/form-data" class="mt-6 space-y-5">
            @csrf
            <div>
                <label for="csv_file_citizen" class="block text-sm font-medium text-slate-700">Chọn tệp dữ liệu CSV (*.csv)</label>
                <input
                    type="file"
                    id="csv_file_citizen"
                    name="csv_file"
                    accept=".csv,text/csv"
                    required
                    class="mt-2 block w-full text-sm text-slate-500 file:mr-4 file:rounded-lg file:border-0 file:bg-primary/10 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-primary hover:file:bg-primary/20 cursor-pointer border border-slate-300 rounded-lg p-1.5"
                >
                @error('csv_file')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="rounded-lg bg-slate-50 p-4 border border-slate-200">
                <h3 class="text-xs font-bold text-slate-700 uppercase tracking-wider">Cấu trúc cột mẫu trong CSV Citizen:</h3>
                <code class="mt-2 block rounded bg-slate-900 p-3 text-xs text-emerald-400 font-mono overflow-x-auto">
                    name,email,citizen_id,phone,address,date_of_birth<br>
                    Nguyễn Văn A,nguyenvana@gmail.com,001098123456,0987654321,"123 Lê Lợi, Hà Nội",1990-05-15
                </code>
                <ul class="mt-2 text-xs text-slate-600 list-disc list-inside space-y-1">
                    <li><span class="font-semibold text-slate-800">name</span>: Bắt buộc, tối đa 255 ký tự.</li>
                    <li><span class="font-semibold text-slate-800">email</span>: Bắt buộc, định dạng email, không được trùng lặp.</li>
                    <li><span class="font-semibold text-slate-800">citizen_id</span>: Bắt buộc, đúng 12 chữ số CCCD, không được trùng lặp.</li>
                    <li><span class="font-semibold text-slate-800">phone</span>: Tùy chọn, 10-11 chữ số.</li>
                    <li><span class="font-semibold text-slate-800">date_of_birth</span>: Tùy chọn, định dạng YYYY-MM-DD.</li>
                </ul>
            </div>

            <div class="flex justify-end">
                <button type="submit" class="rounded-lg bg-primary px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-primary-dark transition">
                    🚀 Tải lên & Nạp Citizen
                </button>
            </div>
        </form>
    </div>

    {{-- Staff Import Form --}}
    <div x-show="activeTab === 'staff'" class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm" x-cloak>
        <h2 class="text-lg font-bold text-slate-900">Import Danh sách Cán bộ (Staff / Manager)</h2>
        <p class="mt-1 text-sm text-slate-500">Tải lên tệp CSV chứa danh sách Cán bộ và Trưởng phòng. Phân quyền và gán vào Phòng ban tương ứng.</p>

        <form action="{{ route('admin.users.import.staff') }}" method="POST" enctype="multipart/form-data" class="mt-6 space-y-5">
            @csrf
            <div>
                <label for="csv_file_staff" class="block text-sm font-medium text-slate-700">Chọn tệp dữ liệu CSV (*.csv)</label>
                <input
                    type="file"
                    id="csv_file_staff"
                    name="csv_file"
                    accept=".csv,text/csv"
                    required
                    class="mt-2 block w-full text-sm text-slate-500 file:mr-4 file:rounded-lg file:border-0 file:bg-primary/10 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-primary hover:file:bg-primary/20 cursor-pointer border border-slate-300 rounded-lg p-1.5"
                >
                @error('csv_file')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="rounded-lg bg-slate-50 p-4 border border-slate-200">
                    <h3 class="text-xs font-bold text-slate-700 uppercase tracking-wider">Cấu trúc cột mẫu trong CSV Staff:</h3>
                    <code class="mt-2 block rounded bg-slate-900 p-3 text-xs text-emerald-400 font-mono overflow-x-auto">
                        name,email,department_id,role,phone<br>
                        Trần Thị B,tranthib@gov.vn,1,staff,0912345678<br>
                        Phạm Văn C,phamvanc@gov.vn,2,manager,0988776655
                    </code>
                    <ul class="mt-2 text-xs text-slate-600 list-disc list-inside space-y-1">
                        <li><span class="font-semibold text-slate-800">department_id</span>: ID Phòng ban hợp lệ.</li>
                        <li><span class="font-semibold text-slate-800">role</span>: Nhận giá trị <code class="bg-slate-200 px-1 py-0.5 rounded">staff</code> hoặc <code class="bg-slate-200 px-1 py-0.5 rounded">manager</code>.</li>
                    </ul>
                </div>

                <div class="rounded-lg bg-slate-50 p-4 border border-slate-200">
                    <h3 class="text-xs font-bold text-slate-700 uppercase tracking-wider">Danh sách ID Phòng ban hiện có:</h3>
                    <div class="mt-2 max-h-36 overflow-y-auto space-y-1 text-xs">
                        @forelse($departments as $dept)
                            <div class="flex justify-between items-center bg-white p-1.5 rounded border border-slate-200">
                                <span class="font-semibold text-slate-800">{{ $dept->name }}</span>
                                <span class="font-mono bg-slate-100 px-2 py-0.5 rounded font-bold text-primary">ID: {{ $dept->id }}</span>
                            </div>
                        @empty
                            <p class="text-slate-400 italic">Chưa có phòng ban nào trong hệ thống.</p>
                        @endforelse
                    </div>
                </div>
            </div>

            <div class="flex justify-end">
                <button type="submit" class="rounded-lg bg-primary px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-primary-dark transition">
                    🚀 Tải lên & Nạp Staff
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
