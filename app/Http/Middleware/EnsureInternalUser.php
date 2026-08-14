<?php

namespace App\Http\Middleware;

use App\Support\Auth\AuthEventLogger;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final readonly class EnsureInternalUser
{
    public function __construct(private AuthEventLogger $events) {}

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->guest(route('admin.login'));
        }

        if (! $user->canAccessProtectedResources()) {
            $this->events->accessDenied($user, $request, 'inactive_account');

            abort(Response::HTTP_FORBIDDEN);
        }

        if (! $user->isInternalUser()) {
            $this->events->accessDenied($user, $request, 'wrong_role', [
                'required_role' => 'internal',
                'actual_role' => $user->role->value,
            ]);

            abort(Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}
