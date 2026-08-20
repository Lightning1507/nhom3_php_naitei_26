@extends('admin.layouts.app')

@section('title', 'Hồ sơ dịch vụ công')

@section('content')
    <div class="mb-5 flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-950">Hồ sơ dịch vụ công</h1>
            <p class="mt-1 text-sm text-gray-600">Danh sách hồ sơ trong phạm vi được phép để phân công và xử lý.</p>
        </div>
    </div>

    @isset($stats)
        <section class="mb-5 grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-2" aria-label="Thống kê hồ sơ">
            @foreach ([
                ['label' => 'Đang chờ xử lý', 'value' => $stats['pending'], 'class' => 'text-amber-700'],
                ['label' => 'Quá hạn', 'value' => $stats['overdue'], 'class' => 'text-red-700'],
            ] as $stat)
                <article class="admin-card">
                    <div class="admin-card-body">
                        <p class="text-[13px] font-medium text-gray-500">{{ $stat['label'] }}</p>
                        <p class="mt-1 text-2xl font-bold {{ $stat['class'] }}">{{ number_format($stat['value']) }}</p>
                    </div>
                </article>
            @endforeach
        </section>
    @endisset

    @if ($claimable > 0)
        <section class="mb-5 admin-card" aria-labelledby="claimable-title">
            <div class="flex flex-wrap items-center justify-between gap-2 border-b border-border px-4 py-3 sm:px-5">
                <h2 id="claimable-title" class="text-base font-bold text-gray-950">Hồ sơ có thể nhận</h2>
                <x-admin.badge variant="info">{{ number_format($claimable) }} hồ sơ</x-admin.badge>
            </div>
            <div class="admin-card-body space-y-2">
                @forelse ($claimableApplications as $application)
                    <div class="flex flex-wrap items-center justify-between gap-2 rounded-lg border border-border bg-white px-3 py-2">
                        <div class="min-w-0">
                            <p class="truncate font-mono text-sm font-semibold text-primary">{{ $application->application_code }}</p>
                            <p class="truncate text-xs text-gray-600">{{ $application->serviceType?->name }} · {{ $application->citizen?->name }}</p>
                        </div>
                        <x-admin.button variant="secondary" :href="route('admin.applications.show', $application)">Nhận xử lý</x-admin.button>
                    </div>
                @empty
                    <p class="text-sm text-gray-600">Không có hồ sơ nào đang chờ nhận.</p>
                @endforelse
            </div>
        </section>
    @endif

    <section class="admin-card" aria-labelledby="application-results-title">
        <h2 id="application-results-title" class="sr-only">Kết quả tra cứu hồ sơ</h2>
        <form method="GET" action="{{ route('admin.applications.index') }}" class="border-b border-border p-4 sm:p-5">
            <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-4 xl:items-end">
                <div>
                    <label class="admin-label" for="application-q">Tìm kiếm</label>
                    <input id="application-q" class="admin-input" type="search" name="q" value="{{ request('q') }}" maxlength="40" placeholder="Mã hồ sơ">
                </div>
                <div>
                    <label class="admin-label" for="application-status">Trạng thái</label>
                    <select id="application-status" class="admin-select" name="status">
                        <option value="">Tất cả trạng thái</option>
                        @foreach (['received' => 'Mới tiếp nhận', 'processing' => 'Đang xử lý', 'supplement_required' => 'Chờ bổ sung', 'approved' => 'Đã duyệt', 'rejected' => 'Đã từ chối'] as $value => $label)
                            <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="admin-label" for="application-overdue">Hạn xử lý</label>
                    <select id="application-overdue" class="admin-select" name="overdue">
                        <option value="">Tất cả</option>
                        <option value="1" @selected(request()->boolean('overdue'))>Chỉ hồ sơ quá hạn</option>
                    </select>
                </div>
                <div class="flex flex-wrap gap-2">
                    <x-admin.button type="submit">Áp dụng</x-admin.button>
                    @if (request()->hasAny(['q', 'status', 'overdue', 'assigned_staff_id']))
                        <x-admin.button variant="secondary" :href="route('admin.applications.index')">Xóa bộ lọc</x-admin.button>
                    @endif
                </div>
            </div>
        </form>

        @if ($applications->isEmpty())
            <div class="px-5 py-12 text-center">
                <h3 class="text-lg font-bold text-gray-950">Không có hồ sơ nào</h3>
                <p class="mx-auto mt-2 max-w-lg text-sm text-gray-600">Hồ sơ trong phạm vi của bạn sẽ xuất hiện tại đây.</p>
            </div>
        @else
            <div class="border-b border-border px-4 pt-4 sm:px-5">
                <h2 class="text-base font-bold text-gray-950">Hồ sơ của tôi</h2>
                <p class="mt-0.5 text-sm text-gray-600">Các hồ sơ đã được gán cho bạn hoặc nằm trong phạm vi quản lý.</p>
            </div>
            <div class="admin-table-wrap rounded-none border-x-0 border-t-0" tabindex="0" aria-label="Bảng hồ sơ có thể cuộn ngang">
                <table class="admin-table min-w-[920px]">
                    <caption class="sr-only">Danh sách hồ sơ trong phạm vi được phép</caption>
                    <thead>
                        <tr>
                            <th scope="col">Mã hồ sơ</th>
                            <th scope="col">Dịch vụ</th>
                            <th scope="col">Người nộp</th>
                            <th scope="col">Người xử lý</th>
                            <th scope="col">Trạng thái</th>
                            <th scope="col">Hạn xử lý</th>
                            <th scope="col" aria-label="Thao tác"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($applications as $application)
                            <tr>
                                <td class="whitespace-nowrap font-mono font-semibold text-primary">{{ $application->application_code }}</td>
                                <td class="max-w-[260px]">
                                    <p class="truncate font-medium text-gray-950" title="{{ $application->serviceType?->name }}">{{ $application->serviceType?->name }}</p>
                                    <p class="truncate text-xs text-gray-500">{{ $application->serviceType?->responsibleDepartment?->name }}</p>
                                </td>
                                <td class="max-w-[200px]">
                                    <p class="truncate" title="{{ $application->citizen?->name }}">{{ $application->citizen?->name }}</p>
                                </td>
                                <td class="max-w-[200px]">
                                    <p class="truncate" title="{{ $application->assignedStaff?->name }}">{{ $application->assignedStaff?->name ?: 'Chưa gán' }}</p>
                                </td>
                                <td>
                                    <x-admin.badge :variant="match ($application->status->value) {
                                        'approved' => 'success',
                                        'rejected' => 'danger',
                                        'supplement_required' => 'warning',
                                        'processing' => 'info',
                                        default => 'neutral',
                                    }">
                                        {{ match ($application->status->value) {
                                            'received' => 'Mới tiếp nhận',
                                            'processing' => 'Đang xử lý',
                                            'supplement_required' => 'Chờ bổ sung',
                                            'approved' => 'Đã duyệt',
                                            'rejected' => 'Đã từ chối',
                                            default => $application->status->value,
                                        } }}
                                    </x-admin.badge>
                                </td>
                                <td>
                                    @if ($application->completed_at)
                                        <span class="text-xs text-gray-500">Đã hoàn tất</span>
                                    @elseif ($application->isOverdue())
                                        <x-admin.badge variant="danger">Quá hạn</x-admin.badge>
                                    @else
                                        <span class="text-xs text-gray-500">{{ $application->serviceType?->processing_time_days }} ngày</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="flex justify-end">
                                        <x-admin.button variant="ghost" :href="route('admin.applications.show', $application)">Chi tiết</x-admin.button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="px-4 py-4 sm:px-5">
                <p class="mb-3 text-sm text-gray-600">
                    Hiển thị {{ number_format($applications->firstItem()) }}–{{ number_format($applications->lastItem()) }} trong {{ number_format($applications->total()) }} kết quả
                </p>
                @if ($applications->hasPages())
                    {{ $applications->onEachSide(1)->links() }}
                @endif
            </div>
        @endif
    </section>
@endsection