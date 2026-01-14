<?php

use function Livewire\Volt\{state, mount, computed, updating};
use Kreait\Firebase\Contract\Database;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

state([
    'pets' => [],
    'userSub' => null,
    'breeds' => [],
    'dogBreedsCache' => [],
    'search' => '',

    // Control de Modales y Edición
    'editingPetId' => null,
    'viewingPet' => null,

    // Formulario
    'nombre' => '',
    'especie' => '',
    'raza' => '',
    'fecha_nacimiento' => '',
    'notas' => ''
]);

$fetchPets = function (Database $database) {
    return $database->getReference('users/' . auth()->id() . '/pets')->getValue() ?? [];
};

mount(function (Database $database) use ($fetchPets) {
    $this->pets = $fetchPets($database);
    $uid = auth()->id();
    $this->userSub = $database->getReference("user_subscriptions/$uid")->getValue();

    $response = Http::get('https://dog.ceo/api/breeds/list/all');
    if ($response->successful()) {
        $this->dogBreedsCache = array_keys($response->json()['message']);
    }
});

$checkLimit = function() {
    $planId = $this->userSub['plan_id'] ?? 'basic';
    $currentCount = count($this->pets);

    $limits = [
        'basic' => 3,
        'premium' => 5,
        'vip' => 999
    ];

    $maxAllowed = $limits[$planId] ?? 3;

    if ($currentCount >= $maxAllowed) {
        $this->js("Swal.fire({
            title: 'Límite alcanzado',
            text: 'Tu plan ' + '" . ucfirst($planId) . "' + ' permite un máximo de ' + $maxAllowed + ' mascotas.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Ver Planes 🐾',
            cancelButtonText: 'Cerrar',
            confirmButtonColor: '#2563eb',
            borderRadius: '1.5rem'
        }).then((result) => {
            if (result.isConfirmed) { window.location.href = '/membresias'; }
        })");
        return false;
    }
    return true;
};

$openCreateModal = function() {
    if ($this->checkLimit()) {
        $this->reset(['nombre', 'especie', 'raza', 'fecha_nacimiento', 'notas', 'editingPetId', 'breeds']);
        $this->dispatch('open-modal-form');
    }
};

$filteredPets = computed(function () {
    return collect($this->pets)
        ->filter(fn($pet) => empty($this->search) || str_contains(strtolower($pet['Nombre'] ?? ''), strtolower($this->search)))
        ->toArray();
});

$updatedEspecie = function () {
    $this->raza = '';
    if ($this->especie === 'Perro') {
        $this->breeds = $this->dogBreedsCache;
    } elseif ($this->especie === 'Gato') {
        $this->breeds = ['Persa', 'Siamés', 'Maine Coon', 'Bengala', 'Sphynx', 'Ragdoll', 'Común Europeo'];
    } else { $this->breeds = []; }
};

$viewPet = function ($id) {
    $this->viewingPet = $this->pets[$id];
    $this->dispatch('open-modal-view');
};

$editPet = function ($id) {
    $pet = $this->pets[$id];
    $this->editingPetId = $id;
    $this->nombre = $pet['Nombre'];
    $this->especie = $pet['Especie'];
    $this->updatedEspecie();
    $this->raza = $pet['Raza'] ?? '';
    $this->fecha_nacimiento = $pet['Fecha_nacimiento'];
    $this->notas = $pet['Notas_generales'] ?? '';

    $this->dispatch('open-modal-form');
};

$save = function (Database $database) use ($fetchPets) {
    if (!$this->editingPetId && !$this->checkLimit()) return;

    $this->validate([
        'nombre' => 'required|min:2',
        'especie' => 'required',
        'fecha_nacimiento' => 'required|date',
    ]);

    $data = [
        'Nombre' => $this->nombre,
        'Especie' => $this->especie,
        'Raza' => $this->raza,
        'Fecha_nacimiento' => $this->fecha_nacimiento,
        'Notas_generales' => $this->notas,
        'updated_at' => now()->toDateTimeString()
    ];

    $ref = 'users/' . auth()->id() . '/pets';

    if ($this->editingPetId) {
        $database->getReference($ref . '/' . $this->editingPetId)->update($data);
        $msg = '¡Datos actualizados!';
    } else {
        $database->getReference($ref)->push($data);
        $msg = '¡Mascota registrada!';
    }

    $this->reset(['nombre', 'especie', 'raza', 'fecha_nacimiento', 'notas', 'editingPetId', 'breeds']);
    $this->pets = $fetchPets($database);
    $this->dispatch('close-modal-form');
    $this->dispatch('notify', $msg);
};

$deletePet = function (Database $database, $id) use ($fetchPets) {
    $database->getReference('users/' . auth()->id() . '/pets/' . $id)->remove();
    $this->pets = $fetchPets($database);
    $this->dispatch('notify', 'Mascota eliminada');
};

?>

<div class="p-6 max-w-7xl mx-auto" x-data="{ confirmingDelete: null }">

    <div class="flex flex-col md:flex-row justify-between items-center gap-4 mb-8">
        <div>
            <h2 class="text-3xl font-black text-gray-900 dark:text-white">Mis Mascotas</h2>
        </div>
        <div class="flex w-full md:w-auto gap-3 items-center">
            <div class="text-right hidden sm:block mr-2">
                <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Capacidad</p>
                <p class="text-xs font-bold text-gray-600 dark:text-gray-300">
                    {{ count($this->pets) }} / {{ ($this->userSub['plan_id'] ?? 'basic') === 'vip' ? '∞' : (($this->userSub['plan_id'] ?? 'basic') === 'premium' ? '5' : '3') }}
                </p>
            </div>

            <input wire:model.live="search" type="text" placeholder="Buscar..." class="rounded-xl border-gray-200 dark:bg-gray-800 dark:text-white flex-grow">

            {{-- Volvemos a wire:click para que checkLimit() funcione --}}
            <button wire:click="openCreateModal" class="bg-blue-600 text-white px-6 py-2 rounded-xl font-bold shadow-lg hover:bg-blue-700 transition">
                + Nueva Mascota
            </button>
        </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
        @forelse($this->filteredPets as $id => $pet)
            <div wire:key="pet-{{ $id }}" class="bg-white dark:bg-gray-800 rounded-3xl p-5 border border-gray-100 dark:border-gray-700 shadow-sm hover:shadow-xl transition-all relative">
                <div class="flex flex-col items-center text-center">
                    <div class="w-20 h-20 rounded-2xl bg-gray-50 dark:bg-gray-700 flex items-center justify-center text-4xl mb-4">
                        {{ $pet['Especie'] === 'Perro' ? '🐶' : ($pet['Especie'] === 'Gato' ? '🐱' : '🐾') }}
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white">{{ $pet['Nombre'] }}</h3>
                    <span class="text-sm text-blue-600 font-semibold bg-blue-50 dark:bg-blue-900/30 px-3 py-1 rounded-full mt-2">
                        {{ $pet['Raza'] ?: 'Mestizo' }}
                    </span>
                </div>

                <div class="flex gap-2 mt-6">
                    <button wire:click="viewPet('{{ $id }}')" class="flex-1 bg-gray-900 dark:bg-white dark:text-gray-900 text-white py-2 rounded-xl text-xs font-bold hover:opacity-80">
                        Ver Ficha
                    </button>

                    <a href="{{ route('pets.history', $id) }}" wire:navigate class="p-2 bg-blue-50 dark:bg-blue-900/30 text-blue-600 rounded-xl">
                        <flux:icon.clipboard-document-list variant="micro" />
                    </a>

                    <button wire:click="editPet('{{ $id }}')" class="p-2 bg-gray-100 dark:bg-gray-700 rounded-xl">✏️</button>
                    <button @click="confirmingDelete = '{{ $id }}'" class="p-2 bg-gray-100 dark:bg-gray-700 rounded-xl">🗑️</button>
                </div>

                <div x-show="confirmingDelete === '{{ $id }}'" class="absolute inset-0 bg-white/95 dark:bg-gray-800/95 rounded-3xl flex flex-col items-center justify-center p-4 z-10" x-cloak>
                    <p class="text-sm font-bold mb-3">¿Eliminar a {{ $pet['Nombre'] }}?</p>
                    <div class="flex gap-2">
                        <button @click="confirmingDelete = null" class="px-4 py-1 text-xs bg-gray-200 rounded-lg">No</button>
                        <button wire:click="deletePet('{{ $id }}')" class="px-4 py-1 text-xs bg-red-600 text-white rounded-lg font-bold">Sí</button>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-span-full text-center py-20 text-gray-400">No hay mascotas registradas.</div>
        @endforelse
    </div>

    {{-- MODAL FORMULARIO: Ajustado para mayor compatibilidad --}}
    <dialog wire:ignore.self id="modal-pet"
            x-on:open-modal-form.window="document.getElementById('modal-pet').showModal()"
            x-on:close-modal-form.window="document.getElementById('modal-pet').close()"
            class="modal p-0 rounded-3xl shadow-2xl backdrop:bg-gray-900/50">
        <div class="bg-white dark:bg-gray-800 w-full max-w-md p-8">
            <h3 class="text-2xl font-bold mb-6 dark:text-white">{{ $editingPetId ? 'Editar Mascota' : 'Nueva Mascota' }}</h3>
            <form wire:submit="save" class="space-y-4">
                <flux:input wire:model="nombre" label="Nombre" />
                <div class="grid grid-cols-2 gap-4">
                    <flux:select wire:model.live="especie" label="Especie">
                        <option value="">Elegir...</option>
                        <option value="Perro">Perro</option>
                        <option value="Gato">Gato</option>
                    </flux:select>
                    <flux:select wire:model="raza" label="Raza">
                        <option value="">Elegir...</option>
                        @foreach($breeds ?? [] as $breed) <option value="{{ ucfirst($breed) }}">{{ ucfirst($breed) }}</option> @endforeach
                    </flux:select>
                </div>
                <flux:input wire:model="fecha_nacimiento" type="date" label="Fecha de Nacimiento" />
                <flux:textarea wire:model="notas" label="Notas Adicionales" />
                <div class="flex gap-3 mt-6">
                    <flux:button type="button" onclick="document.getElementById('modal-pet').close()" variant="ghost" class="flex-1">Cancelar</flux:button>
                    <flux:button type="submit" variant="primary" class="flex-1 !bg-blue-600">{{ $editingPetId ? 'Actualizar' : 'Guardar' }}</flux:button>
                </div>
            </form>
        </div>
    </dialog>

    {{-- MODAL VISTA: Ajustado para mayor compatibilidad --}}
    <dialog wire:ignore.self id="modal-view"
            x-on:open-modal-view.window="document.getElementById('modal-view').showModal()"
            class="modal p-0 rounded-3xl shadow-2xl backdrop:bg-gray-900/50">
        @if($viewingPet)
            <div class="bg-white dark:bg-gray-800 w-full max-w-sm p-8 text-center">
                <div class="text-6xl mb-4">{{ $viewingPet['Especie'] === 'Perro' ? '🐶' : '🐱' }}</div>
                <h3 class="text-3xl font-black dark:text-white">{{ $viewingPet['Nombre'] }}</h3>
                <p class="text-blue-600 font-bold mb-6">{{ $viewingPet['Raza'] ?: 'Mestizo' }}</p>

                <div class="bg-gray-50 dark:bg-gray-700/50 rounded-2xl p-4 space-y-3 text-left mb-6">
                    <div class="flex justify-between">
                        <span class="text-gray-500 text-sm font-medium">Cumpleaños</span>
                        <span class="font-bold dark:text-gray-200">{{ Carbon::parse($viewingPet['Fecha_nacimiento'])->format('d/m/Y') }}</span>
                    </div>
                    <div class="flex justify-between border-t border-gray-100 pt-2">
                        <span class="text-gray-500 text-sm font-medium">Edad</span>
                        <span class="font-bold dark:text-gray-200">{{ Carbon::parse($viewingPet['Fecha_nacimiento'])->age }} años</span>
                    </div>
                </div>
                <flux:button onclick="document.getElementById('modal-view').close()" class="w-full">Cerrar Ficha</flux:button>
            </div>
        @endif
    </dialog>

    <div x-data="{ show: false, message: '' }" x-on:notify.window="show = true; message = $event.detail; setTimeout(() => show = false, 3000)"
         x-show="show" class="fixed bottom-5 right-5 bg-gray-900 text-white px-6 py-3 rounded-2xl shadow-2xl z-50" x-cloak>
        <span x-text="message"></span>
    </div>
</div>
