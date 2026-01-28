<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>PetCare - Gestión Veterinaria Integral</title>
    <link rel="manifest" href="{{ asset('manifest.json') }}">
    <meta name="theme-color" content="#f26419"> {{-- Actualizado al naranja primary-600 --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    {{-- Eliminado el script de detección de Dark Mode para forzar Light Mode siempre --}}
</head>
<body class="bg-brand-cream flex flex-col min-h-screen" x-data="{ mobileMenuOpen: false }">

<div class="flex-grow">
    <header>
        <nav class="bg-primary-400 border-primary-500 border-b">
            <div class="max-w-screen-xl mx-auto p-4 flex items-center justify-between">

                {{-- 1. IZQUIERDA: Logo --}}
                <div class="flex w-1/3 justify-start" >
                    <a href="/" class="flex items-center space-x-3">
                        <span class="self-center text-2xl font-bold whitespace-nowrap text-primary-800">PetCare</span>
                    </a>
                </div>

                {{-- 2. CENTRO: Menú Desktop --}}
                <div class="hidden md:flex w-1/3 justify-center">
                    <ul class="flex flex-row items-center gap-x-8 font-medium">
                        <li><a href="#" class="text-primary-700 hover:text-primary-800 transition">Inicio</a></li>
                        <li><a href="{{ route('pets.index') }}" class="text-secondary hover:text-primary-700 transition">Mascotas</a></li>
                        <li><a href="{{ route('forum.index') }}" class="text-secondary hover:text-primary-700 transition">Foro</a></li>
                        <li><a href="{{ route('membership.index') }}" class="text-secondary hover:text-primary-700 transition">Planes</a></li>
                    </ul>
                </div>

                {{-- 3. DERECHA: Botones y Acciones --}}
                <div class="flex w-1/2 md:w-1/3 items-center justify-end space-x-2">
                    @if (Route::has('login'))
                        @auth
                            <a href="{{ url('/dashboard') }}" class="text-white bg-primary-700 hover:bg-primary-800 font-medium rounded-lg text-sm px-4 py-2 shadow-sm">Mi Dashboard</a>

                            <form method="POST" action="{{ route('logout') }}" class="inline">
                                @csrf
                                <button type="submit" class="text-secondary hover:bg-primary-200 border border-primary-500 font-medium rounded-lg text-sm px-4 py-2 transition-all">
                                    Salir
                                </button>
                            </form>
                        @else
                            <a href="{{ route('login') }}" class="text-secondary hover:bg-primary-200 font-medium rounded-lg text-sm px-3 py-2 transition">Iniciar Sesión</a>
                            @if (Route::has('register'))
                                <a href="{{ route('register') }}" class="text-white bg-primary-700 hover:bg-primary-800 font-medium rounded-lg text-sm px-4 py-2 shadow-sm transition">Registrarse</a>
                            @endif
                        @endauth
                    @endif

                    {{-- Hamburguesa (Solo Mobile) --}}
                    <button @click="mobileMenuOpen = !mobileMenuOpen" type="button" class="inline-flex items-center p-2 w-10 h-10 justify-center text-sm text-secondary rounded-lg md:hidden hover:bg-primary-300">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7"/></svg>
                    </button>
                </div>
            </div>

            {{-- Menú Mobile --}}
            <div x-show="mobileMenuOpen" x-transition class="md:hidden bg-primary-400 border-b border-primary-500">
                <ul class="flex flex-col p-4 space-y-2 font-semibold">
                    <li><a href="#" class="block py-2 px-3 text-primary-800 bg-primary-300 rounded-lg">Inicio</a></li>
                    <li><a href="{{ route('pets.index') }}" class="block py-2 px-3 text-secondary hover:bg-primary-300 rounded-lg">Mascotas</a></li>
                    <li><a href="{{ route('forum.index') }}" class="block py-2 px-3 text-secondary hover:bg-primary-300 rounded-lg">Foro</a></li>
                    <li><a href="{{ route('membership.index') }}" class="block py-2 px-3 text-secondary hover:bg-primary-300 rounded-lg">Planes</a></li>
                </ul>
            </div>
        </nav>

        {{-- HERO SECTION --}}
        <section class="bg-brand-cream pt-24 pb-8 lg:pt-32">
            <div class="grid max-w-screen-xl px-4 py-8 mx-auto lg:gap-8 lg:py-16 lg:grid-cols-12">
                <div class="mr-auto place-self-center lg:col-span-7">
                    <h1 class="max-w-2xl mb-4 text-4xl font-black tracking-tight leading-none md:text-5xl xl:text-6xl text-secondary uppercase">
                        UN ENFOQUE DE BIENESTAR QUE, <span class="text-primary-600">PRIORIZA LAS MASCOTAS.</span>
                    </h1>
                    <p class="max-w-2xl mb-6 font-medium text-secondary/70 lg:mb-8 md:text-lg lg:text-xl">
                        Gestiona historiales médicos, agenda citas con profesionales y únete a la comunidad de cuidado animal más grande de Argentina.
                    </p>
                    <div class="flex flex-col space-y-4 sm:flex-row sm:space-y-0 sm:space-x-4">
                        <a href="/register" class="inline-flex items-center justify-center px-6 py-3 text-base font-bold text-center text-white rounded-xl bg-primary-600 hover:bg-primary-700 shadow-lg shadow-primary-500/30 transition-all transform hover:scale-105">Comenzar ahora</a>
                        <a href="{{ route('services.info') }}" class="bg-brand-bone text-secondary border border-secondary/10 px-6 py-3 rounded-xl font-bold hover:bg-zinc-200 transition text-center">Ver servicios</a>
                    </div>
                </div>
                <div class="hidden lg:mt-0 lg:col-span-5 lg:flex">
                    <img src="https://images.unsplash.com/photo-1577175889968-f551f5944abd?q=80&w=600" alt="Veterinario" class="rounded-[2.5rem] shadow-2xl border-4 border-white">
                </div>
            </div>
        </section>
    </header>
</div>

<footer class="bg-primary-400 border-t border-primary-500">
    <div class="mx-auto max-w-screen-xl p-8 text-center">
        <span class="text-sm text-secondary font-bold">© 2026 PetCare™. Todos los derechos reservados.</span>
    </div>
</footer>

@livewireScripts
</body>
</html>
