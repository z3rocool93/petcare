<?php

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage; // Para manejar el borrado de fotos viejas
use Illuminate\Validation\Rule;
use Livewire\Volt\Component;
use Livewire\WithFileUploads; // Trait necesario para subir archivos
use Kreait\Firebase\Contract\Database;

new class extends Component {
    use WithFileUploads;

    public string $name = '';
    public string $email = '';
    public $photo; // Propiedad para la nueva foto seleccionada
    public string $planName = 'Básico';

    /**
     * Mount the component.
     */
    public function mount(Database $database): void
    {
        $user = Auth::user();
        $this->name = $user->name;
        $this->email = $user->email;

        // RF9: Traer el nombre del plan desde Firebase
        $subscription = $database->getReference("user_subscriptions/{$user->id}")->getValue();
        $this->planName = $subscription['plan_name'] ?? 'Básico';
    }

    /**
     * Update the profile information for the currently authenticated user.
     */
    public function updateProfileInformation(): void
    {
        $user = Auth::user();

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required', 'string', 'lowercase', 'email', 'max:255',
                Rule::unique(User::class)->ignore($user->id)
            ],
            'photo' => ['nullable', 'image', 'max:1024'],
        ]);

        // Mantenemos la ruta actual por defecto
        $path = $user->profile_photo_path;

        // RF4: Si se subió una foto nueva
        if ($this->photo) {
            // Borramos la anterior si existe
            if ($user->profile_photo_path) {
                Storage::disk('public')->delete($user->profile_photo_path);
            }

            // Guardamos la nueva y actualizamos la variable $path
            $path = $this->photo->store('profile-photos', 'public');
        }

        // ACTUALIZACIÓN DIRECTA: Usamos el ID para asegurar que afectamos a la DB
        User::where('id', $user->id)->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'profile_photo_path' => $path,
            'email_verified_at' => ($user->email !== $validated['email']) ? null : $user->email_verified_at,
        ]);

        // VITAL: Refrescamos la instancia de Auth para que reconozca los cambios
        Auth::user()->refresh();

        // Limpiamos la variable de Livewire para que la vista use la ruta de la DB
        $this->photo = null;

        $this->dispatch('profile-updated', name: Auth::user()->name);
        $this->dispatch('notify', '¡Perfil y foto guardados con éxito!');
    }

    public function resendVerificationNotification(): void
    {
        $user = Auth::user();
        if ($user->hasVerifiedEmail()) {
            $this->redirectIntended(default: route('dashboard', absolute: false));
            return;
        }
        $user->sendEmailVerificationNotification();
        Session::flash('status', 'verification-link-sent');
    }
}; ?>

<section class="w-full">
    @include('partials.settings-heading')

    <x-settings.layout :heading="__('Perfil')" :subheading="__('Actualiza tu información y foto de perfil')">

        {{-- CARD INFORMATIVA (RF9 y RF4) --}}
        <div class="mb-8 p-6 bg-white dark:bg-zinc-900 border border-zinc-100 dark:border-zinc-800 rounded-[2.5rem] shadow-sm flex flex-col md:flex-row items-center gap-8">
            {{-- AVATAR CON PREVIEW --}}
            <div class="relative group">
                <div class="h-28 w-28 rounded-[2rem] overflow-hidden border-4 border-white dark:border-zinc-800 shadow-2xl bg-blue-600 flex items-center justify-center text-white">
                    @if ($photo)
                        {{-- Mientras se está subiendo o antes de guardar --}}
                        <img src="{{ $photo->temporaryUrl() }}" class="h-full w-full object-cover">
                    @elseif (auth()->user()->profile_photo_path)
                        {{-- Foto persistida en la DB y cargada desde el disco public --}}
                        <img src="{{ Storage::disk('public')->url(auth()->user()->profile_photo_path) }}" class="h-full w-full object-cover">
                    @else
                        {{-- Fallback si no hay nada --}}
                        <div class="h-full w-full bg-blue-600 flex items-center justify-center text-4xl font-black uppercase">
                            {{-- Llamamos al método initials() desde el modelo del usuario --}}
                            {{ auth()->user()->initials() }}
                        </div>
                    @endif
                </div>
            </div>

            <div class="flex-1 text-center md:text-left">
                <div class="flex items-center justify-center md:justify-start gap-2 mb-1">
                    <span class="px-2 py-0.5 bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 text-[10px] font-black uppercase rounded-lg tracking-widest">
                        Cuenta {{ $planName }}
                    </span>
                </div>
                <h3 class="text-2xl font-black text-zinc-900 dark:text-white uppercase tracking-tight">{{ $name }}</h3>
                <p class="text-zinc-500 text-sm">{{ $email }}</p>
            </div>

            <flux:button href="{{ route('membership.index') }}" wire:navigate variant="ghost" class="rounded-xl">
                Cambiar Plan
            </flux:button>
        </div>

        {{-- FORMULARIO DE EDICIÓN --}}
        <form wire:submit="updateProfileInformation" class="w-full space-y-6">

            {{-- INPUT DE FOTO --}}
            <flux:field>
                <flux:label>Foto de Perfil</flux:label>
                <div class="mt-2 flex items-center gap-4" x-data>
                    {{-- Input real oculto con una referencia (x-ref) --}}
                    <input
                        type="file"
                        wire:model="photo"
                        x-ref="fileInput"
                        class="hidden"
                        accept="image/*"
                    />

                    {{-- Botón que al hacer clic activa el input oculto --}}
                    <flux:button
                        type="button"
                        icon="camera"
                        variant="filled"
                        class="rounded-xl"
                        x-on:click="$refs.fileInput.click()"
                    >
                        Seleccionar imagen
                    </flux:button>

                    {{-- Indicador de carga --}}
                    <flux:text class="text-xs italic" wire:loading wire:target="photo">
                        Subiendo archivo...
                    </flux:text>
                </div>
                <flux:error name="photo" />
            </flux:field>

            <flux:input wire:model="name" :label="__('Nombre')" type="text" required autocomplete="name" />

            <div>
                <flux:input wire:model="email" :label="__('Email')" type="email" required autocomplete="email" />

                @if (auth()->user() instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! auth()->user()->hasVerifiedEmail())
                    <div class="mt-4 p-4 bg-amber-50 dark:bg-amber-900/10 border border-amber-100 dark:border-amber-800 rounded-2xl">
                        <flux:text class="text-amber-800 dark:text-amber-400 text-sm">
                            {{ __('Your email address is unverified.') }}
                            <flux:link class="font-bold cursor-pointer underline" wire:click.prevent="resendVerificationNotification">
                                {{ __('Click here to re-send.') }}
                            </flux:link>
                        </flux:text>
                    </div>
                @endif
            </div>

            <div class="flex items-center gap-4 pt-4 border-t border-zinc-100 dark:border-zinc-800">
                <flux:button variant="primary" type="submit" class="px-10 rounded-xl shadow-lg shadow-blue-500/20">
                    {{ __('Guardar Perfil') }}
                </flux:button>

                <x-action-message class="text-green-600 font-bold" on="profile-updated">
                    {{ __('Updated!') }}
                </x-action-message>
            </div>
        </form>

        <div class="mt-12 pt-12">
            <livewire:settings.delete-user-form />
        </div>
    </x-settings.layout>
</section>
