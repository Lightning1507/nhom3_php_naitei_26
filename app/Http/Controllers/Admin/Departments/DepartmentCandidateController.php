<?php

namespace App\Http\Controllers\Admin\Departments;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;

class DepartmentCandidateController extends Controller
{
    private const RESULT_LIMIT = 20;

    public function managerCandidates(Request $request): JsonResponse
    {
        $this->authorize('create', Department::class);
        $term = $this->validatedSearchTerm($request);

        $candidates = $this->searchUsers(User::query()->eligibleDepartmentLeaders(), $term)
            ->orderBy('name')
            ->orderBy('id')
            ->limit(self::RESULT_LIMIT + 1)
            ->get(['id', 'name', 'email', 'role']);

        return $this->userCandidateResponse($candidates);
    }

    public function memberCandidates(Request $request, Department $department): JsonResponse
    {
        $this->authorize('addMember', $department);
        $term = $this->validatedSearchTerm($request);
        /** @var User $actor */
        $actor = $request->user();

        $query = $actor->isSuperAdmin()
            ? User::query()->eligibleDepartmentMembers()
            : User::query()->eligibleDepartmentStaff();
        $query->whereDoesntHave('departments', fn (Builder $departmentQuery): Builder => $departmentQuery
            ->whereKey($department->getKey()));

        $candidates = $this->searchUsers($query, $term)
            ->orderBy('name')
            ->orderBy('id')
            ->limit(self::RESULT_LIMIT + 1)
            ->get(['id', 'name', 'email', 'role']);

        return $this->userCandidateResponse($candidates);
    }

    private function validatedSearchTerm(Request $request): string
    {
        $input = ['search' => trim((string) $request->query('search'))];

        return Validator::make($input, [
            'search' => ['required', 'string', 'min:2', 'max:100'],
        ], [
            'search.required' => 'Vui lòng nhập ít nhất 2 ký tự để tìm kiếm.',
            'search.min' => 'Vui lòng nhập ít nhất 2 ký tự để tìm kiếm.',
            'search.max' => 'Từ khóa tìm kiếm không được vượt quá 100 ký tự.',
        ])->validate()['search'];
    }

    private function searchUsers(Builder $query, string $term): Builder
    {
        $escaped = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $term);
        $pattern = "%{$escaped}%";

        return $query->where(function (Builder $candidateQuery) use ($pattern): void {
            $candidateQuery
                ->whereRaw("name ILIKE ? ESCAPE E'\\\\'", [$pattern])
                ->orWhereRaw("email ILIKE ? ESCAPE E'\\\\'", [$pattern]);
        });
    }

    /**
     * @param  Collection<int, User>  $candidates
     */
    private function userCandidateResponse(Collection $candidates): JsonResponse
    {
        $hasMore = $candidates->count() > self::RESULT_LIMIT;
        $data = $candidates->take(self::RESULT_LIMIT)->map(fn (User $user): array => [
            'id' => $user->getKey(),
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role->value,
        ])->values();

        return response()->json([
            'data' => $data,
            'meta' => ['has_more' => $hasMore],
        ]);
    }
}
