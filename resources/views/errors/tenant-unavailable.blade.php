<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Workspace unavailable · ValveTDS</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-full items-center justify-center bg-slate-50 px-4 py-12 text-slate-900">
    <main class="w-full max-w-lg rounded-xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
        <h1 class="text-xl font-semibold tracking-tight">Workspace temporarily unavailable</h1>
        <p class="mt-2 text-sm leading-6 text-slate-600">
            We could not connect to your company workspace. The issue has been logged; please try again shortly or contact your administrator.
        </p>
        <a href="{{ route('login') }}" class="mt-6 inline-flex rounded-lg bg-blue-600 px-3.5 py-2 text-sm font-medium text-white hover:bg-blue-700">Return to sign in</a>
    </main>
</body>
</html>
