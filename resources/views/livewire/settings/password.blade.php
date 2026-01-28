<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Livewire\Volt\Component;

new class extends Component {
    public string $current_password = '';
    public string $password = '';
    public string $password_confirmation = '';

    /**
     * Update the password for the currently authenticated user.
     */
    public function updatePassword(): void
    {
        try {
            $validated = $this->validate([
                'current_password' => ['required', 'string', 'current_password'],
                'password' => ['required', 'string', Password::defaults(), 'confirmed'],
            ]);
        } catch (ValidationException $e) {
            $this->reset('current_password', 'password', 'password_confirmation');

            throw $e;
        }

        Auth::user()->update([
            'password' => $validated['password'],
        ]);

        $this->reset('current_password', 'password', 'password_confirmation');

        $this->dispatch('password-updated');
    }
}; ?>

<section class="w-full">
    @include('partials.settings-heading')

    <x-settings.layout :heading="__('Actualizar contraseña')" :subheading="__('Asegúrese de que su cuenta utilice una contraseña larga y aleatoria para mantener la seguridad')">

        <form method="POST" wire:submit="updatePassword" class="mt-6 space-y-6">

            {{-- Contraseña Actual --}}
            <flux:field>
                <flux:label class="!text-secondary font-black text-xs uppercase tracking-tighter">{{ __('Contraseña actual') }}</flux:label>
                <flux:input
                    wire:model="current_password"
                    type="password"
                    required
                    autocomplete="current-password"
                    class="!bg-white !border-secondary/20 focus:!ring-primary-600 !text-secondary"
                />
            </flux:field>

            {{-- Nueva Contraseña --}}
            <flux:field>
                <flux:label class="!text-secondary font-black text-xs uppercase tracking-tighter">{{ __('Nueva contraseña') }}</flux:label>
                <flux:input
                    wire:model="password"
                    type="password"
                    required
                    autocomplete="new-password"
                    class="!bg-white !border-secondary/20 focus:!ring-primary-600 !text-secondary"
                />
            </flux:field>

            {{-- Confirmar Contraseña --}}
            <flux:field>
                <flux:label class="!text-secondary font-black text-xs uppercase tracking-tighter">{{ __('Confirma la contraseña') }}</flux:label>
                <flux:input
                    wire:model="password_confirmation"
                    type="password"
                    required
                    autocomplete="new-password"
                    class="!bg-white !border-secondary/20 focus:!ring-primary-600 !text-secondary"
                />
            </flux:field>

            <div class="flex items-center gap-4 pt-4 border-t border-secondary/10">
                {{-- Botón Guardar con estilo Naranja PetCare --}}
                <flux:button
                    variant="primary"
                    type="submit"
                    class="px-10 rounded-xl !bg-primary-600 hover:!bg-primary-700 !text-white shadow-lg shadow-primary-600/20"
                >
                    {{ __('Guardar Cambios') }}
                </flux:button>

                {{-- Mensaje de éxito consistente con el perfil --}}
                <x-action-message class="!text-secondary font-bold flex items-center gap-2" on="password-updated">
                    <flux:icon.check-circle variant="micro" class="text-green-600" />
                    {{ __('¡Contraseña actualizada!') }}
                </x-action-message>
            </div>
        </form>
    </x-settings.layout>
</section>
