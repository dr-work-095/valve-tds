<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Subscription inactive · ValveTDS</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-full items-center justify-center bg-slate-50 px-4 py-12 text-slate-900">
    <main class="w-full max-w-lg rounded-xl border border-amber-200 bg-white p-6 shadow-sm sm:p-8">
        <span class="flex h-11 w-11 items-center justify-center rounded-full bg-amber-50 text-amber-700">
            <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                <path d="M12 3 2.8 19h18.4L12 3Z"/><path d="M12 9v4m0 3v.01"/>
            </svg>
        </span>
        <h1 class="mt-5 text-xl font-semibold tracking-tight">Subscription inactive</h1>
        <p class="mt-2 text-sm leading-6 text-slate-600">
            Access to {{ $companyName }}'s workspace is currently unavailable. Please contact your ValveTDS administrator for assistance.
        </p>
        <form method="POST" action="{{ route('logout') }}" class="mt-6">
            @csrf
            <button type="submit" class="inline-flex rounded-lg border border-slate-300 px-3.5 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Sign out</button>
        </form>
    </main>
</body>
</html>
