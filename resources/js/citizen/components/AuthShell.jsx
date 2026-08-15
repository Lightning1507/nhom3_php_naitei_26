import { Link } from 'react-router-dom';

export function BrandMark({ className = 'size-10' }) {
    return (
        <span className={`flex items-center justify-center rounded-lg bg-primary text-white shadow-sm ${className}`}>
            <svg aria-hidden="true" className="size-2/3" fill="none" viewBox="0 0 24 24">
                <path d="M4 10h16" stroke="currentColor" strokeLinecap="round" strokeWidth="1.8" />
                <path d="M6 10v8M10 10v8M14 10v8M18 10v8" stroke="currentColor" strokeLinecap="round" strokeWidth="1.8" />
                <path d="M4.5 18h15M3.5 21h17" stroke="currentColor" strokeLinecap="round" strokeWidth="1.8" />
                <path
                    d="M5 7.5 12 3l7 4.5H5Z"
                    stroke="currentColor"
                    strokeLinejoin="round"
                    strokeWidth="1.8"
                />
            </svg>
        </span>
    );
}

export default function AuthShell({ children, description, title }) {
    return (
        <main className="min-h-screen bg-surface px-4 py-8 text-gray-900 sm:px-6 lg:px-8">
            <section className="mx-auto grid min-h-[calc(100vh-4rem)] w-full max-w-5xl items-center gap-8 md:grid-cols-[1fr_420px]">
                <div className="max-w-xl">
                    <Link className="flex w-fit items-center gap-3 text-sm font-semibold text-primary" to="/">
                        <BrandMark className="size-12" />
                        Hệ thống Quản lý Dịch vụ Công
                    </Link>
                    <h2 className="mt-8 text-4xl font-bold leading-tight text-gray-950">
                        Quản lý hồ sơ dịch vụ công của bạn
                    </h2>
                    <p className="mt-4 text-base leading-7 text-gray-600">
                        Đăng nhập để nộp hồ sơ, theo dõi trạng thái xử lý và cập nhật thông tin
                        liên hệ khi cần.
                    </p>
                    <div className="mt-7 grid gap-3 text-sm text-gray-700">
                        <p className="rounded-lg border border-border bg-white px-4 py-3">
                            Theo dõi tiến độ xử lý hồ sơ trực tuyến
                        </p>
                        <p className="rounded-lg border border-border bg-white px-4 py-3">
                            Sử dụng một tài khoản công dân cho các dịch vụ
                        </p>
                    </div>
                </div>

                <div className="card-container rounded-lg p-6 shadow-sm sm:p-7">
                    <div className="mb-5 text-center">
                        <h1 className="text-2xl font-bold text-gray-950">{title}</h1>
                        {description && <p className="mt-2 text-sm leading-6 text-gray-600">{description}</p>}
                    </div>

                    {children}
                </div>
            </section>
        </main>
    );
}
