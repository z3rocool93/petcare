<x-layouts.auth>
    <div class="flex flex-col gap-8 bg-white dark:bg-gray-800 p-8 rounded-2xl shadow-xl border border-gray-100 dark:border-gray-700">

        <div class="flex flex-col items-center gap-4">
            <div class="w-16 h-16 bg-blue-600 rounded-2xl flex items-center justify-center text-3xl shadow-lg shadow-blue-100 dark:shadow-none">
                🐶
            </div>
            <div class="text-center">
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white">{{ __('¡Bienvenido de nuevo!') }}</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ __('Ingresa para gestionar tus mascotas') }}</p>
            </div>
        </div>

        <x-auth-session-status class="text-center text-sm font-medium text-green-600" :status="session('status')" />

        <form method="POST" action="{{ route('login.store') }}" class="flex flex-col gap-6">
            @csrf

            <flux:input
                name="email"
                :label="__('Correo Electrónico')"
                :value="old('email')"
                type="email"
                required
                autofocus
                autocomplete="email"
                placeholder="ejemplo@correo.com"
                class="rounded-xl"
            />

            <div class="relative">
                <flux:input
                    name="password"
                    :label="__('Contraseña')"
                    type="password"
                    required
                    autocomplete="current-password"
                    placeholder="••••••••"
                    viewable
                    class="rounded-xl"
                />

                @if (Route::has('password.request'))
                    {{-- Ajustamos el link de recuperar clave para que se vea más integrado --}}
                    <flux:link class="absolute top-0 end-0 text-xs font-semibold text-blue-600 hover:text-blue-700" :href="route('password.request')" wire:navigate>
                        {{ __('¿Olvidaste tu clave?') }}
                    </flux:link>
                @endif
            </div>

            <div class="flex items-center">
                <flux:checkbox name="remember" :label="__('Recordarme en este equipo')" :checked="old('remember')" class="text-sm text-gray-600" />
            </div>

            <div class="mt-2">
                <flux:button variant="primary" type="submit" class="w-full !bg-blue-600 hover:!bg-blue-700 !rounded-xl !py-3 font-bold shadow-md shadow-blue-100 dark:shadow-none transition-all" data-test="login-button">
                    {{ __('Iniciar Sesión') }}
                </flux:button>
            </div>
        </form>

        @if (Route::has('register'))
            <div class="pt-4 border-t border-gray-100 dark:border-gray-700 text-sm text-center text-zinc-600 dark:text-zinc-400">
                <span>{{ __('¿Aún no tienes cuenta?') }}</span>
                <flux:link :href="route('register')" wire:navigate class="font-bold text-blue-600 hover:text-blue-700">
                    {{ __('Regístrate aquí') }}
                </flux:link>
            </div>
        @endif
    </div>
</x-layouts.auth>
