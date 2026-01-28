<x-layouts.auth>
    <div class="flex flex-col gap-8 bg-white dark:bg-secondary-light p-8 rounded-2xl shadow-xl border border-gray-100 dark:border-gray-700">

        <div class="flex flex-col items-center gap-4">
            <div class="w-16 h-16 bg-primary-600 rounded-2xl flex items-center justify-center text-3xl shadow-lg shadow-blue-100 dark:shadow-none">
                🐾
            </div>
            <div class="text-center">
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white">{{ __('Crear una cuenta') }}</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ __('Únete para empezar a cuidar a tus mascotas') }}</p>
            </div>
        </div>

        <x-auth-session-status class="text-center text-sm font-medium text-green-600" :status="session('status')" />

        <form method="POST" action="{{ route('register.store') }}" class="flex flex-col gap-5">
            @csrf

            <flux:input
                name="name"
                :label="__('Nombre completo')"
                :value="old('name')"
                type="text"
                required
                autofocus
                autocomplete="name"
                placeholder="Juan Pérez"
                class="rounded-xl"
            />

            <flux:input
                name="email"
                :label="__('Correo electrónico')"
                :value="old('email')"
                type="email"
                required
                autocomplete="email"
                placeholder="ejemplo@correo.cl"
                class="rounded-xl"
            />

            <flux:input
                name="password"
                :label="__('Contraseña')"
                type="password"
                required
                autocomplete="new-password"
                placeholder="Mínimo 8 caracteres"
                viewable
                class="rounded-xl"
            />

            <flux:input
                name="password_confirmation"
                :label="__('Confirmar contraseña')"
                type="password"
                required
                autocomplete="new-password"
                placeholder="Repite tu contraseña"
                viewable
                class="rounded-xl"
            />

            <div class="mt-4">
                <flux:button type="submit" variant="primary" class="w-full !bg-blue-600 hover:!bg-blue-700 !rounded-xl !py-3 font-bold shadow-md shadow-blue-100 dark:shadow-none transition-all" data-test="register-user-button">
                    {{ __('Crear mi cuenta') }}
                </flux:button>
            </div>
        </form>

        <div class="pt-4 border-t border-gray-100 dark:border-gray-700 text-sm text-center text-zinc-600 dark:text-zinc-400">
            <span>{{ __('¿Ya tienes una cuenta?') }}</span>
            <flux:link :href="route('login')" wire:navigate class="font-bold text-blue-600 hover:text-blue-700">
                {{ __('Inicia sesión aquí') }}
            </flux:link>
        </div>
    </div>
</x-layouts.auth>
