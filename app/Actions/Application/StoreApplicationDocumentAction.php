<?php

namespace App\Actions\Application;

use App\Enums\DocumentKind;
use App\Models\Application;
use App\Models\ApplicationDocument;
use App\Models\User;
use Illuminate\Http\UploadedFile;

class StoreApplicationDocumentAction
{
    public function execute(User $uploader, Application $application, UploadedFile $file): ApplicationDocument
    {
        $path = $file->store('applications/'.$application->id, 'local');

        return ApplicationDocument::query()->create([
            'application_id' => $application->id,
            'uploaded_by' => $uploader->id,
            'document_kind' => DocumentKind::Submission,
            'original_name' => $file->getClientOriginalName(),
            'disk' => 'local',
            'path' => $path,
            'mime_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
        ]);
    }
}
