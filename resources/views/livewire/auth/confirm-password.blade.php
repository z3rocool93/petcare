<x-layouts.auth>
    <div class="flex flex-col gap-6">
        {{-- Encabezado con Ícono de Seguridad --}}
        <div class="flex flex-col items-center gap-2">
            <div class="h-16 w-16 bg-zinc-100 dark:bg-zinc-800 rounded-[1.5rem] flex items-center justify-center text-zinc-900 dark:text-white shadow-inner mb-2">
                <flux:icon.lock-closed variant="micro" class="w-8 h-8" />
            </div>

            <x-auth-header
                :title="__('Confirmar Acceso')"
                :description="__('Esta es un área segura de PetCare. Por favor, confirma tu contraseña antes de continuar para proteger tu información.')"
                class="text-center"
            />
        </div>

        <x-auth-session-status class="text-center" :status="session('status')" />

        <form method="POST" action="{{ route('password.confirm.store') }}" class="flex flex-col gap-6">
            @csrf

            {{-- Input de Contraseña con Estilo Flux --}}
            <flux:input
                name="password"
                :label="__('Tu Contraseña')"
                type="password"
                required
                autocomplete="current-password"
                :placeholder="__('Ingresa tu contraseña actual')"
                viewable
                class="rounded-xl"
            />

            {{-- Botón de Acción con Estilo PetCare --}}
            <div class="flex flex-col gap-3">
                <flux:button variant="primary" type="submit" class="w-full rounded-xl shadow-lg shadow-blue-500/20" data-test="confirm-password-button">
                    {{ __('Confirmar Identidad') }}
                </flux:button>

                {{-- Link de escape por si el usuario se arrepiente --}}
                <flux:button href="{{ route('dashboard') }}" variant="ghost" class="w-full rounded-xl">
                    {{ __('Cancelar y Volver') }}
                </flux:button>
            </div>
        </form>
    </div>
</x-layouts.auth>
