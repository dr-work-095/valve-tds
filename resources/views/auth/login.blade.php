<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title }} · ValveTDS</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-full bg-slate-50 text-slate-900">
    <main class="flex min-h-screen items-center justify-center px-4 py-12">
        <section class="w-full max-w-md rounded-xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
            <a href="{{ $admin ? route('admin.login') : route('login') }}" class="mb-8 flex items-center gap-3">
                <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-blue-600 text-white">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path d="M5 7h14M5 12h14M5 17h14"/><circle cx="8" cy="7" r="2"/><circle cx="15" cy="12" r="2"/><circle cx="10" cy="17" r="2"/>
                    </svg>
                </span>
                <span class="text-lg font-bold tracking-tight">ValveTDS</span>
            </a>

            <h1 class="text-xl font-semibold tracking-tight">{{ $title }}</h1>
            <p class="mt-1 text-sm text-slate-500">
                {{ $admin ? 'Manage tenant workspaces and subscriptions.' : "Access your company's technical workspace." }}
            </p>

            @if ($errors->any())
                <div class="mt-5 rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-700" role="alert">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ $action }}" class="mt-6 space-y-5">
                @csrf
                <div>
                    <label for="email" class="mb-1.5 block text-sm font-medium text-slate-700">Email address</label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="username"
                        class="h-10 w-full rounded-lg border border-slate-300 bg-white px-3 text-sm placeholder:text-slate-400 focus:border-blue-600 focus:outline-none focus:ring-3 focus:ring-blue-100">
                </div>
                <div>
                    <label for="password" class="mb-1.5 block text-sm font-medium text-slate-700">Password</label>
                    <input id="password" name="password" type="password" required autocomplete="current-password"
                        class="h-10 w-full rounded-lg border border-slate-300 bg-white px-3 text-sm placeholder:text-slate-400 focus:border-blue-600 focus:outline-none focus:ring-3 focus:ring-blue-100">
                </div>
                <label class="inline-flex items-center gap-2 text-sm text-slate-600">
                    <input name="remember" type="checkbox" value="1" class="h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                    Remember me
                </label>
                <button type="submit" class="flex h-10 w-full items-center justify-center rounded-lg bg-blue-600 px-4 text-sm font-semibold text-white hover:bg-blue-700 focus:outline-none focus:ring-3 focus:ring-blue-200">
                    Sign in
                </button>
            </form>

            @if ($admin)
                <p class="mt-6 border-t border-slate-100 pt-5 text-center text-sm text-slate-500">
                    Tenant user? <a href="{{ route('login') }}" class="font-medium text-blue-700 hover:text-blue-800">Sign in to your workspace</a>
                </p>
            @else
                <p class="mt-6 border-t border-slate-100 pt-5 text-center text-sm text-slate-500">
                    Super Admin? <a href="{{ route('admin.login') }}" class="font-medium text-blue-700 hover:text-blue-800">Open admin sign in</a>
                </p>
            @endif
        </section>
    </main>
</body>
</html>
