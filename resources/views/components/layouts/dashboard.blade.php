@php
    $focusEditor = $focusEditor ?? false;
@endphp
<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $pageTitle }} · ValveTDS</title>
    @stack('styles')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-full font-sans text-slate-800 antialiased transition-colors dark:bg-slate-900 dark:text-slate-100">
    <div class="flex min-h-screen dark:bg-slate-900">
    @unless ($focusEditor)
        <aside id="sidebar" class="sidebar flex w-64 shrink-0 flex-col border-r border-slate-200 bg-white dark:border-slate-700/60 dark:bg-slate-800">
            <div class="brand-row flex h-16 items-center gap-3 border-b border-slate-200 px-5 dark:border-slate-700/60">
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-blue-600 text-white">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path d="M5 7h14M5 12h14M5 17h14"/><circle cx="8" cy="7" r="2"/><circle cx="15" cy="12" r="2"/><circle cx="10" cy="17" r="2"/>
                    </svg>
                </span>
                <span class="brand-label text-lg font-bold tracking-tight">ValveTDS</span>
            </div>

            <div class="px-3 pb-2 pt-5">
                <p class="nav-label mb-2 px-3 text-[10px] font-semibold uppercase tracking-[.14em] text-slate-400">
                    {{ $adminMode ? 'Administration' : 'Workspace' }}
                </p>
                <nav class="space-y-1" aria-label="Main navigation">
                    <a href="{{ $adminMode ? route('admin.dashboard') : route('dashboard') }}"
                        @class(['nav-link flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm transition', 'bg-blue-50 font-semibold text-blue-700' => request()->routeIs('admin.dashboard', 'dashboard')])
                        @if (request()->routeIs('admin.dashboard', 'dashboard')) aria-current="page" @endif>
                        <svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true">
                            <rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/>
                        </svg>
                        <span class="nav-label">Dashboard</span>
                    </a>
                    @if ($adminMode)
                        <a href="{{ route('admin.tenants.index') }}"
                            @class(['nav-link flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm transition', 'bg-blue-50 font-semibold text-blue-700' => request()->routeIs('admin.tenants.*')])
                            @if (request()->routeIs('admin.tenants.*')) aria-current="page" @endif>
                            <svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true">
                                <path d="M4 7h16M4 12h16M4 17h16"/><circle cx="8" cy="7" r="2"/><circle cx="16" cy="12" r="2"/>
                            </svg>
                            <span class="nav-label">Tenants</span>
                        </a>
                    @else
                        @if ($user->role === 'admin')
                            <a href="{{ route('tenant.settings.edit') }}"
                                @class(['nav-link flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm transition', 'bg-blue-50 font-semibold text-blue-700' => request()->routeIs('tenant.settings.*')])
                                @if (request()->routeIs('tenant.settings.*')) aria-current="page" @endif>
                                <svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true">
                                    <circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 .34 1.88l.06.06-1.42 1.42-.06-.06A1.7 1.7 0 0 0 16.4 18l-.09.05V22h-2v-4.95l-.09-.05a1.7 1.7 0 0 0-1.88.34l-.06.06-1.42-1.42.06-.06A1.7 1.7 0 0 0 11.26 15H7v-2h4.26a1.7 1.7 0 0 0 1.88-1.34l.06-.06-1.42-1.42-.06.06A1.7 1.7 0 0 0 10.6 12H7v-2h3.6a1.7 1.7 0 0 0 1.88-1.34l.06-.06-1.42-1.42.06-.06A1.7 1.7 0 0 0 12.6 6H16v2h-3.4a1.7 1.7 0 0 0-.34 1.88l.06.06 1.42 1.42.06-.06A1.7 1.7 0 0 0 14.4 12H19v2h-4.6a1.7 1.7 0 0 0-1.88 1.34l-.06.06 1.42 1.42.06-.06A1.7 1.7 0 0 0 15.6 18H19v-2h-3.4a1.7 1.7 0 0 0-.34-1.88l-.06-.06L13.78 12.64"/>
                                </svg>
                                <span class="nav-label">Company settings</span>
                            </a>
                            <a href="{{ route('tenant.materials.index') }}"
                                @class(['nav-link flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm transition', 'bg-blue-50 font-semibold text-blue-700' => request()->routeIs('tenant.materials.*')])
                                @if (request()->routeIs('tenant.materials.*')) aria-current="page" @endif>
                                <svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true">
                                    <path d="M4 7h16M4 12h16M4 17h16"/><circle cx="8" cy="7" r="2"/><circle cx="16" cy="12" r="2"/>
                                </svg>
                                <span class="nav-label">Materials</span>
                            </a>
                            <a href="{{ route('tenant.valve-models.index') }}"
                                @class(['nav-link flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm transition', 'bg-blue-50 font-semibold text-blue-700' => request()->routeIs('tenant.valve-models.*')])
                                @if (request()->routeIs('tenant.valve-models.*')) aria-current="page" @endif>
                                <svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true">
                                    <rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/>
                                </svg>
                                <span class="nav-label">Valve models</span>
                            </a>
                        @endif
                        <a href="{{ route('tenant.datasheets.index') }}"
                            @class(['nav-link flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm transition', 'bg-blue-50 font-semibold text-blue-700' => request()->routeIs('tenant.datasheets.*')])
                            @if (request()->routeIs('tenant.datasheets.*')) aria-current="page" @endif>
                            <svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true">
                                <path d="M6 3h8l4 4v14H6z"/><path d="M14 3v5h5M9 13h6M9 17h6"/>
                            </svg>
                            <span class="nav-label">Datasheets</span>
                        </a>
                    @endif
                </nav>
            </div>

            <div class="mt-auto border-t border-slate-200 p-3 dark:border-slate-700/60">
                <button id="collapseBtn" type="button" aria-expanded="true" class="collapse-button flex w-full items-center gap-3 rounded-lg px-3 py-2.5 text-sm text-slate-600 transition hover:bg-slate-50">
                    <svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true">
                        <path d="m15 18-6-6 6-6"/><path d="M9 12h11"/>
                    </svg>
                    <span class="collapse-label">Collapse</span>
                </button>
            </div>
        </aside>
        @endunless

        <div class="min-w-0 flex-1">
            @unless ($focusEditor)
            <header class="flex h-16 items-center justify-between gap-4 border-b border-slate-200 bg-white px-4 dark:border-slate-700/60 dark:bg-slate-800 sm:px-6 lg:px-8">
                <div class="flex min-w-0 items-center gap-3">
                    <button id="mobileMenuBtn" type="button" aria-label="Open navigation" aria-expanded="false" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-50 md:hidden">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
                    </button>
                    <div class="truncate text-sm text-slate-500 dark:text-slate-400">
                        <span>{{ $adminMode ? 'Administration' : ($tenant->company_name ?? 'Workspace') }}</span>
                        <span class="mx-2 text-slate-300">/</span>
                        <span class="font-medium text-slate-900">{{ $pageTitle }}</span>
                    </div>
                </div>
                <div class="flex shrink-0 items-center gap-3">
                    <div class="hidden text-right sm:block">
                        <p class="text-sm font-medium text-slate-800 dark:text-slate-100">{{ $user->name }}</p>
                        <p class="text-xs text-slate-500 dark:text-slate-400">{{ $adminMode ? 'Super Admin' : ucfirst($user->role) }}</p>
                    </div>
                    <span class="flex h-9 w-9 items-center justify-center rounded-full bg-blue-100 text-xs font-semibold text-blue-700 ring-1 ring-blue-200" aria-hidden="true">
                        {{ collect(explode(' ', $user->name))->map(fn ($part) => mb_substr($part, 0, 1))->take(2)->join('') }}
                    </span>
                    <form method="POST" action="{{ $adminMode ? route('admin.logout') : route('logout') }}">
                        @csrf
                        <button type="submit" class="rounded-lg px-2 py-2 text-sm font-medium text-slate-600 hover:bg-slate-50 hover:text-slate-900">Sign out</button>
                    </form>
                </div>
            </header>
            @endunless

            <main class="mx-auto">
                {{ $slot }}
                @unless ($focusEditor)
                <footer class="py-6 text-center text-xs text-slate-400">ValveTDS · Technical documentation made simple</footer>
                @endunless
            </main>
        </div>
    </div>
    @unless ($focusEditor)
    <div id="mobileBackdrop" class="fixed inset-0 z-30 hidden bg-slate-900/20 md:hidden"></div>
    @endunless
    @stack('scripts')
</body>
</html>
