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
    {{-- El heading de settings también debería recibir estas clases si es un componente tuyo --}}
    @include('partials.settings-heading')

    <x-settings.layout :heading="__('Perfil')" :subheading="__('Actualiza tu información y foto de perfil')">

        {{-- CARD INFORMATIVA (Ajustada a Petróleo y Naranja) --}}
        <div class="mb-8 p-6 bg-secondary border border-secondary/10 rounded-[2.5rem] shadow-sm flex flex-col md:flex-row items-center gap-8">

            {{-- AVATAR CON PREVIEW --}}
            <div class="relative group">
                {{-- Cambiado bg-blue-600 a bg-primary-600 --}}
                <div class="h-28 w-28 rounded-[2rem] overflow-hidden border-4 border-secondary shadow-2xl bg-primary-600 flex items-center justify-center text-white">
                    @if ($photo)
                        <img src="{{ $photo->temporaryUrl() }}" class="h-full w-full object-cover">
                    @elseif (auth()->user()->profile_photo_path)
                        <img src="{{ Storage::disk('public')->url(auth()->user()->profile_photo_path) }}" class="h-full w-full object-cover">
                    @else
                        {{-- Fallback: Fondo naranja con iniciales en blanco --}}
                        <div class="h-full w-full bg-primary-600 flex items-center justify-center text-4xl font-black uppercase text-white">
                            {{ auth()->user()->initials() }}
                        </div>
                    @endif
                </div>
            </div>

            <div class="flex-1 text-center md:text-left">
                <div class="flex items-center justify-center md:justify-start gap-2 mb-1">
                    {{-- Badge del Plan: Cambiado de Blue a Primary (Naranja suave) --}}
                    <span class="px-2 py-0.5 bg-primary-50 text-primary-700 text-[10px] font-black uppercase rounded-lg tracking-widest border border-primary-100">
                        Cuenta {{ $planName }}
                    </span>
                </div>
                {{-- Nombre en Petróleo --}}
                <h3 class="text-2xl font-black text-brand-bone uppercase tracking-tight">{{ $name }}</h3>
                {{-- Email en Petróleo con opacidad --}}
                <p class="text-brand-bone/70 text-sm font-medium">{{ $email }}</p>
            </div>

            {{-- Botón Cambiar Plan en Petróleo --}}
            <flux:button
                href="{{ route('membership.index') }}"
                wire:navigate
                variant="ghost"
                class="rounded-xl !text-secondary hover:!bg-primary-50 hover:!text-primary-700 transition-colors"
            >
                Cambiar Plan
            </flux:button>
        </div>

        {{-- FORMULARIO DE EDICIÓN --}}
        <form wire:submit="updateProfileInformation" class="w-full space-y-6">

            {{-- INPUT DE FOTO --}}
            <flux:field>
                <flux:label class="!text-secondary font-black uppercase text-xs tracking-wider">Foto de Perfil</flux:label>
                <div class="mt-2 flex items-center gap-4" x-data>
                    <input type="file" wire:model="photo" x-ref="fileInput" class="hidden" accept="image/*" />

                    <flux:button
                        type="button"
                        icon="camera"
                        variant="filled"
                        class="rounded-xl !bg-primary-600 hover:!bg-primary-700 !text-white shadow-sm"
                        x-on:click="$refs.fileInput.click()"
                    >
                        Seleccionar imagen
                    </flux:button>

                    <flux:text class="!text-secondary/70 text-xs italic" wire:loading wire:target="photo">
                        Subiendo archivo...
                    </flux:text>
                </div>
                <flux:error name="photo" />
            </flux:field>

            <flux:field>
                <flux:label class="!text-secondary font-black text-xs uppercase tracking-tighter">Nombre Completo</flux:label>
                <flux:input wire:model="name" type="text" required autocomplete="name" class="!bg-white !border-secondary/20 focus:!ring-primary-600 !text-secondary" />
            </flux:field>

            <flux:field>
                <flux:label class="!text-secondary font-black text-xs uppercase tracking-tighter">Correo Electrónico</flux:label>
                <flux:input wire:model="email" type="email" required autocomplete="email" class="!bg-white !border-secondary/20 focus:!ring-primary-600 !text-secondary" />

                @if (auth()->user() instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! auth()->user()->hasVerifiedEmail())
                    <div class="mt-4 p-4 bg-primary-50 border border-primary-100 rounded-2xl">
                        <flux:text class="!text-secondary text-sm">
                            {{ __('Tu dirección de correo no está verificada.') }}
                            <flux:link class="!text-primary-700 font-bold cursor-pointer underline" wire:click.prevent="resendVerificationNotification">
                                {{ __('Haz clic aquí para re-enviar.') }}
                            </flux:link>
                        </flux:text>
                    </div>
                @endif
            </flux:field>

            <div class="flex items-center gap-4 pt-4 border-t border-secondary/10">
                <flux:button variant="primary" type="submit" class="px-10 rounded-xl !bg-primary-600 hover:!bg-primary-700 shadow-lg shadow-primary-600/20">
                    {{ __('Guardar Perfil') }}
                </flux:button>

                <x-action-message class="!text-secondary font-bold flex items-center gap-2" on="profile-updated">
                    <flux:icon.check-circle variant="micro" class="text-green-600" />
                    {{ __('¡Actualizado con éxito!') }}
                </x-action-message>
            </div>
        </form>

        {{-- Nota: El componente de borrar usuario también debería ser revisado para seguir esta línea --}}
        <div class="mt-12 pt-12 border-t border-secondary/10">
            <livewire:settings.delete-user-form />
        </div>
    </x-settings.layout>
</section>
