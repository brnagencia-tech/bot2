<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="{{ (auth()->user()->theme ?? 'light') === 'dark' ? 'dark' : '' }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        <div class="min-h-screen bg-gray-100 dark:bg-slate-900 dark:text-slate-100 sm:flex">
            <!-- Sidebar (desktop) -->
            <aside class="hidden sm:flex sm:flex-col sm:w-64 bg-white dark:bg-slate-800 border-r border-gray-200 dark:border-slate-700">
                <div class="h-16 flex items-center px-4 border-b border-gray-200 dark:border-slate-700">
                    <a href="{{ route('dashboard') }}" class="flex items-center gap-2">
                        @php($logo = auth()->user()->logo_path ? asset('storage/'.auth()->user()->logo_path) : null)
                        @if($logo)
                            <img src="{{ $logo }}" class="h-8" alt="Logo" />
                        @else
                            <x-application-logo class="h-8 w-8 fill-current text-gray-800 dark:text-slate-100" />
                        @endif
                        <span class="font-semibold">{{ config('app.name', 'BotWhatsApp') }}</span>
                    </a>
                </div>
                <nav class="flex-1 p-4 space-y-1">
                    <a href="{{ route('dashboard') }}" class="block px-3 py-2 rounded hover:bg-gray-100 dark:hover:bg-slate-700 {{ request()->routeIs('dashboard') ? 'bg-gray-100 dark:bg-slate-700' : '' }}">Dashboard</a>
                    <a href="{{ route('whatsapp.cloud.index') }}" class="block px-3 py-2 rounded hover:bg-gray-100 dark:hover:bg-slate-700 {{ request()->routeIs('whatsapp.cloud.*') ? 'bg-gray-100 dark:bg-slate-700' : '' }}">WhatsApp Cloud</a>
                    <a href="{{ route('whatsapp.index') }}" class="block px-3 py-2 rounded hover:bg-gray-100 dark:hover:bg-slate-700 {{ request()->routeIs('whatsapp.*') ? 'bg-gray-100 dark:bg-slate-700' : '' }}">WhatsApp (Web)</a>
                    <a href="{{ route('profile.edit') }}" class="block px-3 py-2 rounded hover:bg-gray-100 dark:hover:bg-slate-700 {{ request()->routeIs('profile.*') ? 'bg-gray-100 dark:bg-slate-700' : '' }}">Perfil</a>
                    <a href="{{ route('settings.index') }}" class="block px-3 py-2 rounded hover:bg-gray-100 dark:hover:bg-slate-700 {{ request()->routeIs('settings.*') ? 'bg-gray-100 dark:bg-slate-700' : '' }}">Configurações</a>
                    <form method="POST" action="{{ route('logout') }}" class="mt-4">
                        @csrf
                        <button class="w-full text-left px-3 py-2 rounded hover:bg-gray-100 dark:hover:bg-slate-700">Sair</button>
                    </form>
                </nav>
            </aside>

            <!-- Content area -->
            <div class="flex-1 min-w-0">
                <!-- Top navigation (mobile) -->
                <div class="sm:hidden">
                    @include('layouts.navigation')
                </div>

                @isset($header)
                    <header class="bg-white dark:bg-slate-800 shadow">
                        <div class="max-w-7xl mx-auto py-4 px-4 sm:px-6 lg:px-8">
                            {{ $header }}
                        </div>
                    </header>
                @endisset

                <main>
                    {{ $slot }}
                </main>
            </div>
        </div>
    </body>
</html>
