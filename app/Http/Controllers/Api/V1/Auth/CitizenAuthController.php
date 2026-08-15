<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Actions\Auth\RegisterCitizen;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\LoginCitizenRequest;
use App\Http\Requests\Api\V1\Auth\RegisterCitizenRequest;
use App\Http\Resources\Api\V1\UserResource;
use App\Http\Responses\ApiResponse;
use App\Support\Auth\AuthEventLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CitizenAuthController extends Controller
{
    public function register(
        RegisterCitizenRequest $request,
        RegisterCitizen $registerCitizen,
        AuthEventLogger $events,
    ): JsonResponse {
        $user = $registerCitizen->handle($request->validated());

        $events->log(
            action: 'citizen.registered',
            actor: $user,
            subject: $user,
            request: $request,
            description: 'Citizen registered.',
        );

        return ApiResponse::success(
            message: 'Đăng ký tài khoản công dân thành công.',
            data: UserResource::make($user),
            status: 201,
        );
    }

    public function login(LoginCitizenRequest $request, AuthEventLogger $events): JsonResponse
    {
        $user = $request->authenticate($events);

        auth()->guard('web')->login($user);
        $request->session()->regenerate();

        $events->log(
            action: 'citizen.login_succeeded',
            actor: $user,
            subject: $user,
            request: $request,
            description: 'Citizen login succeeded.',
        );

        return ApiResponse::success(
            message: 'Đăng nhập thành công.',
            data: UserResource::make($user),
        );
    }

    public function logout(Request $request, AuthEventLogger $events): JsonResponse
    {
        $user = $request->user();

        if ($user) {
            $events->log(
                action: 'citizen.logout',
                actor: $user,
                subject: $user,
                request: $request,
                description: 'Citizen logged out.',
            );
        }

        auth()->guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        auth()->forgetGuards();

        return ApiResponse::success(
            message: 'Đăng xuất thành công.',
            data: [],
        );
    }
}
