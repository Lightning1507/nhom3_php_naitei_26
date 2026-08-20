<?php

namespace App\Models;

use App\Enums\ApplicationStatus;
use App\Support\ServiceSchema;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'application_code',
    'citizen_id',
    'service_type_id',
    'assigned_staff_id',
    'status',
    'form_data',
    'submitted_at',
    'processing_started_at',
    'completed_at',
    'result_note',
    'rejection_reason',
])]
class Application extends Model
{
    use HasFactory, SoftDeletes;

    public function citizen(): BelongsTo
    {
        return $this->belongsTo(User::class, 'citizen_id');
    }

    public function serviceType(): BelongsTo
    {
        return $this->belongsTo(ServiceType::class);
    }

    public function assignedStaff(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_staff_id');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(ApplicationDocument::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(ApplicationAssignment::class);
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(ApplicationStatusHistory::class);
    }

    public function activeAssignment(): ?ApplicationAssignment
    {
        return $this->assignments
            ->first(fn (ApplicationAssignment $assignment) => $assignment->ended_at === null);
    }

    public function isOverdue(): bool
    {
        if ($this->completed_at !== null) {
            return false;
        }

        $processingTimeDays = (int) ($this->serviceType?->processing_time_days ?? 0);

        return $this->submitted_at !== null
            && $this->submitted_at->addDays($processingTimeDays)->isPast();
    }

    public function supplementNote(): ?string
    {
        $latest = $this->statusHistories
            ->filter(fn (ApplicationStatusHistory $history) => $history->to_status === ApplicationStatus::SupplementRequired)
            ->sortByDesc(fn (ApplicationStatusHistory $history) => $history->created_at?->timestamp ?? 0)
            ->first();

        return $latest?->note;
    }

    /**
     * @return array<int, array{code: string, label: string}>
     */
    public function missingRequiredDocuments(): array
    {
        if (! in_array($this->status, [ApplicationStatus::Received, ApplicationStatus::SupplementRequired], true)) {
            return [];
        }

        if (! $this->relationLoaded('serviceType') || ! $this->relationLoaded('documents')) {
            return [];
        }

        $requirements = ServiceSchema::normalizeDocumentRequirements($this->serviceType->document_requirements);

        $uploadedCodes = $this->documents
            ->pluck('requirement_code')
            ->filter()
            ->flip();

        $missing = [];

        foreach ($requirements as $requirement) {
            if ($requirement['required'] && ! $uploadedCodes->has($requirement['code'])) {
                $missing[] = ['code' => $requirement['code'], 'label' => $requirement['label']];
            }
        }

        return $missing;
    }

    public function scopeVisibleTo(Builder $query, User $actor): Builder
    {
        if ($actor->isSuperAdmin()) {
            return $query;
        }

        if ($actor->isManager()) {
            $departmentIds = $actor->ledDepartments()->pluck('id');

            return $query->whereHas(
                'serviceType',
                fn (Builder $serviceQuery) => $serviceQuery->whereIn('responsible_department_id', $departmentIds)
            );
        }

        return $query->where('assigned_staff_id', $actor->getKey());
    }

    public function scopeClaimableBy(Builder $query, User $actor): Builder
    {
        $departmentIds = $actor->departments()->pluck('departments.id');

        return $query
            ->whereNull('assigned_staff_id')
            ->where('status', ApplicationStatus::Received)
            ->whereHas(
                'serviceType',
                fn (Builder $serviceQuery) => $serviceQuery->whereIn('responsible_department_id', $departmentIds)
            );
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ApplicationStatus::class,
            'form_data' => 'array',
            'submitted_at' => 'datetime',
            'processing_started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }
}
