@extends('admin.layouts.app')

@section('title', 'Nhật ký hoạt động')

@section('content')
    <div class="mb-5">
        <h1 class="text-2xl font-bold text-gray-950">Nhật ký hoạt động</h1>
        <p class="mt-1 text-sm text-gray-600">Tra cứu các thao tác bảo mật, quản trị và xử lý hồ sơ đã được hệ thống ghi nhận.</p>
    </div>

    <section class="admin-card" aria-labelledby="activity-log-results-title">
        <h2 id="activity-log-results-title" class="sr-only">Kết quả tra cứu nhật ký</h2>

        <form method="GET" action="{{ route('admin.activity-logs.index') }}" class="border-b border-border p-4 sm:p-5">
            <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-12 xl:items-end">
                <div class="xl:col-span-3">
                    <label class="admin-label" for="activity-q">Tìm kiếm</label>
                    <input
                        id="activity-q"
                        class="admin-input"
                        type="search"
                        name="q"
                        value="{{ $filters['q'] ?? '' }}"
                        maxlength="100"
                        placeholder="Hành động, mô tả, actor, mã hồ sơ"
                    >
                    @error('q') <p class="admin-field-error">{{ $message }}</p> @enderror
                </div>

                <div class="xl:col-span-3">
                    <label class="admin-label" for="activity-actor">Người thực hiện</label>
                    <select id="activity-actor" class="admin-select" name="actor_id">
                        <option value="">Tất cả người thực hiện</option>
                        @foreach ($actorOptions as $actor)
                            <option value="{{ $actor->id }}" @selected((string) ($filters['actor_id'] ?? '') === (string) $actor->id)>
                                {{ $actor->name }} - {{ $actor->email }}
                            </option>
                        @endforeach
                    </select>
                    @error('actor_id') <p class="admin-field-error">{{ $message }}</p> @enderror
                </div>

                <div class="xl:col-span-2">
                    <label class="admin-label" for="activity-action">Hành động</label>
                    <select id="activity-action" class="admin-select" name="action">
                        <option value="">Tất cả hành động</option>
                        @foreach ($actionOptions as $value => $label)
                            <option value="{{ $value }}" @selected(($filters['action'] ?? null) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('action') <p class="admin-field-error">{{ $message }}</p> @enderror
                </div>

                <div class="xl:col-span-2">
                    <label class="admin-label" for="activity-subject">Đối tượng</label>
                    <select id="activity-subject" class="admin-select" name="subject_type">
                        <option value="">Tất cả đối tượng</option>
                        @foreach ($subjectTypeOptions as $value => $label)
                            <option value="{{ $value }}" @selected(($filters['subject_type'] ?? null) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('subject_type') <p class="admin-field-error">{{ $message }}</p> @enderror
                </div>

                <div class="xl:col-span-1">
                    <label class="admin-label" for="activity-date-from">Từ ngày</label>
                    <input id="activity-date-from" class="admin-input" type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}">
                    @error('date_from') <p class="admin-field-error">{{ $message }}</p> @enderror
                </div>

                <div class="xl:col-span-1">
                    <label class="admin-label" for="activity-date-to">Đến ngày</label>
                    <input id="activity-date-to" class="admin-input" type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}">
                    @error('date_to') <p class="admin-field-error">{{ $message }}</p> @enderror
                </div>

                <div class="flex flex-wrap gap-2 xl:col-span-12">
                    <x-admin.button type="submit">Áp dụng</x-admin.button>
                    @if ($hasFilters)
                        <x-admin.button variant="secondary" :href="route('admin.activity-logs.index')">Xóa bộ lọc</x-admin.button>
                    @endif
                </div>
            </div>
        </form>

        @if ($activityLogs->isEmpty())
            <div class="px-5 py-14 text-center">
                <h3 class="text-base font-semibold text-gray-950">Không tìm thấy nhật ký phù hợp</h3>
                <p class="mt-1 text-sm text-gray-600">Hãy kiểm tra từ khóa hoặc thay đổi bộ lọc.</p>
                @if ($hasFilters)
                    <x-admin.button class="mt-4" variant="secondary" :href="route('admin.activity-logs.index')">Xóa bộ lọc</x-admin.button>
                @endif
            </div>
        @else
            <div class="admin-table-wrap overflow-x-auto rounded-none border-x-0 border-t-0">
                <table class="admin-table min-w-[1040px] w-full">
                    <caption class="sr-only">Danh sách nhật ký hoạt động</caption>
                    <thead>
                        <tr>
                            <th scope="col">Thời gian</th>
                            <th scope="col">Người thực hiện</th>
                            <th scope="col">Hành động</th>
                            <th scope="col">Đối tượng</th>
                            <th scope="col">Mô tả</th>
                            <th scope="col">Ngữ cảnh</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($activityLogs as $log)
                            @php
                                $metadata = $log->metadata ?? [];
                                $application = $metadata['application'] ?? null;
                                $department = $metadata['department'] ?? null;
                                $subjectLabel = $application['application_code'] ?? $department['name'] ?? class_basename((string) $log->subject_type);
                                $actionLabel = $actionOptions[$log->action] ?? $log->action;
                            @endphp
                            <tr>
                                <td class="whitespace-nowrap">{{ $log->created_at?->format('d/m/Y H:i') }}</td>
                                <td class="max-w-[220px]">
                                    @if ($log->actor)
                                        <p class="truncate font-semibold text-gray-950" title="{{ $log->actor->name }}">{{ $log->actor->name }}</p>
                                        <p class="truncate text-xs text-gray-500" title="{{ $log->actor->email }}">{{ $log->actor->email }}</p>
                                        <x-admin.badge :variant="$log->actor->role->badgeVariant()">{{ $log->actor->role->label() }}</x-admin.badge>
                                    @else
                                        <span class="text-sm text-gray-500">Hệ thống</span>
                                    @endif
                                </td>
                                <td>
                                    <p class="font-semibold text-gray-950">{{ $actionLabel }}</p>
                                    <p class="font-mono text-xs text-gray-500">{{ $log->action }}</p>
                                </td>
                                <td class="max-w-[180px]">
                                    <p class="truncate font-semibold text-gray-950" title="{{ $subjectLabel }}">{{ $subjectLabel }}</p>
                                    <p class="truncate text-xs text-gray-500" title="{{ $log->subject_type }}">{{ class_basename((string) $log->subject_type) }} #{{ $log->subject_id }}</p>
                                    @if (isset($application['status_label']))
                                        <p class="mt-1 text-xs text-gray-500">{{ $application['status_label'] }}</p>
                                    @endif
                                </td>
                                <td class="max-w-[260px]">
                                    <p class="line-clamp-2 text-sm text-gray-700">{{ $log->description ?: 'Không có mô tả.' }}</p>
                                    @if (($metadata['note_present'] ?? false) === true)
                                        <p class="mt-1 text-xs font-medium text-amber-700">Có ghi chú nghiệp vụ</p>
                                    @endif
                                </td>
                                <td class="max-w-[220px]">
                                    <p class="truncate text-xs text-gray-600" title="{{ $log->ip_address }}">IP: {{ $log->ip_address ?: 'Không ghi nhận' }}</p>
                                    <p class="truncate text-xs text-gray-500" title="{{ $log->user_agent }}">{{ $log->user_agent ?: 'Không có user agent' }}</p>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="border-t border-border px-4 py-4 sm:px-5">
                <p class="mb-3 text-sm text-gray-600">
                    Hiển thị {{ number_format($activityLogs->firstItem()) }}-{{ number_format($activityLogs->lastItem()) }} trong {{ number_format($activityLogs->total()) }} kết quả
                </p>
                @if ($activityLogs->hasPages())
                    {{ $activityLogs->onEachSide(1)->links() }}
                @endif
            </div>
        @endif
    </section>
@endsection
