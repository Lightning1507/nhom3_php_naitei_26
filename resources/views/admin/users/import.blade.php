@extends('admin.layouts.app')

@section('title', 'Nhập dữ liệu tài khoản từ tệp CSV')

@section('content')
<div class="space-y-6" x-data="{ activeTab: '{{ session('import_type', 'citizen') }}' }">
    {{-- Tiêu đề trang --}}
    <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Nhập dữ liệu tài khoản từ tệp CSV</h1>
            <p class="mt-1 text-sm text-slate-500">Thêm hàng loạt tài khoản Công dân hoặc Cán bộ vào hệ thống từ tệp dữ liệu CSV.</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.dashboard') }}" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50">
                Quay lại trang chủ
            </a>
        </div>
    </div>

    {{-- Kết quả nhập dữ liệu --}}
    @if ($report)
        @php
            $data = $report['data'] ?? [];
            $total = $data['total_rows'] ?? 0;
            $success = $data['success_count'] ?? 0;
            $failure = $data['failure_count'] ?? 0;
            $reportErrors = $data['errors'] ?? [];
        @endphp
        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex items-center justify-between flex-wrap gap-2">
                <h2 class="text-lg font-bold text-slate-900">Kết quả nhập dữ liệu</h2>
                <span class="text-xs px-2.5 py-1 rounded border font-semibold {{ $failure > 0 ? 'border-amber-300 bg-amber-50 text-amber-800' : 'border-emerald-300 bg-emerald-50 text-emerald-800' }}">
                    {{ session('import_type') === 'staff' ? 'Cán bộ, công chức' : 'Công dân' }}
                </span>
            </div>

            <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-3">
                <div class="rounded-lg bg-slate-50 p-4 border border-slate-200">
                    <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Tổng số bản ghi</span>
                    <p class="mt-1 text-2xl font-extrabold text-slate-800">{{ $total }}</p>
                </div>
                <div class="rounded-lg bg-emerald-50 p-4 border border-emerald-200">
                    <span class="text-xs font-semibold text-emerald-700 uppercase tracking-wider">Nhập thành công</span>
                    <p class="mt-1 text-2xl font-extrabold text-emerald-700">{{ $success }}</p>
                </div>
                <div class="rounded-lg bg-red-50 p-4 border border-red-200">
                    <span class="text-xs font-semibold text-red-700 uppercase tracking-wider">Không hợp lệ / Lỗi</span>
                    <p class="mt-1 text-2xl font-extrabold text-red-700">{{ $failure }}</p>
                </div>
            </div>

            @if ($failure > 0 && !empty($reportErrors))
                <div class="mt-6">
                    <h3 class="text-sm font-bold text-slate-800 mb-2">Chi tiết các dòng không hợp lệ ({{ count($reportErrors) }} dòng):</h3>
                    <div class="overflow-x-auto rounded-lg border border-slate-200">
                        <table class="w-full text-left text-sm text-slate-600">
                            <thead class="bg-slate-100 text-xs font-semibold text-slate-700 border-b border-slate-200">
                                <tr>
                                    <th class="px-4 py-3">Số thứ tự dòng</th>
                                    <th class="px-4 py-3">Trường dữ liệu</th>
                                    <th class="px-4 py-3">Nguyên nhân lỗi</th>
                                    <th class="px-4 py-3">Dữ liệu gốc</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200 bg-white">
                                @foreach ($reportErrors as $err)
                                    <tr class="hover:bg-slate-50 transition">
                                        <td class="px-4 py-3 font-mono font-bold text-red-600">Dòng {{ $err['line_number'] ?? 'N/A' }}</td>
                                        <td class="px-4 py-3 font-semibold text-slate-900">{{ $err['field'] ?? 'Chung' }}</td>
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

    {{-- Thanh chọn loại nhập dữ liệu --}}
    <div class="border-b border-slate-200">
        <nav class="-mb-px flex space-x-8" aria-label="Loại nhập dữ liệu">
            <button
                @click="activeTab = 'citizen'"
                :class="activeTab === 'citizen' ? 'border-primary text-primary font-bold' : 'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-700'"
                class="whitespace-nowrap border-b-2 py-4 px-1 text-sm font-medium transition"
            >
                Nhập danh sách Công dân
            </button>

            <button
                @click="activeTab = 'staff'"
                :class="activeTab === 'staff' ? 'border-primary text-primary font-bold' : 'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-700'"
                class="whitespace-nowrap border-b-2 py-4 px-1 text-sm font-medium transition"
            >
                Nhập danh sách Cán bộ, công chức
            </button>
        </nav>
    </div>

    {{-- Biểu mẫu nhập dữ liệu Công dân --}}
    <div x-show="activeTab === 'citizen'" class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        <h2 class="text-lg font-bold text-slate-900">Nhập danh sách Công dân</h2>
        <p class="mt-1 text-sm text-slate-500">Tải lên tệp CSV chứa danh sách tài khoản Công dân. Hệ thống sẽ tự động xác thực và tạo tài khoản.</p>

        <form action="{{ route('admin.users.import.citizens') }}" method="POST" enctype="multipart/form-data" class="mt-6 space-y-5">
            @csrf
            <div>
                <label for="csv_file_citizen" class="block text-sm font-medium text-slate-700">Tệp dữ liệu CSV <span class="text-red-500">*</span></label>
                <input
                    type="file"
                    id="csv_file_citizen"
                    name="csv_file"
                    accept=".csv,.txt,text/csv,text/plain,application/vnd.ms-excel"
                    required
                    class="mt-2 block w-full text-sm text-slate-500 file:mr-4 file:rounded-lg file:border-0 file:bg-primary/10 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-primary hover:file:bg-primary/20 cursor-pointer border border-slate-300 rounded-lg p-1.5"
                >
                @error('csv_file')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="rounded-lg bg-slate-50 p-4 border border-slate-200">
                <h3 class="text-xs font-bold text-slate-700 uppercase tracking-wider">Cấu trúc tệp CSV mẫu — Công dân</h3>
                <code class="mt-2 block rounded bg-slate-900 p-3 text-xs text-emerald-400 font-mono overflow-x-auto">
                    name,email,citizen_id,phone,address,date_of_birth<br>
                    Nguyễn Văn A,nguyenvana@gmail.com,001098123456,0987654321,"123 Lê Lợi, Hà Nội",1990-05-15
                </code>
                <ul class="mt-3 text-xs text-slate-600 space-y-1.5">
                    <li><span class="font-semibold text-slate-800">name</span>: Bắt buộc. Họ và tên, tối đa 255 ký tự.</li>
                    <li><span class="font-semibold text-slate-800">email</span>: Bắt buộc. Địa chỉ email hợp lệ, không được trùng lặp.</li>
                    <li><span class="font-semibold text-slate-800">citizen_id</span>: Bắt buộc. Số CCCD gồm đúng 12 chữ số, không được trùng lặp.</li>
                    <li><span class="font-semibold text-slate-800">phone</span>: Không bắt buộc. Số điện thoại từ 10 đến 11 chữ số.</li>
                    <li><span class="font-semibold text-slate-800">date_of_birth</span>: Không bắt buộc. Định dạng YYYY-MM-DD.</li>
                </ul>
            </div>

            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 pt-2 border-t border-slate-100">
                <label class="inline-flex items-center gap-2 cursor-pointer text-xs font-medium text-slate-700 hover:text-slate-900">
                    <input type="checkbox" name="rollback_on_error" value="1" class="rounded border-slate-300 text-primary focus:ring-primary h-4 w-4">
                    <span>Hoàn tác toàn bộ lô nhập nếu có bất kỳ dòng dữ liệu nào không hợp lệ</span>
                </label>
                <button type="submit" class="rounded-lg bg-primary px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-primary-dark transition">
                    Tải lên và xử lý
                </button>
            </div>
        </form>
    </div>

    {{-- Biểu mẫu nhập dữ liệu Cán bộ --}}
    <div x-show="activeTab === 'staff'" class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm" x-cloak>
        <h2 class="text-lg font-bold text-slate-900">Nhập danh sách Cán bộ, công chức</h2>
        <p class="mt-1 text-sm text-slate-500">Tải lên tệp CSV chứa danh sách Cán bộ và Trưởng đơn vị. Hệ thống sẽ tự động phân quyền và gán vào đơn vị tương ứng.</p>

        <form action="{{ route('admin.users.import.staff') }}" method="POST" enctype="multipart/form-data" class="mt-6 space-y-5">
            @csrf
            <div>
                <label for="csv_file_staff" class="block text-sm font-medium text-slate-700">Tệp dữ liệu CSV <span class="text-red-500">*</span></label>
                <input
                    type="file"
                    id="csv_file_staff"
                    name="csv_file"
                    accept=".csv,.txt,text/csv,text/plain,application/vnd.ms-excel"
                    required
                    class="mt-2 block w-full text-sm text-slate-500 file:mr-4 file:rounded-lg file:border-0 file:bg-primary/10 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-primary hover:file:bg-primary/20 cursor-pointer border border-slate-300 rounded-lg p-1.5"
                >
                @error('csv_file')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="rounded-lg bg-slate-50 p-4 border border-slate-200">
                    <h3 class="text-xs font-bold text-slate-700 uppercase tracking-wider">Cấu trúc tệp CSV mẫu — Cán bộ</h3>
                    <code class="mt-2 block rounded bg-slate-900 p-3 text-xs text-emerald-400 font-mono overflow-x-auto">
                        name,email,department_id,role,phone<br>
                        Trần Thị B,tranthib@gov.vn,1,staff,0912345678<br>
                        Phạm Văn C,phamvanc@gov.vn,2,manager,0988776655
                    </code>
                    <ul class="mt-3 text-xs text-slate-600 space-y-1.5">
                        <li><span class="font-semibold text-slate-800">department_id</span>: Mã đơn vị hợp lệ trong hệ thống.</li>
                        <li><span class="font-semibold text-slate-800">role</span>: Vai trò, nhận giá trị <code class="bg-slate-200 px-1 py-0.5 rounded">staff</code> (cán bộ) hoặc <code class="bg-slate-200 px-1 py-0.5 rounded">manager</code> (trưởng đơn vị).</li>
                    </ul>
                </div>

                <div class="rounded-lg bg-slate-50 p-4 border border-slate-200">
                    <h3 class="text-xs font-bold text-slate-700 uppercase tracking-wider">Danh sách mã đơn vị hiện có</h3>
                    <div class="mt-2 max-h-36 overflow-y-auto space-y-1 text-xs">
                        @forelse($departments as $dept)
                            <div class="flex justify-between items-center bg-white p-1.5 rounded border border-slate-200">
                                <span class="font-medium text-slate-800">{{ $dept->name }}</span>
                                <span class="font-mono bg-slate-100 px-2 py-0.5 rounded text-slate-700">Mã: {{ $dept->id }}</span>
                            </div>
                        @empty
                            <p class="text-slate-400 italic">Chưa có đơn vị nào trong hệ thống.</p>
                        @endforelse
                    </div>
                </div>
            </div>

            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 pt-2 border-t border-slate-100">
                <label class="inline-flex items-center gap-2 cursor-pointer text-xs font-medium text-slate-700 hover:text-slate-900">
                    <input type="checkbox" name="rollback_on_error" value="1" class="rounded border-slate-300 text-primary focus:ring-primary h-4 w-4">
                    <span>Hoàn tác toàn bộ lô nhập nếu có bất kỳ dòng dữ liệu nào không hợp lệ</span>
                </label>
                <button type="submit" class="rounded-lg bg-primary px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-primary-dark transition">
                    Tải lên và xử lý
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
