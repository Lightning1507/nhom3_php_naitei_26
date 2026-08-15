import { useState } from 'react';
import { Link, useLocation, useNavigate } from 'react-router-dom';

import { forgetCitizenSession, getRememberedCitizen, logoutCitizen } from '../api/auth';

export default function HomePage() {
    const location = useLocation();
    const navigate = useNavigate();
    const [citizen, setCitizen] = useState(() => getRememberedCitizen());
    const [isLoggingOut, setIsLoggingOut] = useState(false);

    async function handleLogout() {
        setIsLoggingOut(true);

        try {
            await logoutCitizen();
        } finally {
            forgetCitizenSession();
            setCitizen(null);
            setIsLoggingOut(false);
            navigate('/login', {
                replace: true,
                state: {
                    flash: 'Đăng xuất thành công.',
                },
            });
        }
    }

    return (
        <main className="min-h-screen bg-surface px-4 py-10 text-gray-900 sm:px-6 lg:px-8">
            <section className="mx-auto flex min-h-[calc(100vh-5rem)] w-full max-w-6xl flex-col justify-center">
                {location.state?.flash && (
                    <p className="mb-6 w-fit rounded-xl bg-emerald-50 px-4 py-3 text-sm font-semibold text-success">
                        {location.state.flash}
                    </p>
                )}

                <p className="text-sm font-semibold uppercase text-primary">Cổng công dân</p>
                <h1 className="mt-4 max-w-3xl text-4xl font-bold tracking-tight text-gray-950 sm:text-6xl">
                    Hệ thống Quản lý Dịch vụ Công
                </h1>
                <p className="mt-6 max-w-2xl text-lg leading-8 text-gray-600">
                    Dịch vụ công trực tuyến cho công dân.
                </p>

                {citizen ? (
                    <div className="mt-8 max-w-xl rounded-lg border border-border bg-white p-5 shadow-sm">
                        <p className="text-sm font-semibold text-gray-500">Đang đăng nhập với tài khoản</p>
                        <p className="mt-2 text-xl font-bold text-gray-950">{citizen.name}</p>
                        <p className="mt-1 text-sm text-gray-600">{citizen.email}</p>
                        <button
                            className="btn-secondary mt-5 rounded-xl px-6 py-3 text-base"
                            disabled={isLoggingOut}
                            onClick={handleLogout}
                            type="button"
                        >
                            {isLoggingOut ? 'Đang đăng xuất...' : 'Đăng xuất'}
                        </button>
                    </div>
                ) : (
                    <div className="mt-8 flex flex-wrap gap-4">
                        <Link className="btn-primary rounded-xl px-6 py-4 text-base" to="/login">
                            Đăng nhập
                        </Link>
                        <Link className="btn-secondary rounded-xl px-6 py-4 text-base" to="/register">
                            Đăng ký
                        </Link>
                    </div>
                )}
            </section>
        </main>
    );
}
