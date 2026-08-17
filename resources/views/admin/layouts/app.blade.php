<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', config('app.name'))</title>
    @vite(['resources/css/app.css', 'resources/js/admin/app.js'])
</head>
<body class="min-h-screen bg-slate-100 text-slate-900">
    <header class="border-b border-slate-200 bg-white">
        <div class="mx-auto max-w-6xl px-6 py-4">
            <a href="{{ route('admin.dashboard') }}" class="font-semibold">
                {{ config('app.name') }}
            </a>
            <form class="float-right" method="POST" action="{{ route('admin.logout') }}">
                @csrf
                <button class="text-sm font-semibold text-primary" type="submit">
                    Đăng xuất
                </button>
            </form>
        </div>
    </header>

    <main class="mx-auto max-w-6xl px-6 py-10" x-data>
        @yield('content')
    </main>
</body>
</html>
