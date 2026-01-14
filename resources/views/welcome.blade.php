<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>PetCare - Gestión Veterinaria Integral</title>

    {{-- PWA Manifest --}}
    <link rel="manifest" href="{{ asset('manifest.json') }}">
    <meta name="theme-color" content="#3b82f6">

    {{-- Assets --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles

    {{-- Script preventivo para el tema oscuro (evita el "flicker" blanco) --}}
    <script>
        if (localStorage.theme === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark')
        } else {
            document.documentElement.classList.remove('dark')
        }
    </script>
</head>
<body class="bg-white dark:bg-gray-900 transition-colors duration-300" x-data="{ mobileMenuOpen: false }">

<header>
    <nav class="bg-white border-gray-200 dark:bg-gray-900 border-b">
        <div class="max-w-screen-xl flex flex-wrap items-center justify-between mx-auto p-4">
            <a href="/" class="flex items-center space-x-3">
                <span class="self-center text-2xl font-bold whitespace-nowrap text-blue-600 dark:text-blue-500">PetCare</span>
            </a>

            <div class="flex items-center md:order-2 space-x-3">
                @if (Route::has('login'))
                    @auth
                        <a href="{{ url('/dashboard') }}" class="text-white bg-blue-700 hover:bg-blue-800 font-medium rounded-lg text-sm px-4 py-2 dark:bg-blue-600">
                            Mi Panel
                        </a>
                    @else
                        <a href="{{ route('login') }}" class="text-gray-800 dark:text-white hover:bg-gray-50 font-medium rounded-lg text-sm px-4 py-2">
                            Entrar
                        </a>
                        @if (Route::has('register'))
                            <a href="{{ route('register') }}" class="hidden sm:inline-block text-white bg-blue-700 hover:bg-blue-800 font-medium rounded-lg text-sm px-4 py-2 dark:bg-blue-600">
                                Registrarse
                            </a>
                        @endif
                    @endauth
                @endif

                {{-- Botón Modo Oscuro --}}
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
                    class="text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg text-sm p-2.5 transition-colors"
                >
                    <svg x-show="!dark" class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path d="M17.293 13.293A8 8 0 016.707 2.707a8.001 8.001 0 1010.586 10.586z"></path></svg>
                    <svg x-show="dark" class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20" style="display: none;"><path d="M10 2a1 1 0 011 1v1a1 1 0 11-2 0V3a1 1 0 011-1zm4 8a4 4 0 11-8 0 4 4 0 018 0zm-.464 4.95l.707.707a1 1 0 001.414-1.414l-.707-.707a1 1 0 00-1.414 1.414zm2.12-10.607a1 1 0 010 1.414l-.707.707a1 1 0 11-1.414-1.414l.707-.707a1 1 0 011.414 0zM17 11a1 1 0 100-2h-1a1 1 0 100 2h1zm-7 4a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1zM5.05 6.464A1 1 0 106.465 5.05l-.708-.707a1 1 0 00-1.414 1.414l.707.707zm1.414 8.486l-.707.707a1 1 0 01-1.414-1.414l.707-.707a1 1 0 011.414 1.414zM4 11a1 1 0 100-2H3a1 1 0 000 2h1z" fill-rule="evenodd"></path></svg>
                </button>

                {{-- Botón Menú Hamburguesa (Mobile) --}}
                <button @click="mobileMenuOpen = !mobileMenuOpen" type="button" class="inline-flex items-center p-2 w-10 h-10 justify-center text-sm text-gray-500 rounded-lg md:hidden hover:bg-gray-100 focus:outline-none dark:text-gray-400 dark:hover:bg-gray-700">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7"/></svg>
                </button>
            </div>

            {{-- Navegación --}}
            <div :class="mobileMenuOpen ? 'block' : 'hidden'" class="items-center justify-between w-full md:flex md:w-auto md:order-1" id="navbar-cta">
                <ul class="flex flex-col font-medium p-4 md:p-0 mt-4 border border-gray-100 rounded-lg bg-gray-50 md:space-x-8 md:flex-row md:mt-0 md:border-0 md:bg-white dark:bg-gray-800 md:dark:bg-gray-900 dark:border-gray-700">
                    <li><a href="#" class="block py-2 px-3 text-blue-700 md:dark:text-blue-500">Inicio</a></li>
                    <li><a href="{{ route('pets.index') }}" class="block py-2 px-3 text-gray-900 rounded hover:bg-gray-100 md:hover:bg-transparent md:hover:text-blue-700 dark:text-white">Mascotas</a></li>
                    <li><a href="{{ route('forum.index') }}" class="block py-2 px-3 text-gray-900 rounded hover:bg-gray-100 md:hover:bg-transparent md:hover:text-blue-700 dark:text-white">Foro</a></li>
                    <li><a href="{{ route('membership.index') }}" class="block py-2 px-3 text-gray-900 rounded hover:bg-gray-100 md:hover:bg-transparent md:hover:text-blue-700 dark:text-white">Planes</a></li>
                </ul>
            </div>
        </div>
    </nav>
</header>

<main>
    <section class="bg-white dark:bg-gray-900 pt-24 pb-8 lg:pt-32">
        <div class="grid max-w-screen-xl px-4 py-8 mx-auto lg:gap-8 lg:py-16 lg:grid-cols-12">
            <div class="mr-auto place-self-center lg:col-span-7">
                <h1 class="max-w-2xl mb-4 text-4xl font-extrabold tracking-tight leading-none md:text-5xl xl:text-6xl dark:text-white">
                    La salud de tu mascota, <span class="text-blue-600">digitalizada y segura.</span>
                </h1>
                <p class="max-w-2xl mb-6 font-light text-gray-500 lg:mb-8 md:text-lg lg:text-xl dark:text-gray-400">
                    Gestiona historiales médicos, agenda citas con profesionales y únete a la comunidad de cuidado animal más grande de Temuco.
                </p>
                <div class="flex flex-col space-y-4 sm:flex-row sm:space-y-0 sm:space-x-4">
                    <a href="/register" class="inline-flex items-center justify-center px-5 py-3 text-base font-medium text-center text-white rounded-lg bg-blue-700 hover:bg-blue-800">
                        Comenzar ahora
                        <svg class="w-5 h-5 ml-2" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z" clip-rule="evenodd"></path></svg>
                    </a>
                </div>
            </div>
            <div class="hidden lg:mt-0 lg:col-span-5 lg:flex">
                <img src="https://images.unsplash.com/photo-1577175889968-f551f5944abd?auto=format&fit=crop&q=80&w=600" alt="Veterinario" class="rounded-2xl shadow-2xl">
            </div>
        </div>
    </section>
</main>

<footer class="p-4 bg-white md:p-8 lg:p-10 dark:bg-gray-800">
    <div class="mx-auto max-w-screen-xl text-center">
        <span class="text-sm text-gray-500 sm:text-center dark:text-gray-400">© 2026 <a href="#" class="hover:underline">PetCare™</a>. Todos los derechos reservados.</span>
    </div>
</footer>

{{-- Scripts Finales --}}
@livewireScripts

<script>
    {{-- Registro del Service Worker --}}
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', () => {
            navigator.serviceWorker.register("{{ asset('sw.js') }}")
                .then(reg => console.log('PetCare PWA: SW registrado', reg.scope))
                .catch(err => console.log('PetCare PWA: Error al registrar SW', err));
        });
    }

    {{-- Arreglar ReferenceError: Alpine is not defined --}}
    document.addEventListener('livewire:init', () => {
        console.log('Alpine cargado vía Livewire');
    });
</script>

</body>
</html>
