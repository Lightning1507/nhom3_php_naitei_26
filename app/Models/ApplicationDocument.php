<?php

namespace App\Models;

use App\Enums\DocumentKind;
use App\Support\ServiceSchema;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'application_id',
    'uploaded_by',
    'document_kind',
    'original_name',
    'requirement_code',
    'disk',
    'path',
    'mime_type',
    'file_size',
])]
class ApplicationDocument extends Model
{
    use HasFactory, SoftDeletes;

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function requirementLabel(): Attribute
    {
        return Attribute::get(function (): ?string {
            if ($this->requirement_code === null) {
                return null;
            }

            $service = $this->application?->serviceType;

            if ($service === null) {
                return null;
            }

            $label = collect(ServiceSchema::normalizeDocumentRequirements($service->document_requirements))
                ->firstWhere('code', $this->requirement_code)['label'] ?? null;

            return $label !== null ? (string) $label : null;
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'document_kind' => DocumentKind::class,
            'file_size' => 'integer',
        ];
    }
}
