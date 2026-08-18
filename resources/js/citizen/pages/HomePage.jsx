import { useEffect, useState } from 'react';
import { Link, useLocation, useNavigate } from 'react-router-dom';

import { forgetCitizenSession, getRememberedCitizen, logoutCitizen, rememberCitizenSession } from '../api/auth';
import { fetchCitizenProfile } from '../api/profile';

export default function HomePage() {
    const location = useLocation();
    const navigate = useNavigate();
    const [citizen, setCitizen] = useState(() => getRememberedCitizen());
    const [flash, setFlash] = useState(location.state?.flash ?? '');
    const [isCheckingSession, setIsCheckingSession] = useState(() => !getRememberedCitizen());
    const [isLoggingOut, setIsLoggingOut] = useState(false);

    useEffect(() => {
        if (citizen) {
            setIsCheckingSession(false);

            return undefined;
        }

        let isMounted = true;

        async function syncAuthenticatedCitizen() {
            try {
                const response = await fetchCitizenProfile();

                if (!isMounted) {
                    return;
                }

                rememberCitizenSession(response.data);
                setCitizen(response.data);
            } catch {
                forgetCitizenSession();
            } finally {
                if (isMounted) {
                    setIsCheckingSession(false);
                }
            }
        }

        syncAuthenticatedCitizen();

        return () => {
            isMounted = false;
        };
    }, [citizen]);

    useEffect(() => {
        if (!flash) {
            return undefined;
        }

        const timeout = window.setTimeout(() => {
            setFlash('');
            window.history.replaceState({}, document.title);
        }, 4000);

        return () => window.clearTimeout(timeout);
    }, [flash]);

    async function handleLogout() {
        setIsLoggingOut(true);
        setFlash('');

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
        <main className="min-h-screen bg-surface px-4 py-6 text-gray-900 sm:px-6 lg:px-8">
            <section className="mx-auto flex min-h-[calc(100vh-3rem)] w-full max-w-6xl flex-col">
                <header className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <Link className="text-sm font-semibold uppercase text-primary" to="/">
                        Cổng công dân
                    </Link>

                    {citizen ? (
                        <div className="flex flex-wrap items-center gap-3 sm:justify-end">
                            <div className="text-left sm:text-right">
                                <p className="text-sm font-semibold text-gray-950">{citizen.name}</p>
                                <p className="text-xs text-gray-500">{citizen.email}</p>
                            </div>
                            <Link className="btn-primary rounded-xl px-4 py-2 text-sm" to="/profile">
                                Hồ sơ
                            </Link>
                            <button
                                className="btn-secondary rounded-xl px-4 py-2 text-sm"
                                disabled={isLoggingOut}
                                onClick={handleLogout}
                                type="button"
                            >
                                {isLoggingOut ? 'Đang đăng xuất...' : 'Đăng xuất'}
                            </button>
                        </div>
                    ) : !isCheckingSession ? (
                        <div className="flex flex-wrap gap-3 sm:justify-end">
                            <Link className="btn-secondary rounded-xl px-4 py-2 text-sm" to="/login">
                                Đăng nhập
                            </Link>
                            <Link className="btn-primary rounded-xl px-4 py-2 text-sm" to="/register">
                                Đăng ký
                            </Link>
                        </div>
                    ) : null}
                </header>

                <div className="flex flex-1 flex-col justify-center py-16">
                    {flash && (
                        <p className="mb-6 w-fit rounded-xl bg-emerald-50 px-4 py-3 text-sm font-semibold text-success">
                            {flash}
                        </p>
                    )}

                    <h1 className="mt-4 max-w-3xl text-4xl font-bold tracking-tight text-gray-950 sm:text-6xl">
                        Hệ thống Quản lý Dịch vụ Công
                    </h1>
                    <p className="mt-6 max-w-2xl text-lg leading-8 text-gray-600">
                        Dịch vụ công trực tuyến cho công dân.
                    </p>

                    <div className="mt-8 flex flex-wrap gap-4">
                        <Link className="btn-primary rounded-xl px-6 py-4 text-base shadow-sm" to="/services">
                            Danh mục Dịch vụ
                        </Link>
                        {!citizen && !isCheckingSession && (
                            <>
                                <Link className="btn-secondary rounded-xl px-6 py-4 text-base" to="/login">
                                    Đăng nhập
                                </Link>
                                <Link className="btn-secondary rounded-xl px-6 py-4 text-base" to="/register">
                                    Đăng ký
                                </Link>
                            </>
                        )}
                    </div>
                </div>
            </section>
        </main>
    );
}
