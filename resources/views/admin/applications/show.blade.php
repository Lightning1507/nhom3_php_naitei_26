@extends('admin.layouts.app')

@section('title', 'Chi tiết hồ sơ '.$application->application_code)

@section('content')
    <div class="mb-5 flex flex-wrap items-start justify-between gap-4">
        <div>
            <p class="text-sm text-gray-500">
                <a class="font-semibold text-primary hover:underline" href="{{ route('admin.applications.index') }}">← Hồ sơ</a>
            </p>
            <h1 class="mt-1 text-2xl font-bold text-gray-950">{{ $application->application_code }}</h1>
            <p class="mt-1 text-sm text-gray-600">{{ $application->serviceType?->name }}</p>
        </div>
        <div class="flex flex-wrap gap-2">
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
        </div>
    </div>

    @if ($application->isOverdue())
        <div class="mb-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800" role="alert">
            Hồ sơ đã quá hạn xử lý theo {{ $application->serviceType?->processing_time_days }} ngày kể từ khi nộp.
        </div>
    @endif

    @if ($application->status->value === 'supplement_required')
        @php
            $missingDocs = $application->missingRequiredDocuments();
        @endphp
        <div class="mb-5 overflow-hidden rounded-xl border border-amber-300 bg-amber-50" role="alert">
            <div class="flex flex-col gap-3 px-4 py-3 sm:flex-row sm:items-start sm:justify-between sm:gap-4">
                <div class="min-w-0">
                    <p class="text-[13px] font-bold uppercase tracking-wide text-amber-800">Đang chờ bổ sung tài liệu</p>
                    @if ($application->supplementNote())
                        <p class="mt-1 text-sm text-amber-900">
                            <span class="font-semibold">Ghi chú của cán bộ:</span> {{ $application->supplementNote() }}
                        </p>
                    @endif
                    @if ($missingDocs !== [])
                        <p class="mt-2 text-sm text-amber-900">
                            <span class="font-semibold">Tài liệu còn thiếu:</span>
                            @foreach ($missingDocs as $missing)
                                <x-admin.badge variant="warning">{{ $missing['label'] }}</x-admin.badge>
                            @endforeach
                        </p>
                    @endif
                </div>
                @can('resume', $application)
                    <form method="POST" action="{{ route('admin.applications.resume', $application) }}" class="shrink-0">
                        @csrf
                        <x-admin.button type="submit" class="whitespace-nowrap">Tiếp tục xử lý</x-admin.button>
                    </form>
                @endcan
            </div>
        </div>
    @endif

    <div class="grid gap-6 lg:grid-cols-3">
        <section class="admin-card lg:col-span-2" aria-labelledby="application-info-title">
            <h2 id="application-info-title" class="admin-card-title">Thông tin hồ sơ</h2>
            <div class="admin-card-body space-y-4">
                <dl class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                    <div class="min-w-0">
                        <dt class="text-[13px] font-medium text-gray-500">Người nộp</dt>
                        <dd class="mt-0.5 break-words font-medium text-gray-950">{{ $application->citizen?->name }}</dd>
                    </div>
                    <div class="min-w-0">
                        <dt class="text-[13px] font-medium text-gray-500">Người xử lý</dt>
                        <dd class="mt-0.5 break-words font-medium text-gray-950">{{ $application->assignedStaff?->name ?: 'Chưa gán' }}</dd>
                    </div>
                    <div class="min-w-0">
                        <dt class="text-[13px] font-medium text-gray-500">Ngày nộp</dt>
                        <dd class="mt-0.5">{{ $application->submitted_at?->format('d/m/Y H:i') }}</dd>
                    </div>
                    <div class="min-w-0">
                        <dt class="text-[13px] font-medium text-gray-500">Phòng ban phụ trách</dt>
                        <dd class="mt-0.5 break-words">{{ $application->serviceType?->responsibleDepartment?->name }}</dd>
                    </div>
                    <div class="min-w-0">
                        <dt class="text-[13px] font-medium text-gray-500">Bắt đầu xử lý</dt>
                        <dd class="mt-0.5">{{ $application->processing_started_at?->format('d/m/Y H:i') ?: '—' }}</dd>
                    </div>
                    <div class="min-w-0">
                        <dt class="text-[13px] font-medium text-gray-500">Hoàn tất</dt>
                        <dd class="mt-0.5">{{ $application->completed_at?->format('d/m/Y H:i') ?: '—' }}</dd>
                    </div>
                </dl>

                @if ($application->result_note)
                    <div class="overflow-hidden rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3">
                        <p class="text-[13px] font-semibold text-emerald-800">Ghi chú kết quả</p>
                        <p class="mt-1 break-words text-sm text-emerald-900">{{ $application->result_note }}</p>
                    </div>
                @endif

                @if ($application->rejection_reason)
                    <div class="overflow-hidden rounded-xl border border-red-200 bg-red-50 px-4 py-3">
                        <p class="text-[13px] font-semibold text-red-800">Lý do từ chối</p>
                        <p class="mt-1 break-words text-sm text-red-900">{{ $application->rejection_reason }}</p>
                    </div>
                @endif

                <div>
                    <h3 class="text-[13px] font-semibold text-gray-700">Nội dung đăng ký</h3>
                    <dl class="mt-2 grid grid-cols-1 gap-2 sm:grid-cols-2">
                        @foreach ($application->form_data ?? [] as $key => $value)
                            <div class="min-w-0">
                                <dt class="break-words text-[13px] text-gray-500">{{ $key }}</dt>
                                <dd class="break-words text-sm font-medium text-gray-900">{{ is_scalar($value) ? $value : json_encode($value) }}</dd>
                            </div>
                        @endforeach
                    </dl>
                </div>
            </div>
        </section>

        <aside class="space-y-6" aria-label="Thao tác xử lý">
            <section class="admin-card" aria-labelledby="workflow-actions-title">
                <h2 id="workflow-actions-title" class="admin-card-title">Thao tác</h2>
                <div class="admin-card-body space-y-3">
                    @php
                        $guidance = match ($application->status->value) {
                            'received' => $application->assignedStaff?->getKey() === auth()->id()
                                ? 'Hồ sơ đã được gán cho bạn. Hãy bắt đầu xử lý hoặc chuyển trạng thái tiếp theo.'
                                : 'Hồ sơ đang chờ phân công hoặc nhận xử lý.',
                            'processing' => 'Bạn đang xử lý hồ sơ. Có thể yêu cầu bổ sung tài liệu, duyệt hoặc từ chối.',
                            'supplement_required' => 'Đang chờ công dân nộp tài liệu bổ sung. Khi đã đủ tài liệu, hãy tiếp tục xử lý.',
                            'approved' => 'Hồ sơ đã được duyệt và hoàn tất.',
                            'rejected' => 'Hồ sơ đã bị từ chối và hoàn tất.',
                            default => null,
                        };
                    @endphp
                    @if ($guidance)
                        <div class="rounded-xl border border-blue-100 bg-blue-50/60 px-4 py-3 text-sm leading-5 text-blue-900" role="status">
                            <p class="text-[13px] font-bold uppercase tracking-wide text-blue-700">Bước tiếp theo</p>
                            <p class="mt-1">{{ $guidance }}</p>
                        </div>
                    @endif

                    @can('claim', $application)
                        <form method="POST" action="{{ route('admin.applications.claim', $application) }}">
                            @csrf
                            <x-admin.button type="submit" class="w-full">Nhận hồ sơ</x-admin.button>
                        </form>
                    @endcan

                    @can('assign', $application)
                        <x-admin.button data-dialog-open="assign-application-{{ $application->id }}" class="w-full">Phân công / đổi cán bộ</x-admin.button>
                    @endcan

                    @can('startProcessing', $application)
                        <form method="POST" action="{{ route('admin.applications.start-processing', $application) }}">
                            @csrf
                            <x-admin.button type="submit" class="w-full">Bắt đầu xử lý</x-admin.button>
                        </form>
                    @endcan

                    @can('requestSupplement', $application)
                        <x-admin.button variant="secondary" data-dialog-open="request-supplement-{{ $application->id }}" class="w-full">Yêu cầu bổ sung</x-admin.button>
                    @endcan

                    @can('resume', $application)
                        <form method="POST" action="{{ route('admin.applications.resume', $application) }}">
                            @csrf
                            <x-admin.button type="submit" class="w-full">Tiếp tục xử lý</x-admin.button>
                        </form>
                    @endcan

                    @can('approve', $application)
                        <x-admin.button variant="secondary" data-dialog-open="approve-application-{{ $application->id }}" class="w-full">Duyệt hồ sơ</x-admin.button>
                    @endcan

                    @can('reject', $application)
                        <x-admin.button variant="danger" data-dialog-open="reject-application-{{ $application->id }}" class="w-full">Từ chối hồ sơ</x-admin.button>
                    @endcan

                    @can('uploadResultDocument', $application)
                        <x-admin.button variant="secondary" data-dialog-open="result-document-{{ $application->id }}" class="w-full">Đính kèm tài liệu kết quả</x-admin.button>
                    @endcan
                </div>
            </section>

            <section class="admin-card" aria-labelledby="documents-title">
                <h2 id="documents-title" class="admin-card-title">Tài liệu</h2>
                <div class="admin-card-body space-y-2">
                    @forelse ($application->documents as $document)
                        @php
                            $downloadUrl = route('admin.applications.documents.download', [$application, $document]);
                            $previewUrl = $downloadUrl.'?inline=1';
                            $previewable = in_array($document->mime_type, [
                                'application/pdf',
                                'image/png',
                                'image/jpeg',
                                'image/webp',
                            ], true);
                        @endphp
                        <div class="flex items-center justify-between gap-2 rounded-lg border border-border bg-white px-3 py-2">
                            <div class="min-w-0">
                                <p class="truncate text-sm font-medium text-gray-900">{{ $document->original_name }}</p>
                                <p class="text-xs text-gray-500">
                                    {{ $document->requirement_label ?: ($document->document_kind?->value) }}
                                </p>
                            </div>
                            <div class="flex shrink-0 items-center gap-3">
                                @if ($previewable)
                                    <button type="button" class="text-xs font-semibold text-primary hover:underline" data-dialog-open="preview-document-{{ $application->id }}-{{ $document->id }}">Xem</button>
                                @endif
                                <a class="text-xs font-semibold text-primary hover:underline" href="{{ $downloadUrl }}">Tải</a>
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-gray-600">Chưa có tài liệu.</p>
                    @endforelse
                </div>
            </section>
        </aside>
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-2">
        <section class="admin-card" aria-labelledby="timeline-title">
            <h2 id="timeline-title" class="admin-card-title">Lịch sử xử lý</h2>
            <ol class="admin-card-body space-y-3">
                @forelse ($application->statusHistories->sortBy(fn ($h) => [$h->created_at?->timestamp ?? 0, $h->id]) as $history)
                    <li class="flex gap-3">
                        <span class="mt-1.5 size-2 shrink-0 rounded-full bg-primary" aria-hidden="true"></span>
                        <div class="min-w-0">
                            <p class="text-sm font-medium text-gray-900">
                                {{ $history->from_status?->value ?: '—' }} → {{ $history->to_status->value }}
                                <span class="font-normal text-gray-500">bởi {{ $history->changedBy?->name }}</span>
                            </p>
                            @if ($history->note)
                                <p class="mt-0.5 break-words text-sm text-gray-600">{{ $history->note }}</p>
                            @endif
                            <p class="text-xs text-gray-400">{{ $history->created_at?->format('d/m/Y H:i') }}</p>
                        </div>
                    </li>
                @empty
                    <li class="text-sm text-gray-600">Chưa có lịch sử.</li>
                @endforelse
            </ol>
        </section>

        <section class="admin-card" aria-labelledby="assignments-title">
            <h2 id="assignments-title" class="admin-card-title">Lịch sử phân công</h2>
            <ol class="admin-card-body space-y-3">
                @forelse ($application->assignments as $assignment)
                    <li class="flex gap-3">
                        <span class="mt-1.5 size-2 shrink-0 rounded-full bg-primary" aria-hidden="true"></span>
                        <div class="min-w-0">
                            <p class="text-sm font-medium text-gray-900">
                                {{ $assignment->staff?->name }}
                                <span class="font-normal text-gray-500">bởi {{ $assignment->assignedBy?->name }}</span>
                            </p>
                            @if ($assignment->note)
                                <p class="mt-0.5 break-words text-sm text-gray-600">{{ $assignment->note }}</p>
                            @endif
                            <p class="text-xs text-gray-400">
                                {{ $assignment->assigned_at?->format('d/m/Y H:i') }}
                                @if ($assignment->ended_at)
                                    → hết hiệu lực {{ $assignment->ended_at->format('d/m/Y H:i') }}
                                @else
                                    (đang hiệu lực)
                                @endif
                            </p>
                        </div>
                    </li>
                @empty
                    <li class="text-sm text-gray-600">Chưa có lịch sử phân công.</li>
                @endforelse
            </ol>
        </section>
    </div>

    @can('assign', $application)
        <x-admin.dialog
            id="assign-application-{{ $application->id }}"
            title="Phân công cán bộ xử lý"
            description="Chọn staff đang hoạt động thuộc phòng ban phụ trách dịch vụ của hồ sơ."
            data-open-on-error="{{ $errors->has('staff_id') ? 'true' : 'false' }}"
        >
            <form method="POST" action="{{ route('admin.applications.assign', $application) }}" class="space-y-4">
                @csrf
                <div>
                    <label class="admin-label" for="assign-staff-{{ $application->id }}">Cán bộ xử lý</label>
                    <select id="assign-staff-{{ $application->id }}" class="admin-select" name="staff_id">
                        <option value="">Chọn cán bộ…</option>
                        @foreach ($application->serviceType?->responsibleDepartment?->users?->filter(fn ($u) => $u->isStaff() && $u->canAccessProtectedResources()) ?? [] as $candidate)
                            <option value="{{ $candidate->id }}" @selected(old('staff_id') == $candidate->id)>{{ $candidate->name }}</option>
                        @endforeach
                    </select>
                    @error('staff_id')<p class="admin-field-error">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="admin-label" for="assign-note-{{ $application->id }}">Ghi chú (tùy chọn)</label>
                    <textarea id="assign-note-{{ $application->id }}" class="admin-input" name="note" rows="2" maxlength="1000">{{ old('note') }}</textarea>
                </div>
                <div class="flex justify-end gap-2 border-t border-border pt-4">
                    <x-admin.button type="button" variant="secondary" data-dialog-close>Hủy</x-admin.button>
                    <x-admin.button type="submit">Phân công</x-admin.button>
                </div>
            </form>
        </x-admin.dialog>
    @endcan

    @can('requestSupplement', $application)
        <x-admin.dialog
            id="request-supplement-{{ $application->id }}"
            title="Yêu cầu bổ sung tài liệu"
            description="Hồ sơ sẽ chuyển sang trạng thái chờ bổ sung; công dân sẽ thấy lý do và các tài liệu còn thiếu."
            data-open-on-error="{{ $errors->has('note') ? 'true' : 'false' }}"
        >
            <form method="POST" action="{{ route('admin.applications.request-supplement', $application) }}" class="space-y-4">
                @csrf
                <div>
                    <label class="admin-label" for="supplement-note-{{ $application->id }}">Lý do yêu cầu bổ sung</label>
                    <textarea id="supplement-note-{{ $application->id }}" class="admin-input" name="note" rows="3" maxlength="2000" required>{{ old('note') }}</textarea>
                    @error('note')<p class="admin-field-error">{{ $message }}</p>@enderror
                </div>
                <div class="flex justify-end gap-2 border-t border-border pt-4">
                    <x-admin.button type="button" variant="secondary" data-dialog-close>Hủy</x-admin.button>
                    <x-admin.button type="submit">Gửi yêu cầu</x-admin.button>
                </div>
            </form>
        </x-admin.dialog>
    @endcan

    @can('approve', $application)
        <x-admin.dialog
            id="approve-application-{{ $application->id }}"
            title="Duyệt hồ sơ"
            description="Hồ sơ sẽ được duyệt và chuyển sang trạng thái đã hoàn thành."
            data-open-on-error="{{ $errors->has('result_note') ? 'true' : 'false' }}"
        >
            <form method="POST" action="{{ route('admin.applications.approve', $application) }}" class="space-y-4">
                @csrf
                <div>
                    <label class="admin-label" for="result-note-{{ $application->id }}">Ghi chú kết quả (tùy chọn)</label>
                    <textarea id="result-note-{{ $application->id }}" class="admin-input" name="result_note" rows="3" maxlength="2000">{{ old('result_note') }}</textarea>
                    @error('result_note')<p class="admin-field-error">{{ $message }}</p>@enderror
                </div>
                <div class="flex justify-end gap-2 border-t border-border pt-4">
                    <x-admin.button type="button" variant="secondary" data-dialog-close>Hủy</x-admin.button>
                    <x-admin.button type="submit">Xác nhận duyệt</x-admin.button>
                </div>
            </form>
        </x-admin.dialog>
    @endcan

    @can('reject', $application)
        <x-admin.dialog
            id="reject-application-{{ $application->id }}"
            title="Từ chối hồ sơ"
            description="Hồ sơ sẽ bị từ chối kèm lý do bắt buộc; không thể đính kèm tài liệu kết quả."
            data-open-on-error="{{ $errors->has('rejection_reason') ? 'true' : 'false' }}"
        >
            <form method="POST" action="{{ route('admin.applications.reject', $application) }}" class="space-y-4">
                @csrf
                <div>
                    <label class="admin-label" for="rejection-reason-{{ $application->id }}">Lý do từ chối</label>
                    <textarea id="rejection-reason-{{ $application->id }}" class="admin-input" name="rejection_reason" rows="3" maxlength="2000" required>{{ old('rejection_reason') }}</textarea>
                    @error('rejection_reason')<p class="admin-field-error">{{ $message }}</p>@enderror
                </div>
                <div class="flex justify-end gap-2 border-t border-border pt-4">
                    <x-admin.button type="button" variant="secondary" data-dialog-close>Hủy</x-admin.button>
                    <x-admin.button type="submit" variant="danger">Xác nhận từ chối</x-admin.button>
                </div>
            </form>
        </x-admin.dialog>
    @endcan

    @can('uploadResultDocument', $application)
        <x-admin.dialog
            id="result-document-{{ $application->id }}"
            title="Đính kèm tài liệu kết quả"
            description="Tài liệu kết quả chỉ gắn khi hồ sơ đang xử lý (trước hoặc trong lúc duyệt)."
            data-open-on-error="{{ $errors->has('document') ? 'true' : 'false' }}"
        >
            <form method="POST" action="{{ route('admin.applications.result-documents.store', $application) }}" enctype="multipart/form-data" class="space-y-4">
                @csrf
                <div>
                    <label class="admin-label" for="result-file-{{ $application->id }}">Tài liệu kết quả</label>
                    <input id="result-file-{{ $application->id }}" class="admin-input" type="file" name="document" accept=".pdf,.jpg,.jpeg,.png" required>
                    @error('document')<p class="admin-field-error">{{ $message }}</p>@enderror
                </div>
                <div class="flex justify-end gap-2 border-t border-border pt-4">
                    <x-admin.button type="button" variant="secondary" data-dialog-close>Hủy</x-admin.button>
                    <x-admin.button type="submit">Đính kèm</x-admin.button>
                </div>
            </form>
        </x-admin.dialog>
    @endcan

    @foreach ($application->documents as $document)
        @if (in_array($document->mime_type, ['application/pdf', 'image/png', 'image/jpeg', 'image/webp'], true))
            <x-admin.dialog
                id="preview-document-{{ $application->id }}-{{ $document->id }}"
                title="Xem trước tài liệu"
                description="{{ $document->original_name }}"
            >
                <div class="space-y-3">
                    <iframe
                        class="h-[70vh] w-full rounded-lg border border-border bg-gray-100"
                        data-src="{{ route('admin.applications.documents.download', [$application, $document]) }}?inline=1"
                        title="{{ $document->original_name }}"
                    ></iframe>
                    <div class="flex justify-end gap-2 border-t border-border pt-4">
                        <x-admin.button variant="secondary" data-dialog-close>Đóng</x-admin.button>
                        <x-admin.button :href="route('admin.applications.documents.download', [$application, $document])">Tải xuống</x-admin.button>
                    </div>
                </div>
            </x-admin.dialog>
        @endif
    @endforeach
@endsection