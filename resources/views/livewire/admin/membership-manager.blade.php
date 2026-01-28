<?php
use function Livewire\Volt\{state, mount};
use Kreait\Firebase\Contract\Database;

state(['plans' => []]);

mount(function (Database $database) {
    // 1. Verificación de Seguridad
    if (! auth()->user()->isAdmin()) {
        abort(403, 'Acceso denegado. Solo administradores.');
    }

    // 2. Cargar la configuración correcta (Solo una vez)
    // Usamos el nodo que me mostraste: membership_plans
    $this->plans = $database->getReference('membership_plans')->getValue() ?? [];
});

$updatePlan = function (Database $database, $planId) {
    try {
        $cleanId = trim($planId);
        $data = $this->plans[$cleanId];

        $database->getReference("membership_plans/{$cleanId}")->set($data);

        // Feedback directo usando JS desde el servidor
        $this->js("Swal.fire({
            title: '¡Guardado!',
            text: 'El plan " . $data['name'] . " se actualizó correctamente.',
            icon: 'success',
            timer: 2000,
            showConfirmButton: false,
            borderRadius: '1.5rem'
        })");

    } catch (\Exception $e) {
        $this->js("Swal.fire({
            title: 'Error',
            text: '" . $e->getMessage() . "',
            icon: 'error'
        })");
    }
};
?>

<div class="p-6 space-y-6">
    <div class="flex justify-between items-end">
        <div>
            <h2 class="text-3xl font-black dark:text-secondary-light">Gestión de Membresías</h2>
            <p class="text-zinc-500 text-sm">Configuración global de niveles y límites de PetCare</p>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        @foreach($plans as $id => $plan)
            <div wire:key="plan-{{ $id }}" class="bg-white dark:bg-secondary-light p-6 rounded-3xl border border-zinc-200 dark:border-zinc-800 shadow-sm">
                <div class="flex justify-between items-center mb-6">
                    {{-- Usamos el color dinámico que viene de Firebase --}}
                    <span class="text-[10px] font-black uppercase px-3 py-1 bg-{{ $plan['color'] ?? 'blue' }}-100 text-{{ $plan['color'] ?? 'blue' }}-600 rounded-full">
                        {{ $plan['badge'] ?? $id }}
                    </span>
                </div>

                {{-- Cambiamos la forma de pasar los datos para que sea más limpia --}}
                <form wire:submit.prevent="updatePlan('{{ $id }}')" class="space-y-4">
                    {{-- RF1: Nombre del Nivel --}}
                    <flux:input wire:model="plans.{{ $id }}.name" label="Nombre del Plan" />

                    {{-- RF3: Precio --}}
                    <div class="grid grid-cols-1 gap-2">
                        <flux:input wire:model="plans.{{ $id }}.price" type="number" label="Precio ($)" prefix="CLP" />
                    </div>

                    {{-- RF2: Funcionalidades (Límite de mascotas) --}}
                    <flux:input wire:model="plans.{{ $id }}.limit_pets" type="number" label="Límite de Mascotas" placeholder="Ej: 3, 5, 999" />

                    <div class="pt-4">
                        <flux:button type="submit" variant="primary" class="w-full !bg-secondary !text-zinc-300 hover:!bg-primary-700 hover:!text-white font-bold">
                            Guardar Cambios
                        </flux:button>
                    </div>
                </form>
            </div>
        @endforeach
    </div>
</div>
