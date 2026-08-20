<?php

namespace App\Actions\Application;

use App\Enums\ApplicationStatus;
use App\Enums\DocumentKind;
use App\Models\Application;
use App\Models\ApplicationDocument;
use App\Models\User;
use Illuminate\Http\UploadedFile;

class StoreApplicationDocumentAction
{
    public function execute(User $uploader, Application $application, UploadedFile $file, ?string $requirementCode = null): ApplicationDocument
    {
        $path = $file->store('applications/'.$application->id, 'local');

        $kind = $application->status === ApplicationStatus::SupplementRequired
            ? DocumentKind::Supplement
            : DocumentKind::Submission;

        return ApplicationDocument::query()->create([
            'application_id' => $application->id,
            'uploaded_by' => $uploader->id,
            'document_kind' => $kind,
            'original_name' => $file->getClientOriginalName(),
            'requirement_code' => $requirementCode,
            'disk' => 'local',
            'path' => $path,
            'mime_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
        ]);
    }
}
