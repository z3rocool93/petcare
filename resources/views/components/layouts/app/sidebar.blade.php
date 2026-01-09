<!DOCTYPE html>
@php
    // Obtenemos la suscripción del usuario actual desde Firebase
    $userSubscription = app(Kreait\Firebase\Contract\Database::class)
        ->getReference('user_subscriptions/' . auth()->id())
        ->getValue();

    $planName = $userSubscription['plan_name'] ?? 'Básico';
    $planColor = match($userSubscription['plan_key'] ?? 'basic') {
        'premium' => 'bg-blue-100 text-blue-700 border-blue-200',
        'vip' => 'bg-purple-100 text-purple-700 border-purple-200',
        default => 'bg-zinc-100 text-zinc-600 border-zinc-200',
    };
@endphp
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
        <meta name="theme-color" content="#3b82f6">
        <link rel="manifest" href="{{ asset('manifest.json') }}">
        <link rel="apple-touch-icon" href="{{ asset('img/icons/icon-192x192.png') }}">
    </head>
    <body class="min-h-screen bg-white dark:bg-zinc-800">
    <flux:sidebar sticky stashable class="border-e border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
        <flux:sidebar.toggle class="lg:hidden" icon="x-mark" />

        <a href="{{ route('home') }}" class="flex items-center gap-3 px-2 py-4" wire:navigate>
            <div class="bg-blue-600 p-1.5 rounded-lg text-white text-xl">
                🐾
            </div>
            <span class="text-xl font-black tracking-tight dark:text-white">Pet<span class="text-blue-600">Care</span></span>
        </a>

        <flux:navlist variant="outline">
            <flux:navlist.group :heading="__('Plataforma')" class="grid">
            {{-- Dashboard --}}
            <flux:navlist.item icon="home" :href="route('dashboard')" :current="request()->routeIs('dashboard')" wire:navigate>
                {{ __('Inicio') }}
            </flux:navlist.item>

            {{-- Mis Mascotas --}}
            <flux:navlist.item icon="identification" href="/mascotas" :current="request()->is('mascotas*')" wire:navigate>
                {{ __('Mis Mascotas') }}
            </flux:navlist.item>

            {{-- CONSULTAS VET (RF1, RF2, RF3) --}}
            {{-- Usamos el componente nativo para mantener el estilo --}}
            <flux:navlist.item icon="chat-bubble-left-right" :href="route('consultations')" :current="request()->routeIs('consultations')" wire:navigate>
                {{ __('Consultas Vet') }}
            </flux:navlist.item>
                <flux:navlist.item icon="credit-card" :href="route('membership.index')" :current="request()->routeIs('membership.index')" wire:navigate>
                    {{ __('Planes') }}
                </flux:navlist.item>

                <flux:navlist.item
                    icon="map-pin"
                    href="{{ route('veterinarias.index') }}"
                    wire:navigate
                >
                    Veterinarias
                </flux:navlist.item>
        </flux:navlist.group>

            {{-- Grupo secundario (Opcional: puedes agregar Citas Médicas aquí después) --}}
            <flux:navlist.group :heading="__('Gestión')" class="grid mt-4">
                <flux:navlist.item icon="calendar" :href="route('appointments.index')" :current="request()->routeIs('appointments.index')" wire:navigate>
                    {{ __('Agenda Médica') }}
                </flux:navlist.item>
            </flux:navlist.group>
        </flux:navlist>

        <flux:spacer />

        {{-- Eliminamos los links de GitHub y Laravel Docs que estaban aquí --}}

        <div class="hidden lg:block">
            <flux:dropdown position="bottom" align="start">
                <flux:profile
                    :name="auth()->user()->name"
                    :initials="auth()->user()->initials()"
                    icon-trailing="chevrons-up-down"
                    data-test="sidebar-menu-button"
                />

                <flux:menu class="w-[220px]">
                    <flux:menu.radio.group>
                        <div class="p-0 text-sm font-normal">
                            <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                                <span class="relative flex h-8 w-8 shrink-0 overflow-hidden rounded-lg">
                                    <span
                                        class="flex h-full w-full items-center justify-center rounded-lg bg-neutral-200 text-black dark:bg-neutral-700 dark:text-white"
                                    >
                                        {{ auth()->user()->initials() }}
                                    </span>
                                </span>

                                <div class="grid flex-1 text-start text-sm leading-tight">
                                    <span class="truncate font-semibold">{{ auth()->user()->name }}</span>
                                    <span class="truncate text-xs">{{ auth()->user()->email }}</span>
                                    <span class="truncate font-semibold">{{ auth()->user()->name }}</span>
                                    <span class="truncate text-xs text-zinc-500 mb-1">{{ auth()->user()->email }}</span>

                                    {{-- Badge de Membresía --}}
                                    <span class="w-fit text-[10px] font-black uppercase px-2 py-0.5 rounded-md border {{ $planColor }}">
                                        Plan {{ $planName }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <flux:menu.radio.group>
                        <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate>{{ __('Settings') }}</flux:menu.item>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full" data-test="logout-button">
                            {{ __('Log Out') }}
                        </flux:menu.item>
                    </form>
                </flux:menu>
            </flux:dropdown>
        </div>
    </flux:sidebar>

        <!-- Mobile User Menu -->
        <flux:header class="lg:hidden">
            <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

            <flux:spacer />

            <flux:dropdown position="top" align="end">
                <flux:profile
                    :initials="auth()->user()->initials()"
                    icon-trailing="chevron-down"
                />

                <flux:menu>
                    <flux:menu.radio.group>
                        <div class="p-0 text-sm font-normal">
                            <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                                <span class="relative flex h-8 w-8 shrink-0 overflow-hidden rounded-lg">
                                    <span
                                        class="flex h-full w-full items-center justify-center rounded-lg bg-neutral-200 text-black dark:bg-neutral-700 dark:text-white"
                                    >
                                        {{ auth()->user()->initials() }}
                                    </span>
                                </span>

                                <div class="grid flex-1 text-start text-sm leading-tight">
                                    <span class="truncate font-semibold">{{ auth()->user()->name }}</span>
                                    <span class="truncate text-xs">{{ auth()->user()->email }}</span>
                                    <span class="truncate font-semibold">{{ auth()->user()->name }}</span>
                                    <span class="truncate text-xs text-zinc-500 mb-1">{{ auth()->user()->email }}</span>

                                    {{-- Badge de Membresía --}}
                                    <span class="w-fit text-[10px] font-black uppercase px-2 py-0.5 rounded-md border {{ $planColor }}">
                                        Plan {{ $planName }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <flux:menu.radio.group>
                        <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate>{{ __('Settings') }}</flux:menu.item>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full" data-test="logout-button">
                            {{ __('Log Out') }}
                        </flux:menu.item>
                    </form>
                </flux:menu>
            </flux:dropdown>
        </flux:header>

        {{ $slot }}

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

        @fluxScripts
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/sw.js')
                    .then(reg => console.log('PetCare: Service Worker registrado con éxito', reg))
                    .catch(err => console.log('PetCare: Error al registrar el Service Worker', err));
            });
        }
    </script>
    </body>
</html>
