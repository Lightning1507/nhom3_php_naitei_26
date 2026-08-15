<?php

namespace App\Http\Requests\Api\V1\Auth;

use App\Http\Responses\ApiResponse;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rules\Password;

class RegisterCitizenRequest extends FormRequest
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
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)],
            'citizen_id' => ['required', 'digits:12', 'unique:users,citizen_id'],
            'date_of_birth' => ['required', 'date', 'before:today'],
            'phone' => ['required', 'string', 'max:30'],
            'address' => ['required', 'string', 'max:1000'],
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
            'email.required' => 'Vui lòng nhập email.',
            'email.email' => 'Email không hợp lệ.',
            'email.max' => 'Email không được vượt quá :max ký tự.',
            'email.unique' => 'Email này đã được sử dụng.',
            'password.required' => 'Vui lòng nhập mật khẩu.',
            'password.confirmed' => 'Xác nhận mật khẩu không khớp.',
            'password.min' => 'Mật khẩu phải có ít nhất :min ký tự.',
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
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => trim((string) $this->input('name')),
            'email' => mb_strtolower(trim((string) $this->input('email'))),
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
