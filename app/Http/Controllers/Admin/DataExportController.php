<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DataExportRequest;
use App\Services\CsvExportService;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DataExportController extends Controller
{
    public function __construct(
        protected CsvExportService $exportService
    ) {}

    /**
     * Handle CSV export for specified resource.
     */
    public function export(DataExportRequest $request, string $resource): StreamedResponse
    {
        $allowedResources = ['citizens', 'staff', 'applications', 'services', 'departments'];

        if (! in_array($resource, $allowedResources, true)) {
            abort(404, 'Tài nguyên xuất dữ liệu không tồn tại hoặc không được hỗ trợ.');
        }

        return $this->exportService->export($resource, $request->validated());
    }
}
