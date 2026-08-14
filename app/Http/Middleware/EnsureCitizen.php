<?php

namespace App\Http\Middleware;

use App\Http\Responses\ApiResponse;
use App\Support\Auth\AuthEventLogger;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final readonly class EnsureCitizen
{
    public function __construct(private AuthEventLogger $events) {}

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return ApiResponse::error('Authentication is required.', null, Response::HTTP_UNAUTHORIZED);
        }

        if (! $user->canAccessProtectedResources()) {
            $this->events->accessDenied($user, $request, 'inactive_account');

            return ApiResponse::error('This account cannot access protected resources.', null, Response::HTTP_FORBIDDEN);
        }

        if (! $user->isCitizen()) {
            $this->events->accessDenied($user, $request, 'wrong_role', [
                'required_role' => 'citizen',
                'actual_role' => $user->role->value,
            ]);

            return ApiResponse::error('You are not allowed to access this resource.', null, Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}
