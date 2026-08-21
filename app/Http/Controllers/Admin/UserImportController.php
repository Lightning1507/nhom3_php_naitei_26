<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CsvImportRequest;
use App\Models\Department;
use App\Services\CsvImportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class UserImportController extends Controller
{
    public function __construct(
        private readonly CsvImportService $importService
    ) {}

    /**
     * Display the user import page with instructions and reports.
     */
    public function index(): View
    {
        $departments = Department::query()->orderBy('name')->get();

        return view('admin.users.import', compact('departments'));
    }

    /**
     * Handle Citizen CSV import request.
     */
    public function importCitizens(CsvImportRequest $request): RedirectResponse|JsonResponse
    {
        $file = $request->file('csv_file');
        $report = $this->importService->importCitizens($file->getRealPath());

        if ($request->wantsJson()) {
            return response()->json($report);
        }

        return redirect()
            ->route('admin.users.import')
            ->with('report', $report)
            ->with('import_type', 'citizen');
    }

    /**
     * Handle Staff CSV import request.
     */
    public function importStaff(CsvImportRequest $request): RedirectResponse|JsonResponse
    {
        $file = $request->file('csv_file');
        $report = $this->importService->importStaff($file->getRealPath());

        if ($request->wantsJson()) {
            return response()->json($report);
        }

        return redirect()
            ->route('admin.users.import')
            ->with('report', $report)
            ->with('import_type', 'staff');
    }
}
