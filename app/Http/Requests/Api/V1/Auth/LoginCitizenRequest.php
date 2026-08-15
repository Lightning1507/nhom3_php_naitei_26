<?php

namespace App\Http\Requests\Api\V1\Auth;

use App\Http\Responses\ApiResponse;
use App\Models\User;
use App\Support\Auth\AuthEventLogger;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class LoginCitizenRequest extends FormRequest
{
    private const MAX_ATTEMPTS = 5;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.required' => 'Vui lòng nhập email.',
            'email.email' => 'Email không hợp lệ.',
            'password.required' => 'Vui lòng nhập mật khẩu.',
            'password.string' => 'Mật khẩu không hợp lệ.',
        ];
    }

    public function authenticate(AuthEventLogger $events): User
    {
        $this->ensureIsNotRateLimited();

        $user = User::query()
            ->where('email', $this->string('email')->toString())
            ->first();

        if (
            ! $user
            || ! $user->isCitizen()
            || ! $user->canAccessProtectedResources()
            || ! Hash::check($this->string('password')->toString(), $user->password)
        ) {
            RateLimiter::hit($this->throttleKey());

            $events->log(
                action: 'citizen.login_failed',
                actor: $user?->isCitizen() ? $user : null,
                request: $this,
                description: 'Citizen login failed.',
                metadata: [
                    'email' => $this->string('email')->toString(),
                    'reason' => 'invalid_credentials',
                ],
            );

            throw new HttpResponseException(
                ApiResponse::error(
                    message: 'Thông tin đăng nhập không chính xác.',
                    status: 401,
                ),
            );
        }

        RateLimiter::clear($this->throttleKey());

        return $user;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'email' => mb_strtolower(trim((string) $this->input('email'))),
        ]);
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            ApiResponse::error(
                message: 'Dữ liệu không hợp lệ.',
                errors: $validator->errors()->toArray(),
                status: 422,
            ),
        );
    }

    private function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), self::MAX_ATTEMPTS)) {
            return;
        }

        throw new HttpResponseException(
            ApiResponse::error(
                message: 'Bạn đăng nhập sai quá nhiều lần. Vui lòng thử lại sau.',
                errors: [
                    'email' => ['Bạn đăng nhập sai quá nhiều lần. Vui lòng thử lại sau.'],
                ],
                status: 429,
            ),
        );
    }

    private function throttleKey(): string
    {
        return Str::transliterate($this->string('email')->toString().'|'.$this->ip());
    }
}
