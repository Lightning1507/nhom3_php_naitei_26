import { Link, useLocation } from 'react-router-dom';

export default function HomePage() {
    const location = useLocation();

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

                <div className="mt-8 flex flex-wrap gap-4">
                    <Link className="btn-primary rounded-xl px-6 py-4 text-base" to="/login">
                        Đăng nhập
                    </Link>
                    <Link className="btn-secondary rounded-xl px-6 py-4 text-base" to="/register">
                        Đăng ký
                    </Link>
                </div>
            </section>
        </main>
    );
}
