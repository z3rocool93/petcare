<div class="flex items-start max-md:flex-col">
    {{-- MENÚ LATERAL DE AJUSTES --}}
    <div class="me-10 w-full pb-4 md:w-[220px]">
        <flux:navlist variant="outline">
            {{-- Aplicamos el mismo estilo que el sidebar principal --}}
            <flux:navlist.item
                :href="route('profile.edit')"
                wire:navigate
                class="rounded-xl !text-secondary hover:!bg-primary-50 hover:!text-primary-700 data-[current]:!bg-primary-600 data-[current]:!text-white transition-all"
            >
                {{ __('Perfil') }}
            </flux:navlist.item>

            <flux:navlist.item
                :href="route('user-password.edit')"
                wire:navigate
                class="rounded-xl !text-secondary hover:!bg-primary-50 hover:!text-primary-700 data-[current]:!bg-primary-600 data-[current]:!text-white transition-all"
            >
                {{ __('Contraseña') }}
            </flux:navlist.item>

            @if (Laravel\Fortify\Features::canManageTwoFactorAuthentication())
                <flux:navlist.item
                    :href="route('two-factor.show')"
                    wire:navigate
                    class="rounded-xl !text-secondary hover:!bg-primary-50 hover:!text-primary-700 data-[current]:!bg-primary-600 data-[current]:!text-white transition-all"
                >
                    {{ __('Seguridad / Doble Factor') }}
                </flux:navlist.item>
            @endif

            <flux:navlist.item
                :href="route('appearance.edit')"
                wire:navigate
                class="rounded-xl !text-secondary hover:!bg-primary-50 hover:!text-primary-700 data-[current]:!bg-primary-600 data-[current]:!text-white transition-all"
            >
                {{ __('Apariencia') }}
            </flux:navlist.item>
        </flux:navlist>
    </div>

    <flux:separator class="md:hidden" />

    {{-- CONTENIDO Y CABECERA --}}
    <div class="flex-1 self-stretch max-md:pt-6">
        {{-- TÍTULO: Petróleo Puro, Negrita Extrema y Mayúsculas --}}
        <flux:heading class="!text-secondary !font-black uppercase tracking-tight !text-2xl">
            {{ $heading ?? '' }}
        </flux:heading>

        {{-- SUBTÍTULO: Petróleo con Opacidad --}}
        <flux:subheading class="!text-secondary/60 !font-medium">
            {{ $subheading ?? '' }}
        </flux:subheading>

        <div class="mt-8 w-full max-w-lg">
            {{ $slot }}
        </div>
    </div>
</div>
