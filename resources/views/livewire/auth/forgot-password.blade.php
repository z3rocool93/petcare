<x-layouts.auth>
    <div class="flex flex-col gap-6">
        <x-auth-header :title="__('Recuperar contraseña')" :description="__('Ingresa tu correo para recibir un enlace de restablecimiento')" />

        <x-auth-session-status class="text-center bg-green-50 text-green-600 p-3 rounded-xl text-xs font-bold" :status="session('status')" />

        <form method="POST" action="{{ route('password.email') }}" class="flex flex-col gap-6">
            @csrf

            <div>
                <flux:input
                    name="email"
                    :label="__('Correo Electrónico')"
                    type="email"
                    value="{{ old('email') }}"
                    required
                    autofocus
                    placeholder="ejemplo@correo.com"
                    {{-- Flux detecta errores automáticamente si usamos el name correcto --}}
                />
                @error('email')
                <p class="text-xs text-red-600 font-bold mt-2">{{ $message }}</p>
                @enderror
            </div>

            <flux:button variant="primary" type="submit" class="w-full !bg-blue-600 hover:!bg-blue-700 !text-white font-bold py-3 shadow-lg active:scale-[0.98] transition-all">
                {{ __('Enviar enlace al correo') }}
            </flux:button>
        </form>

        <div class="space-x-1 rtl:space-x-reverse text-center text-sm text-zinc-400">
            <span>{{ __('¿Recordaste tu clave?') }}</span>
            <flux:link :href="route('login')" wire:navigate class="font-bold text-blue-600 hover:text-blue-700">
                {{ __('Volver al login') }}
            </flux:link>
        </div>
    </div>
</x-layouts.auth>
