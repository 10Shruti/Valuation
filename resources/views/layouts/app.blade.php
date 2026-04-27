<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full"> {{-- Added h-full --}}
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ config('app.name', 'Jewelry System') }}</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
        <script src="https://cdn.tailwindcss.com"></script>
        
        <style>
            /* Custom Breakpoint for 700px */
            @media (min-width: 701px) {
                .custom-sidebar { transform: translateX(0) !important; position: relative !important; }
                .mobile-toggle-btn, .mobile-overlay { display: none !important; }
            }
            @media (max-width: 700px) {
                .custom-sidebar { position: fixed; z-index: 50; height: 100vh; }
            }
            input[type=number]::-webkit-inner-spin-button, 
            input[type=number]::-webkit-outer-spin-button { -webkit-appearance: none; margin: 0; }
            
            /* Ensure the body doesn't bounce/scroll */
            body, html { overflow: hidden; height: 100%; }
        </style>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased text-slate-900 h-full" x-data="{ mobileMenuOpen: false }">
        
        {{-- Main Wrapper: Fixed Height --}}
        <div class="h-screen flex overflow-hidden bg-gray-100">
            
            <aside :class="mobileMenuOpen ? 'translate-x-0' : '-translate-x-full'"
                class="custom-sidebar w-64 bg-slate-900 text-white transition-transform duration-300 ease-in-out transform flex-shrink-0 shadow-xl flex flex-col h-full">
                
                <div class="p-6 border-b border-slate-800 flex justify-between items-center flex-shrink-0">
                    <x-application-logo class="w-auto h-10 fill-current" />
                    <button @click="mobileMenuOpen = false" class="mobile-toggle-btn text-slate-400 hover:text-white">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                {{-- Scrollable Nav inside Sidebar (if links are many) --}}
                <nav class="mt-6 px-4 space-y-2 flex-1 overflow-y-auto">
                    <a href="{{ route('dashboard') }}" class="flex items-center px-4 py-3 rounded-lg transition {{ request()->routeIs('dashboard') ? 'bg-blue-600 text-white' : 'text-slate-400 hover:bg-slate-800 hover:text-white' }}">
                        <span class="mr-3">🏠</span> Dashboard
                    </a>
                    <a href="{{ route('valuation.create') }}" class="flex items-center px-4 py-3 rounded-lg transition {{ request()->routeIs('valuation.create') ? 'bg-blue-600 text-white' : 'text-slate-400 hover:bg-slate-800 hover:text-white' }}">
                        <span class="mr-3">💎</span> Create Valuation
                    </a>
                    
                    <a href="{{ route('valuation.report') }}" class="flex items-center px-4 py-3 rounded-lg transition {{ request()->routeIs('valuation.report') ? 'bg-blue-600 text-white' : 'text-slate-400 hover:bg-slate-800 hover:text-white' }}">
                        <span class="mr-3">📊</span> Valuation Report
                    </a>
                    
                    <div class="pt-10 pb-6">
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="w-full flex items-center px-4 py-3 text-red-400 hover:bg-red-900/20 hover:text-red-300 rounded-lg transition font-bold text-left">
                                <span class="mr-3">🚪</span> Logout
                            </button>
                        </form>
                    </div>
                </nav>
            </aside>

            <div class="flex-1 flex flex-col min-w-0 h-full">
                

                

                <header class="bg-white border-b border-gray-200 h-16 flex items-center px-4 lg:px-8 justify-between shadow-sm flex-shrink-0 z-10">
                    <div class="flex items-center">
                        <button @click="mobileMenuOpen = true" class="mobile-toggle-btn mr-4 text-slate-600 hover:text-blue-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                        </button>
                        <h2 class="font-bold text-lg lg:text-xl text-slate-700 uppercase tracking-wide truncate">@yield('page_title', 'Jewelry System')</h2>
                    </div>

                    <div class="flex items-center gap-4">
                        <div class="hidden sm:flex sm:items-center">
                            <x-dropdown align="right" width="48">
                                <x-slot name="trigger">
                                    <button class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-slate-500 bg-white hover:text-slate-700 focus:outline-none transition ease-in-out duration-150">
                                        <span>Welcome, {{ Auth::user()->name }}</span>

                                        <div class="ms-1">
                                            <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                            </svg>
                                        </div>
                                    </button>
                                </x-slot>

                                <x-slot name="content">
                                    <x-dropdown-link :href="route('profile.edit')">
                                        {{ __('Profile') }}
                                    </x-dropdown-link>

                                    <form method="POST" action="{{ route('logout') }}">
                                        @csrf
                                        <x-dropdown-link :href="route('logout')"
                                                onclick="event.preventDefault();
                                                            this.closest('form').submit();">
                                            {{ __('Log Out') }}
                                        </x-dropdown-link>
                                    </form>
                                </x-slot>
                            </x-dropdown>
                        </div>
                    </div>
                </header>
                <main class="flex-1 overflow-y-auto p-4 lg:p-8 bg-gray-100">
                    <div class="max-w-7xl mx-auto">
                        {{ $slot }}
                    </div>
                </main>
            </div>

            <div x-show="mobileMenuOpen" 
                 x-transition:enter="transition opacity-0 ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="transition opacity-100 ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 @click="mobileMenuOpen = false" 
                 class="mobile-overlay fixed inset-0 bg-black/50 z-40"></div>
        </div>
    </body>
</html>