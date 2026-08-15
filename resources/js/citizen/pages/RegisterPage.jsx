import { Link, useNavigate } from 'react-router-dom';
import { useState } from 'react';

import { getApiError, registerCitizen } from '../api/auth';
import AuthShell from '../components/AuthShell';
import FormField, { FieldError } from '../components/FormField';

const initialForm = {
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    citizen_id: '',
    date_of_birth: '',
    phone: '',
    address: '',
};

export default function RegisterPage() {
    const navigate = useNavigate();
    const [form, setForm] = useState(initialForm);
    const [errors, setErrors] = useState({});
    const [message, setMessage] = useState('');
    const [isSubmitting, setIsSubmitting] = useState(false);

    function updateField(event) {
        setForm((current) => ({
            ...current,
            [event.target.name]: event.target.value,
        }));
    }

    async function submitForm(event) {
        event.preventDefault();
        setErrors({});
        setMessage('');
        setIsSubmitting(true);

        try {
            await registerCitizen(form);
            navigate('/login', {
                replace: true,
                state: {
                    flash: 'Đăng ký thành công. Vui lòng đăng nhập.',
                },
            });
        } catch (error) {
            const apiError = getApiError(error);
            setMessage(apiError.message);
            setErrors(apiError.errors);
        } finally {
            setIsSubmitting(false);
        }
    }

    return (
        <AuthShell
            description="Tạo tài khoản công dân với thông tin định danh bắt buộc để sử dụng dịch vụ công trực tuyến."
            title="Đăng ký"
        >
            <form noValidate onSubmit={submitForm}>
                <div className="space-y-5">
                    <FormField
                        autoComplete="name"
                        errors={errors.name}
                        label="Họ và tên"
                        name="name"
                        onChange={updateField}
                        value={form.name}
                    />
                    <FormField
                        errors={errors.citizen_id}
                        helpText="CCCD gồm 12 chữ số và không thể thay đổi sau khi đăng ký."
                        label="Số CCCD"
                        name="citizen_id"
                        onChange={updateField}
                        value={form.citizen_id}
                    />
                    <FormField
                        autoComplete="email"
                        errors={errors.email}
                        label="Email"
                        name="email"
                        onChange={updateField}
                        type="email"
                        value={form.email}
                    />
                    <FormField
                        errors={errors.date_of_birth}
                        label="Ngày sinh"
                        name="date_of_birth"
                        onChange={updateField}
                        type="date"
                        value={form.date_of_birth}
                    />
                    <FormField
                        autoComplete="tel"
                        errors={errors.phone}
                        label="Số điện thoại"
                        name="phone"
                        onChange={updateField}
                        value={form.phone}
                    />
                    <div>
                        <label className="label normal-case tracking-normal" htmlFor="address">
                            Địa chỉ
                        </label>
                        <textarea
                            className={`input-field min-h-24 resize-y rounded-lg px-4 py-3 text-base ${
                                errors.address ? 'input-error' : ''
                            }`}
                            id="address"
                            name="address"
                            onChange={updateField}
                            value={form.address}
                        />
                        <FieldError errors={errors.address} />
                    </div>
                    <FormField
                        autoComplete="new-password"
                        errors={errors.password}
                        helpText="Mật khẩu tối thiểu 8 ký tự."
                        label="Mật khẩu"
                        name="password"
                        onChange={updateField}
                        type="password"
                        value={form.password}
                    />
                    <FormField
                        autoComplete="new-password"
                        errors={errors.password_confirmation}
                        label="Xác nhận mật khẩu"
                        name="password_confirmation"
                        onChange={updateField}
                        type="password"
                        value={form.password_confirmation}
                    />

                    {message && (
                        <p className="rounded-lg bg-red-50 px-4 py-3 text-sm font-semibold text-danger">
                            {message}
                        </p>
                    )}

                    <button className="btn-primary w-full rounded-full py-3 text-base" disabled={isSubmitting}>
                        {isSubmitting ? 'Đang tạo tài khoản...' : 'Đăng ký'}
                    </button>
                </div>
            </form>

            <p className="mt-6 text-center text-sm text-gray-600">
                Đã có tài khoản?{' '}
                <Link className="font-semibold text-primary hover:text-primary-hover" to="/login">
                    Đăng nhập
                </Link>
            </p>
        </AuthShell>
    );
}
