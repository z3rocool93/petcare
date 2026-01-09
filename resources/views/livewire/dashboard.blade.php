<?php

use function Livewire\Volt\{state, mount, layout};
use Kreait\Firebase\Contract\Database;
use Illuminate\Support\Str;
use Carbon\Carbon;

layout('components.layouts.app');

state([
    'totalMascotas' => 0,
    'perrosCount' => 0,
    'gatosCount' => 0,
    'recentPets' => [],
    'citaHoy' => null,
    'lastAttended' => null,
    'userSub' => null,
    'diasParaVencer' => null,
    'recordatoriosMañana' => []
]);

mount(function (Database $database) {
    $uid = auth()->id();
    $hoy = Carbon::today()->format('Y-m-d');
    $mañana = Carbon::tomorrow()->format('Y-m-d');

    // Función para limpiar profundamente datos de Firebase (stdClass -> Array)
    $clean = fn($data) => json_decode(json_encode($data), true);

    // 1. CARGAR Y LIMPIAR DATOS
    $pets = $clean($database->getReference("users/$uid/pets")->getValue() ?? []);
    $appts = $clean($database->getReference("users/$uid/appointments")->getValue() ?? []);
    $this->userSub = $clean($database->getReference("user_subscriptions/$uid")->getValue());

    // 2. PROCESAR ESTADÍSTICAS
    $this->totalMascotas = count($pets);
    $collection = collect($pets);
    $this->perrosCount = (int)$collection->where('Especie', 'Perro')->count();
    $this->gatosCount = (int)$collection->where('Especie', 'Gato')->count();
    $this->recentPets = $collection->take(-3)->reverse()->toArray();

    // 3. LÓGICA DE CITAS (Aseguramos que sean arrays limpios)
    $citaRaw = collect($appts)->where('date', $hoy)->first();
    $this->citaHoy = $citaRaw ? (array)$citaRaw : null;

    $lastRaw = collect($appts)
        ->map(fn($item, $key) => array_merge($item, ['id' => $key]))
        ->where('date', '<=', $hoy)
        ->sortByDesc('date')
        ->first();
    $this->lastAttended = $lastRaw ? (array)$lastRaw : null;

    // 4. SIMULACIÓN RENOVACIÓN (RF14/RF15)
    if ($this->userSub && ($this->userSub['auto_renew'] ?? false)) {
        $fechaFin = Carbon::parse($this->userSub['end_date']);
        if (now()->greaterThan($fechaFin)) {
            if (rand(1, 100) > 10) {
                $nuevaFechaFin = now()->addMonth()->toDateTimeString();
                $database->getReference("user_subscriptions/$uid")->update([
                    'start_date' => now()->toDateTimeString(),
                    'end_date' => $nuevaFechaFin
                ]);
                $this->userSub['end_date'] = $nuevaFechaFin;
                $this->dispatch('notify', 'Suscripción renovada automáticamente.');
            } else {
                $this->dispatch('notify', 'Pago automático fallido. Reintentando...');
            }
        }
    }

    // 5. RF1: RECORDATORIOS MAÑANA
    $this->recordatoriosMañana = collect($appts)
        ->filter(fn($item) => ($item['date'] === $mañana) || (($item['due_date'] ?? '') === $mañana))
        ->values()
        ->toArray();

    if (count($this->recordatoriosMañana) > 0) {
        // Si es solo uno, decimos el nombre. Si son varios, damos el total.
        $mensaje = count($this->recordatoriosMañana) === 1
            ? "Mañana tienes una cita con " . $this->recordatoriosMañana[0]['pet_name']
            : "Tienes " . count($this->recordatoriosMañana) . " citas programadas para mañana.";

        $this->dispatch('trigger-push', [
            'title' => '¡Recordatorio PetCare! 🐾',
            'body' => $mensaje
        ]);
    }

    // 6. RF16: DÍAS PARA VENCER
    if ($this->userSub && isset($this->userSub['end_date'])) {
        $this->diasParaVencer = (int)now()->diffInDays(Carbon::parse($this->userSub['end_date']), false);
    }
});
?>

<div class="max-w-7xl mx-auto p-6">

    {{-- RF16: AVISO VENCIMIENTO --}}
    @if($diasParaVencer !== null && $diasParaVencer <= 5 && $diasParaVencer > 0)
        <div class="mb-6 bg-amber-50 border border-amber-200 p-4 rounded-2xl flex items-center justify-between shadow-sm">
            <div class="flex items-center gap-3">
                <span class="text-xl">💳</span>
                <p class="text-sm text-amber-800 font-medium">
                    Tu suscripción <b>{{ $userSub['plan_name'] }}</b> vence en <b>{{ $diasParaVencer }} días</b>.
                    @if($userSub['auto_renew']) Se cobrará automáticamente. @else Recuerda renovar. @endif
                </p>
            </div>
            <a href="{{ route('membership.index') }}" wire:navigate class="text-amber-900 font-bold text-xs underline">Ver Planes</a>
        </div>
    @endif

    <div class="flex flex-col gap-3 mb-8">
        {{-- ALERTA HOY --}}
        @if($citaHoy)
            <div class="bg-orange-50 border border-orange-200 p-4 rounded-2xl flex items-center justify-between shadow-sm animate-pulse">
                <div class="flex items-center gap-4">
                    <div class="bg-orange-500 p-2 rounded-xl text-white">
                        <flux:icon.clock variant="micro" />
                    </div>
                    <div>
                        <p class="text-orange-800 font-bold text-sm">¡Tienes una cita hoy!</p>
                        <p class="text-orange-700 text-xs"><b>{{ $citaHoy['pet_name'] }}</b> a las {{ $citaHoy['time'] }}</p>
                    </div>
                </div>
                <a href="/agenda" class="text-orange-800 font-bold text-xs underline">Ver ahora</a>
            </div>
        @endif

        {{-- ALERTA MAÑANA (RF1) --}}
        @if(count($recordatoriosMañana) > 0)
            <div class="bg-blue-50 border border-blue-200 p-4 rounded-2xl flex items-center justify-between shadow-sm">
                <div class="flex items-center gap-4">
                    <div class="bg-blue-600 p-2 rounded-xl text-white">
                        <flux:icon.bell-alert variant="micro" />
                    </div>
                    <div>
                        <p class="text-blue-800 font-bold text-sm">Recordatorio para mañana</p>
                        <p class="text-blue-700 text-xs">Tienes {{ count($recordatoriosMañana) }} evento(s) programado(s).</p>
                    </div>
                </div>
                <a href="/agenda" class="text-blue-800 font-bold text-xs underline">Preparar cita</a>
            </div>
        @endif
    </div>

    {{-- ÚLTIMA ATENCIÓN --}}
    @if($lastAttended)
        <div class="mb-8 bg-zinc-900 dark:bg-zinc-800 text-white p-6 rounded-[2rem] flex flex-col md:flex-row items-center justify-between shadow-2xl relative overflow-hidden group">
            <div class="absolute -right-10 -top-10 text-9xl opacity-10 rotate-12 group-hover:rotate-0 transition-transform">📋</div>
            <div class="flex items-center gap-6 relative z-10">
                <div class="w-16 h-16 bg-white/10 rounded-2xl flex items-center justify-center text-3xl backdrop-blur-md border border-white/20">
                    {{ ($lastAttended['pet_species'] ?? '') === 'Gato' ? '🐱' : '🐶' }}
                </div>
                <div>
                    <span class="text-blue-400 text-xs font-black uppercase tracking-widest">Última Atención</span>
                    <h3 class="text-2xl font-black">{{ $lastAttended['pet_name'] }}</h3>
                    <p class="text-zinc-400 text-sm">Atendido el {{ Carbon::parse($lastAttended['date'])->translatedFormat('d \d\e F') }}</p>
                </div>
            </div>
            <div class="mt-6 md:mt-0 relative z-10">
                <a href="{{ route('pets.history', $lastAttended['pet_id']) }}" wire:navigate class="bg-blue-600 hover:bg-blue-500 text-white px-6 py-3 rounded-2xl font-bold transition-all shadow-lg">
                    Ver Historial Completo
                </a>
            </div>
        </div>
    @endif

    {{-- HEADER BIENVENIDA --}}
    {{-- HEADER DEL DASHBOARD CON INTEGRACIÓN DE PERFIL (RF4) Y PLAN (RF9) --}}
    <div class="mb-8 flex flex-col md:flex-row justify-between items-center md:items-end gap-6">
        <div class="flex items-center gap-5">
            {{-- AVATAR CIRCULAR DINÁMICO --}}
            <div class="h-16 w-16 md:h-20 md:w-20 rounded-[1.5rem] md:rounded-[2rem] overflow-hidden border-4 border-white dark:border-zinc-800 shadow-xl shadow-blue-500/10 flex-shrink-0">
                @if (auth()->user()->profile_photo_path)
                    {{-- Foto real desde el almacenamiento local --}}
                    <img src="{{ Storage::disk('public')->url(auth()->user()->profile_photo_path) }}" class="h-full w-full object-cover">
                @else
                    {{-- Fallback: Iniciales desde el modelo User --}}
                    @php
                        $colors = ['bg-blue-600', 'bg-purple-600', 'bg-orange-500', 'bg-emerald-600'];
                        $color = $colors[ord(strtoupper(substr(auth()->user()->name, 0, 1))) % count($colors)];
                    @endphp
                    <div class="h-full w-full {{ $color }} flex items-center justify-center text-2xl md:text-3xl font-black text-white uppercase">
                        {{ auth()->user()->initials() }}
                    </div>
                @endif
            </div>

            <div>
                <h1 class="text-2xl md:text-3xl font-black text-zinc-900 dark:text-white uppercase tracking-tight">
                    ¡Hola, {{ explode(' ', auth()->user()->name)[0] }}! 👋
                </h1>
                <p class="text-zinc-500 dark:text-zinc-400 text-sm md:text-base">Bienvenido de vuelta a PetCare.</p>
            </div>
        </div>

        {{-- BADGE DE PLAN (RF9) --}}
        <div class="text-center md:text-right bg-white dark:bg-zinc-900 p-3 px-6 rounded-2xl border border-zinc-100 dark:border-zinc-800 shadow-sm">
            <p class="text-[10px] font-black text-zinc-400 uppercase tracking-widest mb-1">Estatus de Cuenta</p>
            <div class="flex items-center gap-2 justify-center md:justify-end">
            <span class="relative flex h-2 w-2">
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-blue-400 opacity-75"></span>
                <span class="relative inline-flex rounded-full h-2 w-2 bg-blue-500"></span>
            </span>
                <span class="text-blue-700 dark:text-blue-400 text-xs font-black uppercase tracking-tighter">
                Plan {{ $userSub['plan_name'] ?? 'Básico' }}
            </span>
            </div>
        </div>
    </div>

    {{-- ESTADÍSTICAS --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-12">
        <div class="bg-blue-600 rounded-3xl p-6 text-white shadow-xl shadow-blue-500/20">
            <p class="text-blue-100 text-xs font-bold uppercase tracking-wider">Total Mascotas</p>
            <h3 class="text-5xl font-black mt-2">{{ $totalMascotas }}</h3>
            <a href="/mascotas" wire:navigate class="inline-flex items-center mt-6 text-xs font-bold bg-white/20 px-4 py-2 rounded-xl">Gestionar todas →</a>
        </div>
        <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-3xl p-6 shadow-sm">
            <p class="text-zinc-500 text-xs font-bold uppercase mb-4">Perros: {{ $perrosCount }}</p>
            <div class="w-full bg-zinc-100 dark:bg-zinc-800 h-2 rounded-full">
                <div class="bg-orange-500 h-full rounded-full" style="width: {{ $totalMascotas > 0 ? ($perrosCount/$totalMascotas)*100 : 0 }}%"></div>
            </div>
        </div>
        <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-3xl p-6 shadow-sm">
            <p class="text-zinc-500 text-xs font-bold uppercase mb-4">Gatos: {{ $gatosCount }}</p>
            <div class="w-full bg-zinc-100 dark:bg-zinc-800 h-2 rounded-full">
                <div class="bg-purple-500 h-full rounded-full" style="width: {{ $totalMascotas > 0 ? ($gatosCount/$totalMascotas)*100 : 0 }}%"></div>
            </div>
        </div>
    </div>

    {{-- LISTA RECIENTE --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <div class="lg:col-span-2">
            <h4 class="text-xl font-bold mb-4 dark:text-white">Últimas incorporaciones</h4>
            <div class="bg-white dark:bg-zinc-900 border border-zinc-100 dark:border-zinc-800 rounded-3xl overflow-hidden shadow-sm">
                <div class="divide-y divide-zinc-100 dark:divide-zinc-800">
                    @forelse($recentPets as $id => $pet)
                        <div class="p-4 flex items-center justify-between hover:bg-zinc-50 transition">
                            <div class="flex items-center gap-4">
                                <span class="text-2xl">{{ ($pet['Especie'] ?? '') === 'Perro' ? '🐶' : '🐱' }}</span>
                                <div>
                                    <p class="font-bold text-zinc-900 dark:text-white">{{ $pet['Nombre'] }}</p>
                                    <p class="text-xs text-zinc-500">{{ $pet['Raza'] ?? 'Mestizo' }}</p>
                                </div>
                            </div>
                            <a href="{{ route('pets.history', $id) }}" wire:navigate class="text-blue-600 font-bold text-sm">Ver Ficha</a>
                        </div>
                    @empty
                        <div class="p-10 text-center text-zinc-400 italic">No hay mascotas aún.</div>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="space-y-6">
            <h4 class="text-xl font-bold mb-4 dark:text-white">Acciones rápidas</h4>
            <div class="grid gap-3">
                <flux:button href="/mascotas" icon="plus" variant="filled" class="!justify-start rounded-2xl">Registrar Mascota</flux:button>
                <flux:button href="/foro" icon="chat-bubble-left-right" variant="ghost" class="!justify-start rounded-2xl">Ir al Foro</flux:button>
                <flux:button href="/membresias" icon="credit-card" variant="ghost" class="!justify-start rounded-2xl">Cambiar Plan</flux:button>
            </div>
        </div>
    </div>

    {{-- NOTIFICACIONES TOAST --}}
    <div x-data="{ show: false, message: '' }"
         x-on:notify.window="show = true; message = $event.detail; setTimeout(() => show = false, 4000)"
         x-show="show" x-transition
         class="fixed bottom-5 right-5 bg-zinc-900 text-white px-6 py-3 rounded-2xl shadow-2xl z-50 flex items-center gap-3">
        <span class="text-blue-400">●</span>
        <span x-text="message" class="text-sm font-bold"></span>
    </div>

    @script
    <script>
        if (Notification.permission === "default") {
            Notification.requestPermission();
        }

        $wire.on('trigger-push', (data) => {
            const payload = data[0];
            if (Notification.permission === "granted") {
                new Notification(payload.title, {
                    body: payload.body,
                    icon: '/logo-petcare.png'
                });
            }
        });
    </script>
    @endscript
</div>
