<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['name', 'code', 'address', 'leader_id'])]
class Department extends Model
{
    use HasFactory, SoftDeletes;

    public function leader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'leader_id')->withTrashed();
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withTimestamps();
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withTimestamps()
            ->withTrashed();
    }

    public function serviceTypes(): HasMany
    {
        return $this->hasMany(ServiceType::class, 'responsible_department_id');
    }

    public function applicationAssignments(): HasMany
    {
        return $this->hasMany(ApplicationAssignment::class);
    }

    public function scopeVisibleTo(Builder $query, User $actor): Builder
    {
        if ($actor->isSuperAdmin()) {
            return $query;
        }

        if ($actor->isManager()) {
            return $query->where('leader_id', $actor->getKey());
        }

        return $query->whereRaw('1 = 0');
    }

    public function scopeWithStructureCounts(Builder $query): Builder
    {
        return $query->withCount(['members', 'serviceTypes']);
    }

    public function isActive(): bool
    {
        return ! $this->trashed();
    }

    public function isArchived(): bool
    {
        return $this->trashed();
    }

    public function hasEligibleLeader(): bool
    {
        $leader = $this->leader;

        return $leader !== null
            && $leader->isManager()
            && $leader->canAccessProtectedResources();
    }

    public function resolveRouteBindingQuery($query, $value, $field = null)
    {
        return parent::resolveRouteBindingQuery($query, $value, $field)->withTrashed();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'lock_version' => 'integer',
            'deleted_at' => 'datetime',
        ];
    }
}
