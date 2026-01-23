<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Computed;

new class extends Component {
    public string $busqueda = '';

    // RF3: Lista de veterinarias (Datos para la entrega en UFRO)
    #[Computed]
    public function listaVets()
    {
        $todas = [
            [
                'id' => 1,
                'nombre' => 'Veterinaria "El Ombú"',
                'direccion' => 'Av. Rivadavia 4500, Caballito, CABA',
                'telefono' => '011 4901-xxxx',
                'horario' => '09:00 a 20:00',
                'servicios' => 'Clínica, Vacunación, Cirugía',
                'lat' => -34.6186, 'lng' => -58.4367
            ],
            [
                'id' => 2,
                'nombre' => 'Centro Médico "Pampa"',
                'direccion' => 'Av. del Libertador 1200, Recoleta, CABA',
                'telefono' => '011 4811-xxxx',
                'horario' => '24 Horas',
                'servicios' => 'Urgencias, Rayos X, Ecografías',
                'lat' => -34.5915, 'lng' => -58.3895
            ],
            [
                'id' => 3,
                'nombre' => 'Hospital "San Roque"',
                'direccion' => 'Calle Defensa 800, San Telmo, CABA',
                'telefono' => '011 4361-xxxx',
                'horario' => '08:00 a 21:00',
                'servicios' => 'Laboratorio, Farmacia, Nutrición',
                'lat' => -34.6177, 'lng' => -58.3719
            ],
        ];

        if (empty($this->busqueda)) return $todas;

        $termino = strtolower($this->busqueda);
        return array_values(array_filter($todas, function($v) use ($termino) {
            return str_contains(strtolower($v['nombre']), $termino) ||
                str_contains(strtolower($v['servicios']), $termino);
        }));
    }

    // RF2: Notificamos al mapa cuando cambian los resultados
    public function updatedBusqueda()
    {
        $this->dispatch('vets-filtradas', vets: $this->listaVets);
    }
}; ?>

<div
    x-data="mapaPetCare()"
    x-on:vets-filtradas.window="actualizarMapa($event.detail.vets)"
    class="space-y-6"
>
    <header>
        <h2 class="text-2xl font-black text-zinc-900 dark:text-white uppercase tracking-tight">Veterinarias Cercanas</h2>
        <p class="text-zinc-500 text-sm italic">Atención para el proyecto PetCare - Buenos Aires, Argentina.</p>
    </header>

    {{-- Buscador --}}
    <flux:input
        wire:model.live.debounce.300ms="busqueda"
        placeholder="Buscar por nombre o servicio (ej. Urgencias)..."
        icon="magnifying-glass"
        class="rounded-2xl shadow-sm"
    />

    {{-- Layout Responsivo --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 lg:h-[600px]">

        {{-- LISTA DE RESULTADOS (RF3) --}}
        {{-- order-2: En mobile pasa abajo del mapa / h-[400px]: Altura fija en mobile --}}
        <div class="order-2 lg:order-1 lg:col-span-1 h-[400px] lg:h-full space-y-4 overflow-y-auto pr-2 custom-scrollbar">
            @forelse($this->listaVets as $vet)
                <div
                    x-on:click="centrarEn({{ $vet['lat'] }}, {{ $vet['lng'] }})"
                    class="p-5 bg-white dark:bg-zinc-900 border border-zinc-100 dark:border-zinc-800 rounded-[2rem] hover:border-primary-300 transition-all cursor-pointer group shadow-sm"
                >
                    <div class="flex justify-between items-start mb-2">
                        <h4 class="font-black text-zinc-900 dark:text-white uppercase text-sm group-hover:text-primary-500 transition">
                            {{ $vet['nombre'] }}
                        </h4>
                    </div>

                    <div class="space-y-1 text-[11px] text-zinc-500">
                        <p class="flex items-center gap-2"><flux:icon.map-pin variant="micro"/> {{ $vet['direccion'] }}</p>
                        <p class="flex items-center gap-2"><flux:icon.clock variant="micro"/> {{ $vet['horario'] }}</p>
                    </div>

                    <p class="mt-3 text-[10px] bg-zinc-50 dark:bg-zinc-800/50 p-2 rounded-xl border border-zinc-100 dark:border-zinc-800 italic">
                        <strong>Servicios:</strong> {{ $vet['servicios'] }}
                    </p>
                </div>
            @empty
                <div class="text-center py-10 text-zinc-400 italic">No hay resultados para tu búsqueda.</div>
            @endforelse
        </div>

        {{-- CONTENEDOR DEL MAPA (RF1) --}}
        {{-- order-1: En mobile aparece primero / h-[350px]: Evita que se vea como una franja --}}
        <div class="order-1 lg:order-2 lg:col-span-2 h-[350px] lg:h-full rounded-[2.5rem] overflow-hidden border border-zinc-100 dark:border-zinc-800 shadow-inner" wire:ignore>
            <div id="map" class="h-full w-full z-0"></div>
        </div>
    </div>



    <script>
        function mapaPetCare() {
            return {
                leafletMap: null,
                markers: [],

                init() {
                    // Inicializamos centrado en CABA
                    this.leafletMap = L.map('map').setView([-34.6037, -58.3816], 12);

                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        attribution: '&copy; OpenStreetMap contributors'
                    }).addTo(this.leafletMap);

                    // Cargamos los datos iniciales
                    this.actualizarMapa(@json($this->listaVets));

                    // AJUSTE CLAVE: Forzamos el redibujado para evitar errores de carga en mobile
                    setTimeout(() => {
                        this.leafletMap.invalidateSize();
                    }, 500);
                },

                actualizarMapa(vets) {
                    // Limpiar marcadores existentes
                    this.markers.forEach(m => this.leafletMap.removeLayer(m));
                    this.markers = [];

                    // Dibujar nuevos marcadores
                    vets.forEach(v => {
                        const marker = L.marker([v.lat, v.lng])
                            .addTo(this.leafletMap)
                            .bindPopup(`<strong>${v.nombre}</strong><br><small>${v.direccion}</small>`);
                        this.markers.push(marker);
                    });

                    // Si solo hay un resultado, hacemos foco
                    if (vets.length === 1) {
                        this.centrarEn(vets[0].lat, vets[0].lng);
                    }
                },

                centrarEn(lat, lng) {
                    this.leafletMap.flyTo([lat, lng], 15);

                    // En móviles, hacemos scroll suave hacia arriba para que el usuario vea el movimiento del mapa
                    if (window.innerWidth < 1024) {
                        window.scrollTo({ top: 0, behavior: 'smooth' });
                    }
                }
            }
        }
    </script>
</div>
