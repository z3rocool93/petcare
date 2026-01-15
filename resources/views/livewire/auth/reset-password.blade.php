<x-layouts.auth>
    <div class="flex flex-col gap-6">
        <x-auth-header
            :title="__('Nueva Contraseña')"
            :description="__('Por favor, ingresa tu nueva clave para recuperar el acceso a PetCare.')"
        />

        <x-auth-session-status class="text-center text-sm font-bold text-blue-600" :status="session('status')" />

        <form method="POST" action="{{ route('password.update') }}" class="flex flex-col gap-6">
            @csrf

            <input type="hidden" name="token" value="{{ request()->route('token') }}">

            <div>
                <flux:input
                    name="email"
                    :value="old('email', request('email'))"
                    :label="__('Correo Electrónico')"
                    type="email"
                    required
                    readonly
                    class="bg-zinc-50 dark:bg-zinc-800/50 cursor-not-allowed"
                />
                @error('email')
                <p class="text-xs text-red-600 font-bold mt-2">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <flux:input
                    name="password"
                    :label="__('Nueva Contraseña')"
                    type="password"
                    required
                    autocomplete="new-password"
                    placeholder="••••••••"
                    viewable
                />
                @error('password')
                <p class="text-xs text-red-600 font-bold mt-2">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <flux:input
                    name="password_confirmation"
                    :label="__('Confirmar Nueva Contraseña')"
                    type="password"
                    required
                    autocomplete="new-password"
                    placeholder="••••••••"
                    viewable
                />
            </div>

            <div class="pt-2">
                <flux:button
                    type="submit"
                    variant="primary"
                    class="w-full !bg-blue-600 hover:!bg-blue-700 !text-white font-bold py-3 shadow-lg active:scale-[0.98] transition-all"
                >
                    {{ __('Actualizar Contraseña') }}
                </flux:button>
            </div>
        </form>
    </div>
</x-layouts.auth>
