import { Link } from 'react-router-dom';

export default function AuthShell({ children, description, title }) {
    return (
        <main className="min-h-screen bg-surface text-gray-900 md:grid md:grid-cols-2">
            <section className="hidden min-h-screen flex-col items-center justify-center bg-blue-50 px-12 text-center md:flex">
                <Link
                    aria-label="Public Service Management System"
                    className="flex size-24 items-center justify-center rounded-2xl bg-primary text-3xl font-bold text-white shadow-sm"
                    to="/"
                >
                    DV
                </Link>
                <h1 className="mt-8 max-w-lg text-3xl font-bold leading-tight text-gray-950">
                    Hệ thống Quản lý Dịch vụ Công
                </h1>
                <p className="mt-4 max-w-md text-base leading-7 text-gray-600">
                    Cổng thông tin để thực hiện thủ tục hành chính, tra cứu hồ sơ và theo dõi
                    tiến độ xử lý trực tuyến.
                </p>
            </section>

            <section className="flex min-h-screen items-center justify-center px-4 py-8 sm:px-6 lg:px-10">
                <div className="w-full max-w-md">
                    <div className="mb-8 text-center md:hidden">
                        <Link
                            aria-label="Public Service Management System"
                            className="mx-auto flex size-14 items-center justify-center rounded-xl bg-primary text-lg font-bold text-white"
                            to="/"
                        >
                            DV
                        </Link>
                        <p className="mt-3 text-sm font-semibold text-primary">Dịch vụ Công</p>
                    </div>

                    <div className="card-container rounded-lg p-6 shadow-sm sm:p-8">
                        <div className="mb-6">
                            <h2 className="text-3xl font-bold text-gray-950">{title}</h2>
                            {description && (
                                <p className="mt-2 text-sm leading-6 text-gray-600">{description}</p>
                            )}
                        </div>

                        {children}
                    </div>
                </div>
            </section>
        </main>
    );
}
