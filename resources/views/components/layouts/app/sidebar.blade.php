<!DOCTYPE html>
@php
    // RF9: Obtenemos la suscripción solo si el usuario está autenticado
    $userSubscription = null;
    if (auth()->check()) {
        $userSubscription = app(Kreait\Firebase\Contract\Database::class)
            ->getReference('user_subscriptions/' . auth()->id())
            ->getValue();
    }

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
    <script>
        // Al cargar la página, aplicamos el tema guardado o el del sistema
        if (localStorage.theme === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark')
        } else {
            document.documentElement.classList.remove('dark')
        }
    </script>
</head>
<body class="min-h-screen bg-white dark:bg-zinc-800">
<flux:sidebar sticky stashable class="border-e border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
    <flux:sidebar.toggle class="lg:hidden" icon="x-mark" />

    <a href="{{ route('home') }}" class="flex items-center gap-3 px-2 py-4" wire:navigate>
        <div class="bg-blue-600 p-1.5 rounded-lg text-white text-xl">🐾</div>
        <span class="text-xl font-black tracking-tight dark:text-white">Pet<span class="text-blue-600">Care</span></span>
    </a>

    <flux:navlist variant="outline">
        <flux:navlist.group :heading="__('Plataforma')" class="grid">
            {{-- Rutas que requieren Login --}}
            @auth
                <flux:navlist.item icon="home" :href="route('dashboard')" :current="request()->routeIs('dashboard')" wire:navigate>
                    {{ __('Inicio') }}
                </flux:navlist.item>

                <flux:navlist.item icon="identification" href="/mascotas" :current="request()->is('mascotas*')" wire:navigate>
                    {{ __('Mis Mascotas') }}
                </flux:navlist.item>

                <flux:navlist.item icon="chat-bubble-left-right" :href="route('consultations')" :current="request()->routeIs('consultations')" wire:navigate>
                    {{ __('Consultas Vet') }}
                </flux:navlist.item>
            @endauth

            {{-- RUTAS PÚBLICAS (Siempre visibles) --}}
            <flux:navlist.item icon="credit-card" :href="route('membership.index')" :current="request()->routeIs('membership.index')" wire:navigate>
                {{ __('Planes') }}
            </flux:navlist.item>

            <flux:navlist.item icon="map-pin" href="{{ route('veterinarias.index') }}" :current="request()->routeIs('veterinarias.index')" wire:navigate>
                Veterinarias
            </flux:navlist.item>
        </flux:navlist.group>

        @auth
            <flux:navlist.group :heading="__('Gestión')" class="grid mt-4">
                <flux:navlist.item icon="calendar" :href="route('appointments.index')" :current="request()->routeIs('appointments.index')" wire:navigate>
                    {{ __('Agenda Médica') }}
                </flux:navlist.item>
            </flux:navlist.group>
        @endauth
    </flux:navlist>

    <flux:spacer />

    {{-- Perfil de Usuario Desktop --}}
    <div class="hidden lg:block">
        @auth
            <flux:dropdown position="bottom" align="start">
                <flux:profile
                    :name="auth()->user()->name"
                    :initials="auth()->user()->initials()"
                    icon-trailing="chevrons-up-down"
                />

                <flux:menu class="w-[220px]">
                    <div class="p-2 text-start text-sm leading-tight border-b border-zinc-100 dark:border-zinc-800">
                        <span class="truncate font-semibold block dark:text-white">{{ auth()->user()->name }}</span>
                        <span class="truncate text-xs text-zinc-500 block mb-2">{{ auth()->user()->email }}</span>
                        <span class="w-fit text-[10px] font-black uppercase px-2 py-0.5 rounded-md border {{ $planColor }}">
                                Plan {{ $planName }}
                            </span>
                    </div>

                    <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate>{{ __('Configuración') }}</flux:menu.item>

                    <flux:menu.separator />

                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full">
                            {{ __('Cerrar Sesión') }}
                        </flux:menu.item>
                    </form>
                </flux:menu>
            </flux:dropdown>
        @else
            <div class="p-2">
                <flux:button :href="route('login')" variant="primary" class="w-full !bg-blue-600" wire:navigate>Iniciar Sesión</flux:button>
            </div>
        @endauth
    </div>
</flux:sidebar>

{{-- Mobile User Menu --}}
<flux:header class="lg:hidden">
    <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />
    <flux:spacer />

    @auth
        <flux:dropdown position="top" align="end">
            <flux:profile :initials="auth()->user()->initials()" icon-trailing="chevron-down" />
            <flux:menu>
                <div class="p-2 text-start text-sm leading-tight">
                    <span class="truncate font-semibold block dark:text-white">{{ auth()->user()->name }}</span>
                    <span class="w-fit text-[10px] font-black uppercase px-2 py-0.5 rounded-md border {{ $planColor }}">
                            Plan {{ $planName }}
                        </span>
                </div>
                <flux:menu.separator />
                <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate>{{ __('Configuración') }}</flux:menu.item>
                <form method="POST" action="{{ route('logout') }}" class="w-full">
                    @csrf
                    <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full">
                        {{ __('Log Out') }}
                    </flux:menu.item>
                </form>
            </flux:menu>
        </flux:dropdown>
    @else
        <flux:button :href="route('login')" variant="ghost" size="sm" wire:navigate>Entrar</flux:button>
    @endauth
</flux:header>

{{ $slot }}

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
@fluxScripts
<script>
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', () => {
            navigator.serviceWorker.register('/sw.js')
                .then(reg => console.log('PetCare: SW registrado'))
                .catch(err => console.log('PetCare: Error SW', err));
        });
    }
</script>
</body>
</html>
