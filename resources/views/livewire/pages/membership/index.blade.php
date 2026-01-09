<?php

use function Livewire\Volt\{state, mount};
use Kreait\Firebase\Contract\Database;
use Illuminate\Support\Str; // Necesario para Str::random()
use Carbon\Carbon;

state([
    'plans' => [],
    'userSubscription' => null,
    'receipts' => [], // RF13: Estado para el historial de pagos
    'processing' => false,
    'selectedReceipt' => null
]);

mount(function (Database $database) {
    $uid = auth()->id();

    // RF1, RF2, RF3: Cargar configuración de planes
    $this->plans = $database->getReference('membership_plans')->getValue() ?? [];

    // RF9: Cargar estado de suscripción actual
    $this->userSubscription = $database->getReference("user_subscriptions/$uid")->getValue();

    // RF13: Cargar historial de recibos
    $this->receipts = $database->getReference("user_receipts/$uid")->getValue() ?? [];
});
// ACCIÓN: Ver Detalle de Recibo (RF13)
$viewReceipt = function ($id) {
    $this->selectedReceipt = $this->receipts[$id];
    $this->dispatch('open-modal-receipt');
};

// RF6: Proceso de Suscripción (Simulado para RF12)
$subscribe = function (Database $database, $planId) {
    $this->processing = true;

    // Simulación de latencia de pasarela de pago (RF12)
    sleep(1);

    $plan = $this->plans[$planId];
    $uid = auth()->id();

    // Datos de la suscripción
    $subscriptionData = [
        'plan_id' => $planId,
        'plan_name' => $plan['name'],
        'status' => 'active',
        'price' => $plan['price'],
        'start_date' => now()->toDateTimeString(),
        'end_date' => now()->addMonth()->toDateTimeString(), // RF3: Vigencia mensual
        'auto_renew' => true, // RF14: Renovación automática por defecto
    ];

    // RF13: Crear el objeto del Recibo/Factura
    $receipt = [
        'date' => now()->toDateTimeString(),
        'plan' => $plan['name'],
        'amount' => $plan['price'],
        'transaction_id' => 'PET-' . strtoupper(Str::random(10)), // ID de transacción simulado
    ];

    // Guardar en Firebase
    $database->getReference("user_subscriptions/$uid")->set($subscriptionData);
    $database->getReference("user_receipts/$uid")->push($receipt);

    // Actualizar estado local para feedback inmediato
    $this->userSubscription = $subscriptionData;
    // Recargamos los recibos para que aparezcan en la tabla
    $this->receipts = $database->getReference("user_receipts/$uid")->getValue() ?? [];

    $this->processing = false;

    // RF16: Notificación de éxito
    $this->js("Swal.fire('¡Éxito!', 'Te has suscrito a {$plan['name']}. El recibo ha sido generado.', 'success')");
};

// RF8: Cancelación de renovación (Mantiene acceso hasta el final del periodo)
$cancelSubscription = function (Database $database) {
    $uid = auth()->id();

    $database->getReference("user_subscriptions/$uid")->update(['auto_renew' => false]);
    $this->userSubscription['auto_renew'] = false;

    $this->js("Swal.fire('Actualizado', 'La renovación automática ha sido desactivada. Seguirás siendo Premium hasta el final de tu periodo.', 'info')");
};

?>

<div class="p-6 max-w-6xl mx-auto min-h-screen">
    @assets
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.25/jspdf.plugin.autotable.min.js"></script>
    @endassets
    {{-- Encabezado --}}
    <div class="mb-12 text-center">
        <h1 class="text-4xl font-black text-zinc-900 dark:text-white tracking-tight">Membresías <span class="text-blue-600">PetCare</span></h1>
        <p class="text-zinc-500 mt-2">Gestiona tus beneficios y servicios exclusivos para tus mascotas</p>
    </div>

    {{-- RF9: Estado Actual de la Suscripción --}}
    @if($userSubscription)
        <div class="mb-12 bg-white dark:bg-zinc-900 border border-blue-100 dark:border-blue-900/30 rounded-[2rem] p-8 shadow-xl shadow-blue-500/5 flex flex-col md:flex-row justify-between items-center gap-6">
            <div class="flex items-center gap-5">
                <div class="h-14 w-14 bg-blue-600 rounded-2xl flex items-center justify-center text-white shadow-lg shadow-blue-500/40">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" /></svg>
                </div>
                <div>
                    <h2 class="text-xs font-bold text-blue-600 uppercase tracking-widest">Suscripción Activa</h2>
                    <p class="text-2xl font-black dark:text-white">{{ $userSubscription['plan_name'] }}</p>
                    <p class="text-zinc-500 text-sm">Vence el: {{ Carbon::parse($userSubscription['end_date'])->format('d/m/Y') }}</p>
                </div>
            </div>

            @if($userSubscription['auto_renew'])
                <button wire:click="cancelSubscription" class="px-6 py-3 rounded-xl bg-red-50 text-red-600 font-bold text-sm hover:bg-red-100 transition">
                    Cancelar renovación automática
                </button>
            @else
                <span class="px-4 py-2 bg-zinc-100 text-zinc-500 rounded-xl text-sm font-bold italic">Renovación desactivada</span>
            @endif
        </div>
    @endif

    {{-- RF5: Grid de Planes Disponibles --}}
    <div class="grid md:grid-cols-3 gap-8">
        @foreach($plans as $id => $plan)
            <div class="bg-white dark:bg-zinc-900 border {{ ($userSubscription['plan_id'] ?? '') == $id ? 'border-blue-500 ring-4 ring-blue-500/10' : 'border-zinc-100 dark:border-zinc-800' }} rounded-[2.5rem] p-8 flex flex-col relative transition hover:shadow-2xl">

                @if(($userSubscription['plan_id'] ?? '') == $id)
                    <div class="absolute top-0 right-0 bg-blue-500 text-white px-5 py-1.5 rounded-bl-2xl font-bold text-xs uppercase tracking-tighter">Tu Plan</div>
                @endif

                <h3 class="text-2xl font-black mb-1 dark:text-white">{{ $plan['name'] }}</h3>
                <div class="flex items-baseline gap-1 mb-8">
                    <span class="text-4xl font-black dark:text-white">${{ number_format($plan['price'], 0, ',', '.') }}</span>
                    <span class="text-zinc-400 text-sm">/ mes</span>
                </div>

                {{-- RF2: Servicios y Funcionalidades --}}
                <ul class="space-y-4 mb-10 flex-1">
                    @foreach($plan['features'] as $feature)
                        <li class="flex items-start gap-3 text-zinc-600 dark:text-zinc-400 text-sm leading-tight">
                            <div class="mt-0.5 h-5 w-5 rounded-full bg-green-100 dark:bg-green-900/30 flex items-center justify-center flex-shrink-0">
                                <svg class="h-3 w-3 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                            </div>
                            {{ $feature }}
                        </li>
                    @endforeach
                </ul>

                <button
                    wire:click="subscribe('{{ $id }}')"
                    wire:loading.attr="disabled"
                    @disabled(($userSubscription['plan_id'] ?? '') == $id)
                    class="w-full py-4 rounded-2xl font-bold text-lg shadow-lg transition transform active:scale-95
                        {{ ($userSubscription['plan_id'] ?? '') == $id
                            ? 'bg-zinc-100 text-zinc-400 cursor-not-allowed'
                            : 'bg-zinc-900 dark:bg-white dark:text-zinc-900 text-white hover:bg-blue-600 hover:text-white' }}"
                >
                    <span wire:loading.remove wire:target="subscribe('{{ $id }}')">
                        {{ ($userSubscription['plan_id'] ?? '') == $id ? 'Plan Actual' : 'Seleccionar Plan' }}
                    </span>
                    <span wire:loading wire:target="subscribe('{{ $id }}')">Procesando...</span>
                </button>
            </div>
        @endforeach
    </div>

    {{-- RF13: Historial de Pagos --}}
    <div class="mt-24">
        <div class="flex items-center gap-3 mb-6">
            <h3 class="text-2xl font-black dark:text-white tracking-tight">Historial de Pagos</h3>
            <span class="px-2.5 py-0.5 bg-zinc-100 dark:bg-zinc-800 text-zinc-500 rounded-lg text-xs font-bold uppercase tracking-widest">RF13</span>
        </div>

        <div class="bg-white dark:bg-zinc-900 border border-zinc-100 dark:border-zinc-800 rounded-[2rem] overflow-hidden shadow-sm">
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead class="bg-zinc-50 dark:bg-zinc-800/50 text-xs font-bold text-zinc-400 uppercase tracking-widest">
                    <tr>
                        <th class="px-8 py-5">Fecha de Emisión</th>
                        <th class="px-8 py-5">Membresía</th>
                        <th class="px-8 py-5">Monto Cobrado</th>
                        <th class="px-8 py-5">ID Transacción</th>
                    </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                    @forelse($receipts as $key => $receipt)
                        <tr class="text-sm transition hover:bg-zinc-50/50 dark:hover:bg-zinc-800/20">
                            <td class="px-8 py-5 dark:text-zinc-400">{{ \Carbon\Carbon::parse($receipt['date'])->format('d M, Y') }}</td>
                            <td class="px-8 py-5 font-bold dark:text-white">{{ $receipt['plan'] }}</td>
                            <td class="px-8 py-5 font-bold text-zinc-900 dark:text-zinc-100">${{ number_format($receipt['amount'], 0, ',', '.') }}</td>
                            <td class="px-8 py-5 text-right">
                                {{-- CAMBIO AQUÍ: Usamos $key en lugar de $id --}}
                                <button wire:click="viewReceipt('{{ $key }}')" class="text-blue-600 font-bold hover:underline">
                                    Ver Recibo
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-8 py-16 text-center text-zinc-400 italic">
                                No se han encontrado registros de transacciones.
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    {{-- MODAL DE RECIBO DETALLADO (RF13) --}}
    <dialog wire:ignore.self id="modal-receipt"
            x-on:open-modal-receipt.window="$el.showModal()"
            x-on:close-modal-receipt.window="$el.close()"
            class="modal p-0 rounded-[2rem] shadow-2xl backdrop:bg-zinc-900/50">

        @if($selectedReceipt)
            <div class="bg-white dark:bg-zinc-900 w-full max-w-md p-0 overflow-hidden">
                {{-- Cabecera del Recibo --}}
                <div class="bg-zinc-900 p-8 text-white text-center relative">
                    <div class="text-4xl mb-2">🐾</div>
                    <h3 class="text-xl font-black uppercase tracking-widest">Recibo de Pago</h3>
                    <p class="text-zinc-400 text-xs">PetCare Services S.A.</p>
                    <div class="absolute top-4 right-4 text-[10px] font-mono opacity-50">
                        {{ $selectedReceipt['transaction_id'] }}
                    </div>
                </div>

                {{-- Cuerpo del Recibo --}}
                <div class="p-8">
                    <div class="flex justify-between mb-6">
                        <div>
                            <p class="text-[10px] font-bold text-zinc-400 uppercase">Cliente</p>
                            <p class="font-bold dark:text-white">{{ auth()->user()->name }}</p>
                        </div>
                        <div class="text-right">
                            <p class="text-[10px] font-bold text-zinc-400 uppercase">Fecha</p>
                            <p class="font-bold dark:text-white">{{ \Carbon\Carbon::parse($selectedReceipt['date'])->format('d/m/Y') }}</p>
                        </div>
                    </div>

                    <div class="border-t border-dashed border-zinc-200 dark:border-zinc-700 py-4 space-y-3">
                        <div class="flex justify-between items-center">
                            <span class="text-zinc-600 dark:text-zinc-400 text-sm">Suscripción Mensual: <b>{{ $selectedReceipt['plan'] }}</b></span>
                            <span class="font-bold dark:text-white">${{ number_format($selectedReceipt['amount'], 0, ',', '.') }}</span>
                        </div>
                        <div class="flex justify-between items-center text-xs text-zinc-500">
                            <span>Impuestos (Incluidos)</span>
                            <span>$0</span>
                        </div>
                    </div>

                    <div class="border-t-2 border-zinc-900 dark:border-white pt-4 flex justify-between items-center mb-8">
                        <span class="font-black uppercase tracking-tighter text-lg dark:text-white">Total Pagado</span>
                        <span class="text-2xl font-black text-blue-600">${{ number_format($selectedReceipt['amount'], 0, ',', '.') }}</span>
                    </div>

                    {{-- Acciones --}}
                    <div class="grid grid-cols-2 gap-3 no-print">
                        {{-- CAMBIO: Ahora llama a descargarReciboPDF() --}}
                        <button type="button"
                                onclick="descargarReciboPDF()"
                                class="py-3 bg-zinc-900 text-white rounded-xl font-bold text-xs uppercase hover:bg-zinc-800 transition shadow-lg">
                            Descargar PDF
                        </button>

                        <button onclick="document.getElementById('modal-receipt').close()"
                                class="py-3 bg-zinc-100 dark:bg-zinc-800 text-zinc-900 dark:text-white rounded-xl font-bold text-xs uppercase hover:bg-zinc-200 transition">
                            Cerrar
                        </button>
                    </div>
                </div>

                <div class="bg-zinc-50 dark:bg-zinc-800/50 p-4 text-center">
                    <p class="text-[9px] text-zinc-400 uppercase font-medium">Gracias por confiar en PetCare para el cuidado de tus mascotas</p>
                </div>
            </div>
        @endif
    </dialog>

    @script
    <script>
        window.descargarReciboPDF = function () {
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF();

            // Obtenemos los datos del componente Livewire
            const receipt = $wire.selectedReceipt;
            const userName = "{{ auth()->user()->name }}";
            const userEmail = "{{ auth()->user()->email }}";

            // Estilos y Colores (Consistentes con tu reporte clínico)
            const primaryColor = [37, 99, 235]; // Blue 600
            const textColor = [17, 24, 39]; // Gray 900

            // Encabezado
            doc.setFontSize(22);
            doc.setTextColor(primaryColor[0], primaryColor[1], primaryColor[2]);
            doc.setFont('helvetica', 'bold');
            doc.text('RECIBO DE PAGO', 14, 22);

            doc.setFontSize(10);
            doc.setTextColor(107, 114, 128);
            doc.setFont('helvetica', 'normal');
            doc.text('PETCARE MANAGEMENT SYSTEM - COMPROBANTE OFICIAL', 14, 28);

            // Cuadro de Información del Cliente
            doc.setFillColor(249, 250, 251);
            doc.rect(14, 35, 182, 25, 'F');

            doc.setFontSize(12);
            doc.setTextColor(textColor[0], textColor[1], textColor[2]);
            doc.setFont('helvetica', 'bold');
            doc.text('CLIENTE:', 20, 44);

            doc.setFontSize(10);
            doc.setFont('helvetica', 'normal');
            doc.text(userName, 20, 50);
            doc.text(userEmail, 20, 55);

            // Tabla del Detalle del Pago
            const tableRows = [[
                receipt.date,
                receipt.plan.toUpperCase(),
                receipt.transaction_id,
                '$' + new Intl.NumberFormat('es-CL').format(receipt.amount)
            ]];

            doc.autoTable({
                startY: 70,
                head: [['FECHA', 'PLAN ADQUIRIDO', 'TRANSACCIÓN', 'TOTAL']],
                body: tableRows,
                headStyles: {
                    fillColor: primaryColor,
                    fontSize: 9,
                    fontStyle: 'bold',
                    halign: 'center'
                },
                columnStyles: {
                    0: { cellWidth: 40, halign: 'center' },
                    1: { cellWidth: 40, halign: 'center' },
                    2: { cellWidth: 62, halign: 'center' },
                    3: { cellWidth: 40, halign: 'right', fontStyle: 'bold' }
                },
                styles: {
                    fontSize: 9,
                    cellPadding: 6
                }
            });

            // Nota al pie
            doc.setFontSize(8);
            doc.setTextColor(156, 163, 175);
            doc.text(
                'Este documento es un comprobante de pago electrónico generado por PetCare.',
                14,
                doc.lastAutoTable.finalY + 15
            );

            // Descarga Directa
            doc.save('Recibo_PetCare_' + receipt.transaction_id + '.pdf');
        };
    </script>
    @endscript
</div>
