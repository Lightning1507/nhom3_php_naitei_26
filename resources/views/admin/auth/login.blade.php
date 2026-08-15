<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập nội bộ - {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/admin/app.js'])
</head>
<body class="min-h-screen bg-surface px-4 py-8 text-gray-900 sm:px-6 lg:px-8">
    <main class="mx-auto flex min-h-[calc(100vh-4rem)] w-full max-w-md flex-col justify-center">
        <section class="card-container rounded-lg p-6 shadow-sm sm:p-8">
            <p class="text-sm font-semibold uppercase text-primary">Khu vực nội bộ</p>
            <h1 class="mt-2 text-2xl font-bold text-gray-950">Đăng nhập nội bộ</h1>
            <p class="mt-2 text-sm leading-6 text-gray-600">
                Dành cho Staff, Manager và Super Admin.
            </p>

            <form class="mt-6 space-y-5" method="POST" action="{{ route('admin.login.store') }}" novalidate>
                @csrf

                <div>
                    <label class="label normal-case tracking-normal" for="email">Email</label>
                    <input
                        id="email"
                        name="email"
                        type="email"
                        autocomplete="email"
                        value="{{ old('email') }}"
                        class="input-field rounded-lg px-4 py-3 text-base @error('email') input-error @enderror"
                    >
                    @error('email')
                        <p class="mt-2 text-sm font-medium text-danger">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="label normal-case tracking-normal" for="password">Mật khẩu</label>
                    <input
                        id="password"
                        name="password"
                        type="password"
                        autocomplete="current-password"
                        class="input-field rounded-lg px-4 py-3 text-base @error('password') input-error @enderror"
                    >
                    @error('password')
                        <p class="mt-2 text-sm font-medium text-danger">{{ $message }}</p>
                    @enderror
                </div>

                <button class="btn-primary w-full rounded-full py-3 text-base" type="submit">
                    Đăng nhập
                </button>
            </form>
        </section>
    </main>
</body>
</html>
