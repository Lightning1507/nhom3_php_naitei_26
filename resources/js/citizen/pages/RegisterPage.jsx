import { Link, useNavigate } from 'react-router-dom';
import { useEffect, useState } from 'react';

import { getApiError, getRememberedCitizen, registerCitizen } from '../api/auth';
import { BrandMark } from '../components/AuthShell';
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

    useEffect(() => {
        if (getRememberedCitizen()) {
            navigate('/profile', { replace: true });
        }
    }, [navigate]);

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
        <main className="min-h-screen bg-surface px-4 py-6 text-gray-900 sm:px-6 lg:px-8">
            <section className="mx-auto w-full max-w-5xl">
                <header className="mb-6">
                    <Link className="flex items-center gap-3 text-sm font-semibold text-primary" to="/">
                        <BrandMark className="size-10" />
                        Hệ thống Quản lý Dịch vụ Công
                    </Link>
                </header>

                <div className="grid items-start gap-6 lg:grid-cols-[280px_1fr]">
                    <aside className="rounded-lg border border-border bg-blue-50 p-5">
                        <p className="text-sm font-semibold uppercase text-primary">Tài khoản công dân</p>
                        <h1 className="mt-3 text-2xl font-bold leading-tight text-gray-950">
                            Đăng ký sử dụng dịch vụ công
                        </h1>
                        <p className="mt-3 text-sm leading-6 text-gray-600">
                            Thông tin định danh giúp bảo vệ tài khoản và hồ sơ của bạn.
                        </p>
                        <ul className="mt-5 space-y-3 text-sm text-gray-700">
                            <li className="rounded-md bg-white px-3 py-2">CCCD là mã định danh duy nhất</li>
                            <li className="rounded-md bg-white px-3 py-2">Email dùng để đăng nhập</li>
                            <li className="rounded-md bg-white px-3 py-2">Thông tin liên hệ phục vụ xử lý hồ sơ</li>
                        </ul>
                    </aside>

                    <form className="card-container rounded-lg p-5 shadow-sm sm:p-6" noValidate onSubmit={submitForm}>
                        <div className="grid gap-x-4 gap-y-4 md:grid-cols-2">
                            <FormField
                                autoComplete="name"
                                errors={errors.name}
                                label="Họ và tên"
                                name="name"
                                onChange={updateField}
                                value={form.name}
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
                                errors={errors.citizen_id}
                                helpText="CCCD gồm 12 chữ số và không thể thay đổi sau khi đăng ký."
                                label="Số CCCD"
                                name="citizen_id"
                                onChange={updateField}
                                value={form.citizen_id}
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
                            <div className="md:col-span-2">
                                <label className="label mb-1.5 normal-case tracking-normal" htmlFor="address">
                                    Địa chỉ
                                </label>
                                <textarea
                                    className={`input-field min-h-20 resize-y rounded-lg px-3.5 py-2.5 text-sm ${
                                        errors.address ? 'input-error' : ''
                                    }`}
                                    id="address"
                                    name="address"
                                    onChange={updateField}
                                    value={form.address}
                                />
                                <FieldError errors={errors.address} />
                            </div>
                        </div>

                        {message && (
                            <p className="mt-6 rounded-lg bg-red-50 px-4 py-3 text-sm font-semibold text-danger">
                                {message}
                            </p>
                        )}

                        <div className="mt-6 flex flex-col-reverse items-stretch gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <p className="text-center text-sm text-gray-600 sm:text-left">
                                Đã có tài khoản?{' '}
                                <Link className="font-semibold text-primary hover:text-primary-hover" to="/login">
                                    Đăng nhập
                                </Link>
                            </p>
                            <button className="btn-primary rounded-full px-8 py-3 text-base" disabled={isSubmitting}>
                                {isSubmitting ? 'Đang tạo tài khoản...' : 'Đăng ký'}
                            </button>
                        </div>
                    </form>
                </div>
            </section>
        </main>
    );
}
