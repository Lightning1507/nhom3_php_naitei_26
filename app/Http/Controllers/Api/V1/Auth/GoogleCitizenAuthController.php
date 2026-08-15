<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\CompleteGoogleCitizenRegistrationRequest;
use App\Http\Resources\Api\V1\UserResource;
use App\Http\Responses\ApiResponse;
use App\Models\User;
use App\Support\Auth\AuthEventLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\HttpFoundation\Response;

class GoogleCitizenAuthController extends Controller
{
    private const PENDING_SESSION_KEY = 'auth.google_citizen_registration';

    public function redirect(): RedirectResponse
    {
        return Socialite::driver('google')
            ->scopes(['openid', 'profile', 'email'])
            ->redirect();
    }

    public function callback(Request $request, AuthEventLogger $events): RedirectResponse
    {
        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (\Throwable $exception) {
            Log::warning('Google citizen login callback failed.', [
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
            ]);

            $events->log(
                action: 'citizen.google_login_failed',
                request: $request,
                description: 'Google citizen login callback failed.',
                metadata: [
                    'reason' => 'provider_callback_failed',
                    'exception' => $exception::class,
                ],
            );

            return redirect('/login?auth_error=google_callback_failed');
        }

        $email = mb_strtolower(trim((string) $googleUser->getEmail()));
        $name = trim((string) $googleUser->getName());

        if ($email === '') {
            $events->log(
                action: 'citizen.google_login_failed',
                request: $request,
                description: 'Google citizen login did not include an email.',
                metadata: ['reason' => 'missing_email'],
            );

            return redirect('/login?auth_error=google_missing_email');
        }

        $user = User::query()->where('email', $email)->first();

        if (! $user) {
            $request->session()->put(self::PENDING_SESSION_KEY, [
                'email' => $email,
                'name' => $name,
                'google_id' => $googleUser->getId(),
                'avatar' => $googleUser->getAvatar(),
            ]);

            return redirect('/auth/google/complete');
        }

        if (! $user->isCitizen() || ! $user->canAccessProtectedResources()) {
            $events->log(
                action: 'citizen.google_login_failed',
                actor: $user->isCitizen() ? $user : null,
                subject: $user,
                request: $request,
                description: 'Google citizen login denied.',
                metadata: [
                    'email' => $email,
                    'reason' => $user->isCitizen() ? 'inactive_account' : 'wrong_role',
                ],
            );

            return redirect('/login?auth_error=google_login_denied');
        }

        auth()->guard('web')->login($user);
        $request->session()->regenerate();
        $request->session()->forget(self::PENDING_SESSION_KEY);

        $events->log(
            action: 'citizen.google_login_succeeded',
            actor: $user,
            subject: $user,
            request: $request,
            description: 'Citizen logged in with Google.',
            metadata: ['email' => $email],
        );

        return redirect('/profile');
    }

    public function pending(Request $request): JsonResponse
    {
        $pending = $request->session()->get(self::PENDING_SESSION_KEY);

        if (! $pending) {
            return ApiResponse::error(
                message: 'Phiên đăng nhập Google đã hết hạn. Vui lòng thử lại.',
                status: Response::HTTP_NOT_FOUND,
            );
        }

        return ApiResponse::success(
            message: 'Lấy thông tin Google chờ bổ sung thành công.',
            data: [
                'email' => $pending['email'],
                'name' => $pending['name'],
            ],
        );
    }

    public function complete(
        CompleteGoogleCitizenRegistrationRequest $request,
        AuthEventLogger $events,
    ): JsonResponse {
        $pending = $request->session()->get(self::PENDING_SESSION_KEY);

        if (! $pending) {
            return ApiResponse::error(
                message: 'Phiên đăng nhập Google đã hết hạn. Vui lòng thử lại.',
                status: Response::HTTP_NOT_FOUND,
            );
        }

        $email = mb_strtolower(trim((string) $pending['email']));

        if (User::query()->where('email', $email)->exists()) {
            return ApiResponse::error(
                message: 'Email Google này đã được sử dụng. Vui lòng đăng nhập lại.',
                errors: ['email' => ['Email Google này đã được sử dụng.']],
                status: Response::HTTP_UNPROCESSABLE_ENTITY,
            );
        }

        $user = DB::transaction(fn (): User => User::query()->create([
            'name' => $request->string('name')->toString(),
            'email' => $email,
            'password' => Hash::make(Str::random(48)),
            'role' => UserRole::Citizen,
            'citizen_id' => $request->string('citizen_id')->toString(),
            'date_of_birth' => $request->date('date_of_birth')?->toDateString(),
            'phone' => $request->string('phone')->toString(),
            'address' => $request->string('address')->toString(),
            'is_active' => true,
        ]));

        auth()->guard('web')->login($user);
        $request->session()->regenerate();
        $request->session()->forget(self::PENDING_SESSION_KEY);

        $events->log(
            action: 'citizen.google_registration_completed',
            actor: $user,
            subject: $user,
            request: $request,
            description: 'Citizen completed Google registration.',
            metadata: ['email' => $email],
        );

        return ApiResponse::success(
            message: 'Hoàn tất đăng ký Google thành công.',
            data: UserResource::make($user),
            status: Response::HTTP_CREATED,
        );
    }
}
