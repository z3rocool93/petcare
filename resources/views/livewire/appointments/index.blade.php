<?php

use function Livewire\Volt\{state, mount, layout, computed};
use Kreait\Firebase\Contract\Database;
use Carbon\Carbon;

layout('components.layouts.app');

state([
    'appointments' => [],
    'myPets' => [],
    // Veterinarios con HORARIOS DIFERENCIADOS
    'vets' => [
        [
            'id' => 'v1',
            'name' => 'Dr. Ricardo Soto',
            'specialty' => 'Cirugía',
            'hours' => ['09:00', '09:30', '10:00', '10:30', '11:00', '11:30', '12:00'] // Mañana
        ],
        [
            'id' => 'v2',
            'name' => 'Dra. María Paz',
            'specialty' => 'Felinología',
            'hours' => ['15:00', '15:30', '16:00', '16:30', '17:00', '17:30'] // Tarde
        ],
        [
            'id' => 'v3',
            'name' => 'Dr. Carlos Ruiz',
            'specialty' => 'General',
            'hours' => ['09:00', '10:00', '11:00', '12:00', '15:00', '16:00', '17:00'] // Bloques de 1hr
        ],
    ],
    // Formulario
    'vet_id' => '',
    'pet_id' => '',
    'date' => '',
    'time' => '',
    'reason' => '',
    'due_date' => '',
]);

mount(function (Database $database) {
    $uid = auth()->id();
    $this->appointments = $database->getReference("users/$uid/appointments")->getValue() ?? [];
    $this->myPets = $database->getReference("users/$uid/pets")->getValue() ?? [];
});

// PROPIEDAD COMPUTADA: Slots disponibles basados en el horario del médico elegido
$availableSlots = computed(function () {
    if (!$this->vet_id || !$this->date) return [];

    // 1. Obtenemos el médico y su horario base
    $selectedVet = collect($this->vets)->firstWhere('id', $this->vet_id);
    $baseHours = $selectedVet['hours'] ?? [];

    // 2. Buscamos qué horas ya están ocupadas para este médico en esta fecha
    $occupied = collect($this->appointments)
        ->where('vet_id', $this->vet_id)
        ->where('date', $this->date)
        ->pluck('time')
        ->toArray();

    // 3. Devolvemos solo lo que no está ocupado
    return array_filter($baseHours, fn($slot) => !in_array($slot, $occupied));
});

$sortedAppointments = computed(function () {
    return collect($this->appointments)
        ->map(fn($item, $key) => array_merge(['id' => $key, 'vet_name' => 'General'], $item))
        ->sortBy('date')->values()->toArray();
});

$saveAppointment = function (Database $database) {
    $this->validate([
        'vet_id' => 'required',
        'pet_id' => 'required',
        'date' => 'required|date|after_or_equal:today',
        'time' => 'required',
        'reason' => 'required',
        // Validamos la fecha de vencimiento solo si es vacuna
        'due_date' => $this->reason === 'Vacuna' ? 'required|date|after:date' : 'nullable'
    ], [
        'due_date.after' => 'La fecha de refuerzo debe ser posterior a la fecha de la cita.',
        'due_date.required' => 'La fecha de refuerzo es obligatoria para vacunas.'
    ]);

    $uid = auth()->id();
    $vetName = collect($this->vets)->firstWhere('id', $this->vet_id)['name'];
    $petName = $this->myPets[$this->pet_id]['Nombre'] ?? 'Mascota';

    $database->getReference("users/$uid/appointments")->push([
        'vet_id' => $this->vet_id,
        'vet_name' => $vetName,
        'pet_id' => $this->pet_id,
        'pet_name' => $petName,
        'date' => $this->date,
        'time' => $this->time,
        'reason' => $this->reason,
        'due_date' => $this->due_date, // <--- IMPORTANTE: Guardar la fecha de vencimiento
        'status' => 'pendiente'
    ]);

    // Resetear también due_date
    $this->reset(['vet_id', 'pet_id', 'date', 'time', 'reason', 'due_date']);

    $this->reset(['vet_id', 'pet_id', 'date', 'time', 'reason']);
    $this->appointments = $database->getReference("users/$uid/appointments")->getValue() ?? [];
    $this->dispatch('close-modal-appt');
    $this->dispatch('notify', 'Cita agendada correctamente.');
};

$deleteAppointment = function (Database $database, $id) {
    $uid = auth()->id();
    $database->getReference("users/$uid/appointments/$id")->remove();
    $this->appointments = $database->getReference("users/$uid/appointments")->getValue() ?? [];
    $this->dispatch('notify', 'Cita cancelada correctamente.');
};
?>

<div class="p-6 max-w-5xl mx-auto" x-data="{ confirmingCancel: null }">
    <div class="flex justify-between items-end mb-8">
        <div>
            <h2 class="text-3xl font-black text-zinc-900 dark:text-white tracking-tight">Agenda Médica</h2>
            <p class="text-zinc-500">Horarios inteligentes por especialista.</p>
        </div>
        <button onclick="document.getElementById('modal-appt').showModal()"
                class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-xl font-bold shadow-lg transition-all active:scale-95">
            + Agendar Cita
        </button>
    </div>

    <div class="space-y-4">
        @forelse($this->sortedAppointments as $appt)
            <div wire:key="{{ $appt['id'] }}"
                 class="bg-white dark:bg-zinc-900 border border-zinc-100 dark:border-zinc-800 p-5 rounded-3xl flex items-center justify-between shadow-sm relative overflow-hidden group">

                <div class="flex items-center gap-6">
                    <div class="bg-blue-50 dark:bg-blue-900/20 p-3 rounded-2xl text-center min-w-[80px]">
                        <span
                            class="block text-xs font-bold text-blue-600 uppercase">{{ Carbon::parse($appt['date'])->translatedFormat('M') }}</span>
                        <span
                            class="block text-2xl font-black text-blue-700 dark:text-blue-400">{{ Carbon::parse($appt['date'])->format('d') }}</span>
                    </div>

                    <div>
                        <h4 class="font-black text-zinc-900 dark:text-white text-lg leading-tight">{{ $appt['pet_name'] }}</h4>
                        <div class="flex items-center gap-3 mt-1">
                            <span
                                class="text-[10px] font-black uppercase px-2 py-0.5 rounded-md bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-400 border border-zinc-200 dark:border-zinc-700">
                                {{ $appt['reason'] }}
                            </span>
                            <span class="text-sm text-zinc-500 flex items-center gap-1">🕒 {{ $appt['time'] }}</span>
                            <span
                                class="text-xs font-bold text-blue-600 bg-blue-50 dark:bg-blue-900/30 px-2 py-0.5 rounded-lg border border-blue-100 dark:border-blue-800">
                                🩺 {{ $appt['vet_name'] ?? 'General' }}
                            </span>
                        </div>
                    </div>
                </div>

                <button @click="confirmingCancel = '{{ $appt['id'] }}'"
                        class="p-2 text-zinc-300 hover:text-red-500 transition">
                    ✕
                </button>

                <div x-show="confirmingCancel === '{{ $appt['id'] }}'" x-transition
                     class="absolute inset-0 bg-white/95 dark:bg-zinc-900/95 flex flex-col items-center justify-center p-4 text-center z-10 backdrop-blur-sm">
                    <p class="text-sm font-bold text-zinc-900 dark:text-white mb-3">¿Deseas cancelar esta cita?</p>
                    <div class="flex gap-3">
                        <button @click="confirmingCancel = null"
                                class="px-4 py-1.5 text-xs font-bold text-zinc-600 bg-zinc-100 rounded-xl hover:bg-zinc-200 transition">
                            Mantener
                        </button>
                        <button wire:click="deleteAppointment('{{ $appt['id'] }}')" @click="confirmingCancel = null"
                                class="px-4 py-1.5 text-xs font-bold text-white bg-red-600 rounded-xl hover:bg-red-700 shadow-md transition">
                            Sí, cancelar
                        </button>
                    </div>
                </div>
            </div>
        @empty
            <div
                class="text-center py-20 bg-zinc-50 dark:bg-zinc-900/50 rounded-3xl border-2 border-dashed border-zinc-200 dark:border-zinc-800">
                <p class="text-zinc-500 italic">No tienes citas próximas en tu agenda.</p>
            </div>
        @endforelse
    </div>

    <dialog wire:ignore.self id="modal-appt"
            x-on:close-modal-appt.window="document.getElementById('modal-appt').close()"
            class="modal p-0 rounded-3xl shadow-2xl backdrop:bg-zinc-900/60">
        <div class="bg-white dark:bg-zinc-800 w-full max-w-md p-8">

            <h3 class="text-2xl font-bold mb-6 dark:text-white">Agendar Nueva Cita</h3>

            <form wire:submit="saveAppointment" class="space-y-4">
                <flux:select wire:model.live="vet_id" label="Médico Especialista">
                    <option value="">Selecciona un médico...</option>
                    @foreach($vets as $v)
                        <option value="{{ $v['id'] }}">{{ $v['name'] }} ({{ $v['specialty'] }})</option>
                    @endforeach
                </flux:select>

                <flux:select wire:model="pet_id" label="Mascota">
                    <option value="">Selecciona la mascota...</option>
                    @foreach($myPets as $id => $pet)
                        <option value="{{ $id }}">{{ $pet['Nombre'] }}</option>
                    @endforeach
                </flux:select>

                <flux:input wire:model.change="date" type="date" label="Fecha" class="cursor-pointer" />

                <div
                    class="bg-blue-50/50 dark:bg-blue-900/10 p-4 rounded-2xl border border-blue-50 dark:border-blue-900/20">
                    @if($vet_id && $date)
                        <flux:select wire:model="time" label="Horarios Disponibles para esta fecha">
                            <option value="">Selecciona una hora...</option>
                            @forelse($this->availableSlots as $slot)
                                <option value="{{ $slot }}">{{ $slot }} hrs</option>
                            @empty
                                <option value="" disabled>No hay cupos disponibles</option>
                            @endforelse
                        </flux:select>
                    @else
                        <p class="text-xs text-blue-600/70 dark:text-blue-400 text-center font-medium italic">Selecciona
                            médico y fecha para ver disponibilidad</p>
                    @endif
                </div>

                <flux:select wire:model.live="reason" label="Motivo de consulta">
                    <option value="">Selecciona...</option>
                    <option value="Control">Control General</option>
                    <option value="Vacuna">Vacunación</option>
                    <option value="Urgencia">Urgencia</option>
                </flux:select>
                @if($reason === 'Vacuna')
                    <flux:input wire:model="due_date" type="date" label="Fecha de Vencimiento/Refuerzo"
                                help="El sistema te avisará cuando esta fecha se aproxime."/>
                @endif

                <div class="flex gap-3 pt-4">
                    <flux:button type="button" onclick="document.getElementById('modal-appt').close()" variant="ghost"
                                 class="flex-1">Cerrar
                    </flux:button>
                    <flux:button type="submit" variant="primary" class="flex-1 !bg-blue-600" :disabled="!$time">Agendar
                        Ahora
                    </flux:button>
                </div>
            </form>
        </div>
    </dialog>
</div>
