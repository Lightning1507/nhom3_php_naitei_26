<?php

namespace App\Http\Middleware;

use App\Http\Responses\ApiResponse;
use App\Models\User;
use App\Support\Auth\AuthEventLogger;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

final readonly class EnsureInternalUser
{
    public function __construct(private AuthEventLogger $events) {}

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $authenticatedUser = $request->user();

        if (! $authenticatedUser) {
            return $this->deny($request, 'Vui lòng đăng nhập.', Response::HTTP_UNAUTHORIZED);
        }

        $user = User::withTrashed()->find($authenticatedUser->getAuthIdentifier());

        if (! $user) {
            Auth::forgetUser();

            return $this->deny($request, 'Vui lòng đăng nhập.', Response::HTTP_UNAUTHORIZED);
        }

        Auth::setUser($user);
        $request->setUserResolver(static fn (): User => $user);

        if (! $user->canAccessProtectedResources()) {
            $this->events->accessDenied($user, $request, 'inactive_account');

            return $this->deny(
                $request,
                'Tài khoản này không thể truy cập khu vực nội bộ.',
                Response::HTTP_FORBIDDEN,
            );
        }

        if (! ($user->isStaff() || $user->isManager() || $user->isSuperAdmin())) {
            $this->events->accessDenied($user, $request, 'wrong_role', [
                'required_role' => 'internal',
                'actual_role' => $user->role->value,
            ]);

            return $this->deny(
                $request,
                'Bạn không có quyền truy cập khu vực nội bộ.',
                Response::HTTP_FORBIDDEN,
            );
        }

        return $next($request);
    }

    private function deny(Request $request, string $message, int $status): Response
    {
        if ($request->is('api/*')) {
            return ApiResponse::error($message, null, $status);
        }

        return response($message, $status);
    }
}
