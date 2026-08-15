import { Link, useLocation, useNavigate } from 'react-router-dom';
import { useState } from 'react';

import { getApiError, loginCitizen } from '../api/auth';
import AuthShell from '../components/AuthShell';
import FormField from '../components/FormField';

const initialForm = {
    email: '',
    password: '',
};

export default function LoginPage() {
    const location = useLocation();
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
            await loginCitizen(form);
            navigate('/', {
                replace: true,
                state: {
                    flash: 'Đăng nhập thành công.',
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
            description="Đăng nhập bằng email đã đăng ký để theo dõi hồ sơ và sử dụng dịch vụ công trực tuyến."
            title="Đăng nhập"
        >
            {location.state?.flash && (
                <p className="mb-5 rounded-lg bg-emerald-50 px-4 py-3 text-sm font-semibold text-success">
                    {location.state.flash}
                </p>
            )}

            <form noValidate onSubmit={submitForm}>
                <div className="space-y-5">
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
                        autoComplete="current-password"
                        errors={errors.password}
                        label="Mật khẩu"
                        name="password"
                        onChange={updateField}
                        type="password"
                        value={form.password}
                    />

                    {message && (
                        <p className="rounded-lg bg-red-50 px-4 py-3 text-sm font-semibold text-danger">
                            {message}
                        </p>
                    )}

                    <button className="btn-primary w-full rounded-full py-3 text-base" disabled={isSubmitting}>
                        {isSubmitting ? 'Đang đăng nhập...' : 'Đăng nhập'}
                    </button>
                </div>
            </form>

            <p className="mt-6 text-center text-sm text-gray-600">
                Bạn chưa có tài khoản?{' '}
                <Link className="font-semibold text-primary hover:text-primary-hover" to="/register">
                    Đăng ký ngay
                </Link>
            </p>
        </AuthShell>
    );
}
