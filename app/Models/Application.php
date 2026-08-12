<?php

namespace App\Models;

use App\Enums\ApplicationStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
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
