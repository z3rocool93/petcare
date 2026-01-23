<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>PetCare - Gestión Veterinaria Integral</title>
    <link rel="manifest" href="{{ asset('manifest.json') }}">
    <meta name="theme-color" content="#3b82f6">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    <script>
        // Si no hay tema guardado, usamos 'light' por defecto (ignorando la preferencia del sistema para el primer inicio)
        if (localStorage.theme === 'dark' || (!('theme' in localStorage) && false)) {
            document.documentElement.classList.add('dark')
        } else {
            document.documentElement.classList.remove('dark')
        }
    </script>
</head>
<body class="bg-brand-cream dark:bg-gray-900 flex flex-col min-h-screen" x-data="{ mobileMenuOpen: false }">

<div class="flex-grow">
    <header>
        <nav class="bg-primary-400 border-primary-500 dark:bg-gray-900 border-b">
            <div class="max-w-screen-xl mx-auto p-4 flex items-center justify-between">

                {{-- 1. IZQUIERDA: Logo (Ocupa 1/3 del espacio) --}}
                <div class="flex w-1/3 justify-start" >
                    <a href="/" class="flex items-center space-x-3">
                        <span class="self-center text-2xl font-bold whitespace-nowrap text-primary-800 dark:text-primary-500">PetCare</span>
                    </a>
                </div>

                {{-- 2. CENTRO: Menú (Ocupa 1/3 del espacio y centra su contenido) --}}
                <div class="hidden md:flex w-1/3 justify-center">
                    {{-- USAMOS FLEX Y GAP-X-8 AQUÍ PARA LA SEPARACIÓN --}}
                    <ul class="flex flex-row items-center gap-x-8 font-medium">
                        <li><a href="#" class="text-primary-700 dark:text-primary-500 hover:text-primary-600 transition">Inicio</a></li>
                        <li><a href="{{ route('pets.index') }}" class="text-secondary dark:text-white hover:text-primary-600 transition">Mascotas</a></li>
                        <li><a href="{{ route('forum.index') }}" class="text-secondary dark:text-white hover:text-primary-600 transition">Foro</a></li>
                        <li><a href="{{ route('membership.index') }}" class="text-secondary dark:text-white hover:text-primary-600 transition">Planes</a></li>
                    </ul>
                </div>

                {{-- 3. DERECHA: Botones (Ocupa 1/3 del espacio y alinea al final) --}}
                <div class="flex w-1/2 md:w-1/3 items-center justify-end space-x-2">
                    @if (Route::has('login'))
                        @auth
                            <a href="{{ url('/dashboard') }}" class="text-white bg-primary-700 hover:bg-primary-800 font-medium rounded-lg text-sm px-4 py-2 dark:bg-primary-600">Mi Dashboard</a>

                            <form method="POST" action="{{ route('logout') }}" class="inline">
                                @csrf
                                <button type="submit" class="text-zinc-600 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 font-medium rounded-lg text-sm px-4 py-2 transition-all">
                                    Salir
                                </button>
                            </form>
                        @else
                            <a href="{{ route('login') }}" class="text-secondary dark:text-white hover:bg-primary-200 font-medium rounded-lg text-sm px-3 py-2 transition">Iniciar Sesión</a>
                            @if (Route::has('register'))
                                <a href="{{ route('register') }}" class="text-white bg-primary-700 hover:bg-primary-800 font-medium rounded-lg text-sm px-4 py-2 dark:bg-primary-600 transition">Registrarse</a>
                            @endif
                        @endauth
                    @endif

                    {{-- Toggle Dark Mode --}}
                    <button
                        x-data="{
                                dark: document.documentElement.classList.contains('dark'),
                                toggle() {
                                    this.dark = !this.dark;
                                    localStorage.theme = this.dark ? 'dark' : 'light';
                                    if (this.dark) document.documentElement.classList.add('dark');
                                    else document.documentElement.classList.remove('dark');
                                }
                            }"
                        @click="toggle()"
                        type="button"
                        class="text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg text-sm p-2 transition-colors"
                    >
                        <svg x-show="!dark" class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path d="M17.293 13.293A8 8 0 016.707 2.707a8.001 8.001 0 1010.586 10.586z"></path></svg>
                        <svg x-show="dark" class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20" style="display:none;"><path d="M10 2a1 1 0 011 1v1a1 1 0 11-2 0V3a1 1 0 011-1zm4 8a4 4 0 11-8 0 4 4 0 018 0zm-.464 4.95l.707.707a1 1 0 001.414-1.414l-.707-.707a1 1 0 00-1.414 1.414zm2.12-10.607a1 1 0 010 1.414l-.707.707a1 1 0 11-1.414-1.414l.707-.707a1 1 0 011.414 0zM17 11a1 1 0 100-2h-1a1 1 0 100 2h1zm-7 4a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1zM5.05 6.464A1 1 0 106.465 5.05l-.708-.707a1 1 0 00-1.414 1.414l.707.707zm1.414 8.486l-.707.707a1 1 0 01-1.414-1.414l.707-.707a1 1 0 011.414 1.414zM4 11a1 1 0 100-2H3a1 1 0 000 2h1z" fill-rule="evenodd" clip-rule="evenodd"></path></svg>
                    </button>

                    {{-- Hamburguesa (Solo Mobile) --}}
                    <button @click="mobileMenuOpen = !mobileMenuOpen" type="button" class="inline-flex items-center p-2 w-10 h-10 justify-center text-sm text-gray-500 rounded-lg md:hidden hover:bg-gray-100 dark:text-gray-400">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7"/></svg>
                    </button>
                </div>
            </div>

            {{-- Menú Mobile --}}
            <div x-show="mobileMenuOpen" x-transition class="md:hidden bg-primary-400 dark:bg-gray-800 border-b border-primary-500 dark:border-gray-700">
                <ul class="flex flex-col p-4 space-y-2">
                    <li><a href="#" class="block py-2 px-3 text-primary-700">Inicio</a></li>
                    <li><a href="{{ route('pets.index') }}" class="block py-2 px-3 text-secondary dark:text-white">Mascotas</a></li>
                    <li><a href="{{ route('forum.index') }}" class="block py-2 px-3 text-secondary dark:text-white">Foro</a></li>
                    <li><a href="{{ route('membership.index') }}" class="block py-2 px-3 text-secondary dark:text-white">Planes</a></li>
                </ul>
            </div>
        </nav>

        <section class="bg-brand-cream dark:bg-gray-900 pt-24 pb-8 lg:pt-32">
            <div class="grid max-w-screen-xl px-4 py-8 mx-auto lg:gap-8 lg:py-16 lg:grid-cols-12">
                <div class="mr-auto place-self-center lg:col-span-7">
                    <h1 class="max-w-2xl mb-4 text-4xl font-bold tracking-tight leading-none md:text-5xl xl:text-6xl text-secondary dark:text-white">
                        UN ENFOQUE DE BIENESTAR QUE, <span class="text-primary-600">PRIORIZA LAS MASCOTAS.</span>
                    </h1>
                    <p class="max-w-2xl mb-6 font-light text-gray-500 lg:mb-8 md:text-lg lg:text-xl dark:text-gray-400">
                        Gestiona historiales médicos, agenda citas con profesionales y únete a la comunidad de cuidado animal más grande de Argentina.
                    </p>
                    <div class="flex flex-col space-y-4 sm:flex-row sm:space-y-0 sm:space-x-4">
                        <a href="/register" class="inline-flex items-center justify-center px-5 py-3 text-base font-medium text-center text-white rounded-lg bg-primary-600 hover:bg-primary-700">Comenzar ahora</a>
                        <a href="{{ route('services.info') }}" class="bg-brand-bone text-secondary px-6 py-3 rounded-2xl font-bold hover:bg-zinc-200 transition text-center">Ver servicios</a>
                    </div>
                </div>
                <div class="hidden lg:mt-0 lg:col-span-5 lg:flex">
                    <img src="https://images.unsplash.com/photo-1577175889968-f551f5944abd?q=80&w=600" alt="Veterinario" class="rounded-2xl shadow-2xl">
                </div>
            </div>
        </section>
    </header>
</div>

<footer class="bg-primary-400 dark:bg-gray-900 border-t border-primary-500 dark:border-gray-800">
    <div class="mx-auto max-w-screen-xl p-8 text-center">
        <span class="text-sm text-secondary dark:text-gray-400">© 2026 PetCare™. Todos los derechos reservados.</span>
    </div>
</footer>

@livewireScripts
</body>
</html>
