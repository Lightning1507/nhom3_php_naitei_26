<?php

namespace App\Http\Controllers\Admin\Applications;

use App\Actions\Application\ApproveApplicationAction;
use App\Actions\Application\AssignApplicationAction;
use App\Actions\Application\ClaimApplicationAction;
use App\Actions\Application\RejectApplicationAction;
use App\Actions\Application\RequestSupplementAction;
use App\Actions\Application\ResumeProcessingAction;
use App\Actions\Application\StartProcessingAction;
use App\Actions\Application\StoreResultDocumentAction;
use App\Enums\ApplicationStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Applications\ApproveApplicationRequest;
use App\Http\Requests\Admin\Applications\AssignApplicationRequest;
use App\Http\Requests\Admin\Applications\RejectApplicationRequest;
use App\Http\Requests\Admin\Applications\RequestSupplementRequest;
use App\Http\Requests\Admin\Applications\StoreResultDocumentRequest;
use App\Models\Application;
use App\Models\ApplicationDocument;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ApplicationController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Application::class);

        /** @var User $actor */
        $actor = $request->user();

        $query = Application::query()
            ->with(['serviceType.responsibleDepartment', 'citizen', 'assignedStaff'])
            ->visibleTo($actor);

        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }

        if ($request->filled('assigned_staff_id')) {
            $query->where('assigned_staff_id', (int) $request->integer('assigned_staff_id'));
        }

        if ($request->boolean('overdue')) {
            $query->whereNull('completed_at')->whereHas(
                'serviceType',
                fn ($q) => $q->whereRaw('applications.submitted_at + make_interval(secs => service_types.processing_time_days * 86400) < now()')
            );
        }

        if ($request->filled('q')) {
            $query->where('application_code', 'ilike', '%'.$request->string('q')->toString().'%');
        }

        $applications = $query
            ->orderByDesc('submitted_at')
            ->paginate(15)
            ->withQueryString();

        $claimable = $actor->isStaff()
            ? Application::query()->claimableBy($actor)->count()
            : 0;

        $stats = null;

        if ($actor->isManager() || $actor->isSuperAdmin()) {
            $boardQuery = clone $query;

            $stats = [
                'pending' => (clone $boardQuery)->whereIn('status', [
                    ApplicationStatus::Received,
                    ApplicationStatus::Processing,
                    ApplicationStatus::SupplementRequired,
                ])->count(),
                'overdue' => (clone $boardQuery)
                    ->whereNull('completed_at')
                    ->whereHas(
                        'serviceType',
                        fn ($q) => $q->whereRaw('applications.submitted_at + make_interval(secs => service_types.processing_time_days * 86400) < now()')
                    )
                    ->count(),
            ];
        }

        return view('admin.applications.index', compact('applications', 'claimable', 'stats'));
    }

    public function show(Application $application): View
    {
        $this->authorize('view', $application);

        $application->load([
            'serviceType.responsibleDepartment.users',
            'citizen',
            'assignedStaff',
            'documents',
            'assignments.staff',
            'assignments.assignedBy',
            'statusHistories.changedBy',
        ]);

        return view('admin.applications.show', compact('application'));
    }

    public function assign(
        AssignApplicationRequest $request,
        Application $application,
        AssignApplicationAction $action,
    ): RedirectResponse {
        $action->handle(
            $application,
            User::query()->findOrFail((int) $request->validated('staff_id')),
            $request->user(),
            $request->validated('note'),
        );

        return redirect()
            ->route('admin.applications.show', $application)
            ->with('success', 'Đã phân công hồ sơ cho cán bộ xử lý.');
    }

    public function claim(Request $request, Application $application, ClaimApplicationAction $action): RedirectResponse
    {
        $action->handle($application, $request->user());

        return redirect()
            ->route('admin.applications.show', $application)
            ->with('success', 'Đã nhận hồ sơ.');
    }

    public function startProcessing(Request $request, Application $application, StartProcessingAction $action): RedirectResponse
    {
        $action->handle($application, $request->user());

        return redirect()
            ->route('admin.applications.show', $application)
            ->with('success', 'Đã bắt đầu xử lý hồ sơ.');
    }

    public function requestSupplement(
        RequestSupplementRequest $request,
        Application $application,
        RequestSupplementAction $action,
    ): RedirectResponse {
        $action->handle($application, $request->user(), $request->validated('note'));

        return redirect()
            ->route('admin.applications.show', $application)
            ->with('success', 'Đã yêu cầu bổ sung tài liệu.');
    }

    public function resume(Request $request, Application $application, ResumeProcessingAction $action): RedirectResponse
    {
        $action->handle($application, $request->user());

        return redirect()
            ->route('admin.applications.show', $application)
            ->with('success', 'Đã tiếp tục xử lý hồ sơ.');
    }

    public function approve(
        ApproveApplicationRequest $request,
        Application $application,
        ApproveApplicationAction $action,
    ): RedirectResponse {
        $action->handle($application, $request->user(), $request->validated('result_note'));

        return redirect()
            ->route('admin.applications.show', $application)
            ->with('success', 'Đã duyệt hồ sơ.');
    }

    public function reject(
        RejectApplicationRequest $request,
        Application $application,
        RejectApplicationAction $action,
    ): RedirectResponse {
        $action->handle($application, $request->user(), $request->validated('rejection_reason'));

        return redirect()
            ->route('admin.applications.show', $application)
            ->with('success', 'Đã từ chối hồ sơ.');
    }

    public function storeResultDocument(
        StoreResultDocumentRequest $request,
        Application $application,
        StoreResultDocumentAction $action,
    ): RedirectResponse {
        $action->handle(
            $application,
            $request->user(),
            $request->file('document'),
            $request->validated('requirement_code'),
        );

        return redirect()
            ->route('admin.applications.show', $application)
            ->with('success', 'Đã đính kèm tài liệu kết quả.');
    }

    public function downloadDocument(Application $application, ApplicationDocument $document): StreamedResponse
    {
        $this->authorize('view', $application);
        abort_unless($application->documents()->whereKey($document->getKey())->exists(), 404);

        $this->authorize('download', $document);

        return Storage::disk($document->disk)->download(
            $document->path,
            $document->original_name,
        );
    }
}
