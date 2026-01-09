<?php

use function Livewire\Volt\{state, mount, layout, computed};
use Kreait\Firebase\Contract\Database;
use Carbon\Carbon;

layout('components.layouts.app');

state([
    'pets' => [],
    'consultations' => [],
    // Campos del Formulario (RF2)
    'pet_id' => '',
    'subject' => '',
    'phone' => '',
    'email' => '',
    'message' => '', // (RF1)
    'showForm' => false
]);

mount(function (Database $database) {
    $uid = auth()->id();
    $user = auth()->user(); // Asumiendo que usas el auth de Laravel o Firebase

    // Precargar datos de contacto del usuario (RF2)
    $this->phone = $user->phone ?? '';
    $this->email = $user->email ?? '';

    // Cargar Mascotas para el select
    $this->pets = $database->getReference("users/$uid/pets")->getValue() ?? [];

    // Cargar Consultas existentes
    $this->cargarConsultas($database);
});

$cargarConsultas = function (Database $database) {
    $uid = auth()->id();
    $data = $database->getReference("users/$uid/consultations")->getValue() ?? [];

    // Ordenar por fecha (más reciente primero)
    $this->consultations = collect($data)
        ->map(fn($item, $key) => array_merge($item, ['id' => $key]))
        ->sortByDesc('created_at')
        ->values()
        ->toArray();
};

$save = function (Database $database) {
    // Validación básica
    if (empty($this->pet_id) || empty($this->message) || empty($this->subject)) {
        return; // Podrías agregar notificaciones aquí
    }

    $uid = auth()->id();
    $petName = $this->pets[$this->pet_id]['Nombre'] ?? 'Mascota';

    // (RF2) Guardamos todos los datos requeridos
    $newConsultation = [
        'user_name' => auth()->user()->name ?? 'Usuario',
        'pet_id' => $this->pet_id,
        'pet_name' => $petName,
        'date' => now()->format('Y-m-d H:i:s'),
        'subject' => $this->subject,
        'email' => $this->email,
        'phone' => $this->phone,
        'message' => $this->message, // (RF1)
        'status' => 'pending', // Estado inicial
        'response' => null,     // Aún sin respuesta
        'created_at' => time()
    ];

    // Guardar en Firebase
    $database->getReference("users/$uid/consultations")->push($newConsultation);

    // Resetear formulario
    $this->reset(['pet_id', 'subject', 'message', 'showForm']);
    $this->cargarConsultas($database);
};

// (RF3 - Simulación) Esta función actúa como el "Veterinario"
$simularRespuesta = function (Database $database, $id) {
    $uid = auth()->id();

    // Banco de respuestas simuladas para que parezca real
    $respuestas = [
        "Estimado usuario, según los síntomas descritos, parece ser un cuadro leve. Recomendamos hidratación y reposo. Si persiste por 24hrs, acuda a urgencias.",
        "Gracias por contactarnos. Es normal en esta raza. Le sugerimos cambiar el alimento por uno hipoalergénico y observar la evolución.",
        "Hola. Hemos revisado su caso. Por favor agende una hora presencial lo antes posible para realizar exámenes de sangre.",
        "No se preocupe, es una reacción común a la vacuna. Aplique compresas frías en la zona y monitoree la temperatura."
    ];

    $respuestaRandom = $respuestas[array_rand($respuestas)];

    // Actualizamos el registro en Firebase
    $database->getReference("users/$uid/consultations/$id")->update([
        'status' => 'answered',
        'response' => $respuestaRandom,
        'vet_name' => 'Dr. Simulación (IA)',
        'response_date' => now()->format('Y-m-d H:i')
    ]);

    $this->cargarConsultas($database);
};

?>

<div class="p-6 max-w-4xl mx-auto">

    <div class="flex justify-between items-center mb-8">
        <div>
            <h1 class="text-3xl font-black text-zinc-900 dark:text-white">Consultas Veterinarias</h1>
            <p class="text-zinc-500">Contacta con nuestro equipo de expertos (RF1)</p>
        </div>
        <button wire:click="$toggle('showForm')" class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2.5 rounded-xl font-bold transition shadow-lg flex items-center gap-2">
            <span class="text-xl">+</span> Nueva Consulta
        </button>
    </div>

    {{-- FORMULARIO DE NUEVA CONSULTA (RF1 y RF2) --}}
    @if($showForm)
        <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 p-6 rounded-3xl shadow-xl mb-10 animate-in fade-in slide-in-from-top-4">
            <h3 class="text-lg font-bold mb-4 text-zinc-900 dark:text-white">Registrar Consulta (RF2)</h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                {{-- Selección de Mascota --}}
                <div>
                    <label class="block text-xs font-bold text-zinc-500 uppercase mb-1">Mascota</label>
                    <select wire:model="pet_id" class="w-full bg-zinc-50 dark:bg-zinc-800 border-none rounded-xl p-3 text-sm">
                        <option value="">Seleccionar...</option>
                        @foreach($pets as $key => $pet)
                            <option value="{{ $key }}">{{ $pet['Nombre'] }}</option>
                        @endforeach
                    </select>
                </div>
                {{-- Motivo --}}
                <div>
                    <label class="block text-xs font-bold text-zinc-500 uppercase mb-1">Motivo</label>
                    <select wire:model="subject" class="w-full bg-zinc-50 dark:bg-zinc-800 border-none rounded-xl p-3 text-sm">
                        <option value="">Seleccionar...</option>
                        <option value="Comportamiento">Comportamiento</option>
                        <option value="Nutrición">Nutrición</option>
                        <option value="Síntomas">Síntomas / Enfermedad</option>
                        <option value="Urgencia">Urgencia (Consulta rápida)</option>
                    </select>
                </div>
                {{-- Datos de Contacto (RF2) --}}
                <div>
                    <label class="block text-xs font-bold text-zinc-500 uppercase mb-1">Teléfono</label>
                    <input type="text" wire:model="phone" class="w-full bg-zinc-50 dark:bg-zinc-800 border-none rounded-xl p-3 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-bold text-zinc-500 uppercase mb-1">Email</label>
                    <input type="email" wire:model="email" class="w-full bg-zinc-50 dark:bg-zinc-800 border-none rounded-xl p-3 text-sm">
                </div>
            </div>

            <div class="mb-6">
                <label class="block text-xs font-bold text-zinc-500 uppercase mb-1">Tu Pregunta (RF1)</label>
                <textarea wire:model="message" rows="4" class="w-full bg-zinc-50 dark:bg-zinc-800 border-none rounded-xl p-3 text-sm" placeholder="Describe detalladamente el problema..."></textarea>
            </div>

            <div class="flex justify-end gap-3">
                <button wire:click="$toggle('showForm')" class="px-4 py-2 text-zinc-500 hover:text-zinc-700 font-bold">Cancelar</button>
                <button wire:click="save" class="bg-zinc-900 text-white px-6 py-2 rounded-xl font-bold hover:bg-zinc-800 transition">Enviar Consulta</button>
            </div>
        </div>
    @endif

    {{-- LISTADO DE CONSULTAS (RF3) --}}
    <div class="space-y-6">
        @forelse($consultations as $consult)
            <div class="bg-white dark:bg-zinc-900 border border-zinc-100 dark:border-zinc-800 p-6 rounded-3xl shadow-sm relative overflow-hidden group">

                {{-- Badge de Estado --}}
                <div class="absolute top-6 right-6">
                    @if($consult['status'] === 'answered')
                        <span class="bg-green-100 text-green-700 px-3 py-1 rounded-full text-xs font-black uppercase tracking-wider">Respondida</span>
                    @else
                        <span class="bg-amber-100 text-amber-700 px-3 py-1 rounded-full text-xs font-black uppercase tracking-wider animate-pulse">Pendiente</span>
                    @endif
                </div>

                {{-- Cabecera de la Consulta --}}
                <div class="flex items-center gap-3 mb-4">
                    <div class="h-10 w-10 bg-blue-100 rounded-full flex items-center justify-center text-blue-600">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/></svg>
                    </div>
                    <div>
                        <h4 class="font-bold text-zinc-900 dark:text-white">{{ $consult['subject'] }}</h4>
                        <p class="text-xs text-zinc-500">{{ $consult['pet_name'] }} • {{ Carbon::parse($consult['date'])->diffForHumans() }}</p>
                    </div>
                </div>

                {{-- Pregunta del Usuario --}}
                <div class="bg-zinc-50 dark:bg-zinc-800/50 p-4 rounded-2xl mb-4">
                    <p class="text-sm text-zinc-600 dark:text-zinc-300 italic">"{{ $consult['message'] }}"</p>
                </div>

                {{-- (RF3) Visualización de Respuesta --}}
                @if($consult['status'] === 'answered' && isset($consult['response']))
                    <div class="border-t border-zinc-100 dark:border-zinc-800 pt-4 mt-4">
                        <div class="flex items-start gap-3">
                            <div class="mt-1 h-8 w-8 bg-green-100 rounded-full flex items-center justify-center text-green-600 shrink-0">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="m9 12 2 2 4-4"/></svg>
                            </div>
                            <div>
                                <p class="text-xs font-bold text-green-700 uppercase mb-1">Respuesta del Especialista</p>
                                <p class="text-sm text-zinc-700 dark:text-zinc-300">{{ $consult['response'] }}</p>
                                <p class="text-[10px] text-zinc-400 mt-2">{{ $consult['vet_name'] ?? 'Veterinario' }} • {{ $consult['response_date'] ?? '' }}</p>
                            </div>
                        </div>
                    </div>
                @else
                    {{-- BOTÓN DE SIMULACIÓN (Solo visible para demo académica) --}}
                    <div class="mt-4 flex justify-end">
                        <button wire:click="simularRespuesta('{{ $consult['id'] }}')"
                                class="text-xs bg-zinc-800 text-zinc-200 px-3 py-1.5 rounded-lg hover:bg-zinc-700 transition opacity-0 group-hover:opacity-100">
                            ⚡ Demo: Simular Respuesta
                        </button>
                    </div>
                @endif
            </div>
        @empty
            <div class="text-center py-20">
                <div class="bg-zinc-100 dark:bg-zinc-800 h-16 w-16 rounded-full flex items-center justify-center mx-auto mb-4 text-zinc-400">
                    <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><path d="M12 17h.01"/></svg>
                </div>
                <h3 class="text-lg font-bold text-zinc-900 dark:text-white">Sin consultas</h3>
                <p class="text-zinc-500 text-sm">Envía una pregunta y nuestro equipo te responderá.</p>
            </div>
        @endforelse
    </div>
</div>
