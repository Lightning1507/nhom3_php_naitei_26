<?php

namespace App\Http\Requests\Api\V1\Auth;

use App\Http\Responses\ApiResponse;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class CompleteGoogleCitizenRegistrationRequest extends FormRequest
{
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
            'name' => ['required', 'string', 'max:255'],
            'citizen_id' => ['required', 'digits:12', 'unique:users,citizen_id'],
            'date_of_birth' => ['required', 'date', 'before:today'],
            'phone' => ['required', 'string', 'max:30'],
            'address' => ['required', 'string', 'max:1000'],
            'email' => ['prohibited'],
            'role' => ['prohibited'],
            'password' => ['prohibited'],
            'is_active' => ['prohibited'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Vui lòng nhập họ và tên.',
            'name.string' => 'Họ và tên không hợp lệ.',
            'name.max' => 'Họ và tên không được vượt quá :max ký tự.',
            'citizen_id.required' => 'Vui lòng nhập số CCCD.',
            'citizen_id.digits' => 'Số CCCD phải gồm đúng :digits chữ số.',
            'citizen_id.unique' => 'Số CCCD này đã được sử dụng.',
            'date_of_birth.required' => 'Vui lòng nhập ngày sinh.',
            'date_of_birth.date' => 'Ngày sinh không hợp lệ.',
            'date_of_birth.before' => 'Ngày sinh phải trước ngày hôm nay.',
            'phone.required' => 'Vui lòng nhập số điện thoại.',
            'phone.string' => 'Số điện thoại không hợp lệ.',
            'phone.max' => 'Số điện thoại không được vượt quá :max ký tự.',
            'address.required' => 'Vui lòng nhập địa chỉ.',
            'address.string' => 'Địa chỉ không hợp lệ.',
            'address.max' => 'Địa chỉ không được vượt quá :max ký tự.',
            'email.prohibited' => 'Email Google không thể thay đổi tại đây.',
            'role.prohibited' => 'Không thể thay đổi vai trò.',
            'password.prohibited' => 'Không thể gửi mật khẩu trong luồng Google.',
            'is_active.prohibited' => 'Không thể thay đổi trạng thái tài khoản.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => trim((string) $this->input('name')),
            'citizen_id' => preg_replace('/\s+/', '', (string) $this->input('citizen_id')),
            'phone' => trim((string) $this->input('phone')),
            'address' => trim((string) $this->input('address')),
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
}
