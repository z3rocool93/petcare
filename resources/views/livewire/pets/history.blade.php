<?php

use function Livewire\Volt\{state, mount, layout, computed};
use Kreait\Firebase\Contract\Database;
use Carbon\Carbon;

layout('components.layouts.app');

state([
    'pet' => [],
    'history' => [],
    'petId' => '',
    'filter' => 'todos',
    'sort' => 'desc'
]);

$filteredHistory = computed(function () {
    $collection = collect($this->history)
        ->filter(function ($record) {
            if ($this->filter === 'todos') return true;
            return ($record['reason'] ?? '') === $this->filter;
        });

    return $this->sort === 'desc'
        ? $collection->sortByDesc('date')->values()->toArray()
        : $collection->sortBy('date')->values()->toArray();
});

mount(function (Database $database, $id) {
    $this->petId = $id;
    $uid = auth()->id();
    $this->pet = $database->getReference("users/$uid/pets/$id")->getValue() ?? [];
    if (!$this->pet) return redirect()->route('pets.index');

    $allEvents = $database->getReference("users/$uid/appointments")->getValue() ?? [];
    $this->history = collect($allEvents)
        ->map(fn($item, $key) => array_merge($item, ['id' => $key]))
        ->where('pet_id', $id)
        ->values()
        ->toArray();
});
?>

<main class="p-6 max-w-4xl mx-auto">
    {{-- Scripts para Generación de PDF Directa --}}
    @assets
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.25/jspdf.plugin.autotable.min.js"></script>
    @endassets

    {{-- BARRA DE CONTROL --}}
    <div class="no-print space-y-6 mb-10">
        <div class="flex flex-col md:flex-row justify-between gap-4">
            <div class="flex flex-wrap gap-2">
                @foreach(['todos', 'Control', 'Vacuna', 'Urgencia'] as $f)
                    <button wire:click="$set('filter', '{{ $f }}')"
                            class="px-4 py-2 rounded-xl text-[10px] font-black transition-all {{ $filter === $f ? 'bg-blue-600 text-white shadow-lg' : 'bg-zinc-100 dark:bg-zinc-800 text-zinc-500' }}">
                        {{ strtoupper($f) }}
                    </button>
                @endforeach
            </div>
            <div class="flex items-center gap-2 bg-zinc-100 dark:bg-zinc-800 p-1 rounded-xl">
                <button wire:click="$set('sort', 'desc')"
                        class="px-3 py-1 text-[10px] font-bold rounded-lg {{ $sort === 'desc' ? 'bg-white dark:bg-zinc-700 shadow-sm text-zinc-900 dark:text-white' : 'text-zinc-400' }}">
                    MÁS NUEVO
                </button>
                <button wire:click="$set('sort', 'asc')"
                        class="px-3 py-1 text-[10px] font-bold rounded-lg {{ $sort === 'asc' ? 'bg-white dark:bg-zinc-700 shadow-sm text-zinc-900 dark:text-white' : 'text-zinc-400' }}">
                    MÁS ANTIGUO
                </button>
            </div>
        </div>
    </div>

    <div id="area-contenido">
        <div class="flex items-center justify-between mb-12 border-b border-zinc-100 dark:border-zinc-800 pb-8">
            <div class="flex items-center gap-5">
                <a href="/mascotas" class="no-print p-2 bg-zinc-100 dark:bg-zinc-800 rounded-full" wire:navigate>
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
                </a>
                <div>
                    <h2 class="text-3xl font-black text-zinc-900 dark:text-white">Historial de {{ $pet['Nombre'] }}</h2>
                    <p class="text-zinc-500">{{ $pet['Especie'] }} • {{ $pet['Raza'] }}</p>
                </div>
            </div>
            <button type="button"
                    onclick="descargarReportePDF()"
                    class="no-print flex items-center gap-2 bg-zinc-900 text-white px-5 py-2.5 rounded-2xl font-bold hover:bg-zinc-800 shadow-lg cursor-pointer transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" x2="12" y1="15" y2="3"/></svg>
                <span>Exportar Historial</span>
            </button>
        </div>

        <div class="relative">
            @forelse($this->filteredHistory as $record)
                <div class="grid grid-cols-[100px_40px_1fr] gap-2 mb-10">
                    <div class="text-right pt-1 pr-2">
                        <span class="block text-[10px] font-black text-zinc-500 uppercase">{{ Carbon::parse($record['date'])->translatedFormat('M.') }}</span>
                        <span class="block text-sm font-bold text-zinc-400">{{ Carbon::parse($record['date'])->format('Y') }}</span>
                    </div>
                    <div class="relative flex justify-center">
                        <div class="z-10 w-4 h-4 rounded-full bg-blue-600 border-4 border-white mt-1"></div>
                    </div>
                    <div class="bg-white dark:bg-zinc-900 border border-zinc-100 dark:border-zinc-800 p-6 rounded-3xl shadow-sm">
                        <span class="text-[9px] font-black text-blue-600 bg-blue-50 dark:bg-blue-900/30 px-2 py-1 rounded-lg uppercase">{{ $record['reason'] }}</span>
                        <h3 class="text-lg font-bold text-zinc-900 dark:text-white mt-2">{{ $record['vet_name'] ?? 'Clínica SIVi' }}</h3>
                        @if(!empty($record['notes']))
                            <p class="mt-4 text-zinc-600 text-xs italic border-l-2 border-zinc-100 dark:border-zinc-800 pl-4">"{{ $record['notes'] }}"</p>
                        @endif
                    </div>
                </div>
            @empty
                <p class="text-center py-20 text-zinc-500 italic">No hay registros en el historial.</p>
            @endforelse
        </div>
    </div>

    @script
    <script>
        window.descargarReportePDF = function () {
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF();

            const petName = "{{ $pet['Nombre'] }}";
            const petInfo = "{{ $pet['Especie'] }} • {{ $pet['Raza'] }}";
            const historyData = @json($this->filteredHistory);

            // Estilos y Colores
            const primaryColor = [37, 99, 235]; // Blue 600
            const textColor = [17, 24, 39]; // Gray 900

            // Encabezado
            doc.setFontSize(22);
            doc.setTextColor(primaryColor[0], primaryColor[1], primaryColor[2]);
            doc.setFont('helvetica', 'bold');
            doc.text('REPORTE CLÍNICO', 14, 22);

            doc.setFontSize(10);
            doc.setTextColor(107, 114, 128);
            doc.setFont('helvetica', 'normal');
            doc.text('GENERADO POR PETCARE MANAGEMENT SYSTEM', 14, 28);

            // Información de la Mascota
            doc.setFillColor(249, 250, 251);
            doc.rect(14, 35, 182, 20, 'F');

            doc.setFontSize(14);
            doc.setTextColor(textColor[0], textColor[1], textColor[2]);
            doc.setFont('helvetica', 'bold');
            doc.text(petName, 20, 44);

            doc.setFontSize(10);
            doc.setFont('helvetica', 'normal');
            doc.text(petInfo, 20, 49);

            // Tabla de Historial
            const tableRows = historyData.map(item => [
                item.date,
                item.reason.toUpperCase(),
                item.vet_name || 'Clínica SIVi',
                item.notes || 'Sin observaciones'
            ]);

            doc.autoTable({
                startY: 65,
                head: [['FECHA', 'MOTIVO', 'VETERINARIO', 'OBSERVACIONES']],
                body: tableRows,
                headStyles: {
                    fillColor: primaryColor,
                    fontSize: 9,
                    fontStyle: 'bold',
                    halign: 'center'
                },
                columnStyles: {
                    0: { cellWidth: 30, halign: 'center' },
                    1: { cellWidth: 30, halign: 'center' },
                    2: { cellWidth: 40 },
                    3: { fontStyle: 'italic' }
                },
                styles: {
                    fontSize: 9,
                    cellPadding: 5
                },
                alternateRowStyles: {
                    fillColor: [249, 250, 251]
                },
                margin: { top: 65 }
            });

            // Pie de página
            const pageCount = doc.internal.getNumberOfPages();
            for (let i = 1; i <= pageCount; i++) {
                doc.setPage(i);
                doc.setFontSize(8);
                doc.setTextColor(156, 163, 175);
                doc.text(
                    'Página ' + i + ' de ' + pageCount + ' - Generado el ' + new Date().toLocaleString(),
                    14,
                    doc.internal.pageSize.getHeight() - 10
                );
            }

            // Descarga Directa
            doc.save('Historial_' + petName.replace(/\s+/g, '_') + '.pdf');
        };
    </script>
    @endscript
</main>
