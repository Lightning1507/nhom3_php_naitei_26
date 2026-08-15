import { useEffect, useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';

import {
    completeGoogleCitizenRegistration,
    getApiError,
    getPendingGoogleCitizen,
    rememberCitizenSession,
} from '../api/auth';
import { BrandMark } from '../components/AuthShell';
import FormField, { FieldError } from '../components/FormField';

const initialForm = {
    name: '',
    citizen_id: '',
    date_of_birth: '',
    phone: '',
    address: '',
};

export default function CompleteGoogleRegistrationPage() {
    const navigate = useNavigate();
    const [pendingGoogle, setPendingGoogle] = useState(null);
    const [form, setForm] = useState(initialForm);
    const [errors, setErrors] = useState({});
    const [message, setMessage] = useState('');
    const [isLoading, setIsLoading] = useState(true);
    const [isSubmitting, setIsSubmitting] = useState(false);

    useEffect(() => {
        let isMounted = true;

        async function loadPendingGoogle() {
            try {
                const response = await getPendingGoogleCitizen();

                if (!isMounted) {
                    return;
                }

                setPendingGoogle(response.data);
                setForm((current) => ({
                    ...current,
                    name: response.data.name ?? '',
                }));
            } catch {
                navigate('/login', {
                    replace: true,
                    state: {
                        flash: 'Phiên đăng nhập Google đã hết hạn. Vui lòng thử lại.',
                    },
                });
            } finally {
                if (isMounted) {
                    setIsLoading(false);
                }
            }
        }

        loadPendingGoogle();

        return () => {
            isMounted = false;
        };
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
            const response = await completeGoogleCitizenRegistration(form);

            rememberCitizenSession(response.data);
            navigate('/', { replace: true });
        } catch (error) {
            const apiError = getApiError(error);
            setMessage(apiError.message);
            setErrors(apiError.errors);
        } finally {
            setIsSubmitting(false);
        }
    }

    if (isLoading) {
        return (
            <main className="min-h-screen bg-surface px-4 py-8 text-gray-900 sm:px-6 lg:px-8">
                <section className="mx-auto flex min-h-[calc(100vh-4rem)] w-full max-w-5xl items-center justify-center">
                    <p className="rounded-lg border border-border bg-white px-5 py-4 text-sm font-semibold text-gray-600">
                        Đang kiểm tra thông tin Google...
                    </p>
                </section>
            </main>
        );
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
                        <p className="text-sm font-semibold uppercase text-primary">Đăng nhập Google</p>
                        <h1 className="mt-3 text-2xl font-bold leading-tight text-gray-950">
                            Bổ sung thông tin công dân
                        </h1>
                        <p className="mt-3 text-sm leading-6 text-gray-600">
                            Email đã được xác thực bởi Google. Vui lòng bổ sung thông tin định danh để tạo tài khoản công dân.
                        </p>
                        <div className="mt-5 rounded-md bg-white px-3 py-2 text-sm">
                            <p className="font-semibold text-gray-500">Email Google</p>
                            <p className="mt-1 break-all text-gray-950">{pendingGoogle.email}</p>
                        </div>
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
                                errors={errors.citizen_id}
                                helpText="CCCD gồm 12 chữ số và không thể thay đổi sau khi tạo tài khoản."
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
                            <Link className="text-center text-sm font-semibold text-primary" to="/login">
                                Quay lại đăng nhập
                            </Link>
                            <button className="btn-primary rounded-full px-8 py-3 text-base" disabled={isSubmitting}>
                                {isSubmitting ? 'Đang hoàn tất...' : 'Hoàn tất'}
                            </button>
                        </div>
                    </form>
                </div>
            </section>
        </main>
    );
}
