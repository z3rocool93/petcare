<?php
use function Livewire\Volt\{layout};

layout('components.layouts.app');
?>

<div class="bg-white dark:bg-zinc-950 min-h-screen">
    {{-- Hero de Servicios --}}
    <div class="py-20 px-6 text-center bg-zinc-50 dark:bg-zinc-900/50 border-b border-zinc-100 dark:border-zinc-800">
        <h1 class="text-4xl md:text-5xl font-black text-zinc-900 dark:text-white tracking-tight mb-4">
            Nuestros <span class="text-blue-600">Servicios</span>
        </h1>
        <p class="text-zinc-500 max-w-2xl mx-auto text-lg">
            En PetCare, combinamos tecnología y amor por los animales para ofrecerte herramientas integrales en el cuidado de tus mascotas.
        </p>
    </div>

    {{-- Grid de Servicios --}}
    <div class="max-w-6xl mx-auto py-20 px-6">
        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-12">

            {{-- Servicio 1: Historial Clínico --}}
            <div class="space-y-4 group">
                <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900/30 text-blue-600 rounded-2xl flex items-center justify-center shadow-sm group-hover:scale-110 transition-transform">
                    <flux:icon.clipboard-document-list class="w-6 h-6" />
                </div>
                <h3 class="text-xl font-bold dark:text-white">Historial Clínico Digital</h3>
                <p class="text-zinc-500 dark:text-zinc-400 text-sm leading-relaxed">
                    Mantén un registro organizado de vacunas, desparasitaciones y antecedentes médicos. Exporta reportes PDF para tu veterinario de confianza.
                </p>
            </div>

            {{-- Servicio 2: Agenda --}}
            <div class="space-y-4 group">
                <div class="w-12 h-12 bg-orange-100 dark:bg-orange-900/30 text-orange-600 rounded-2xl flex items-center justify-center shadow-sm group-hover:scale-110 transition-transform">
                    <flux:icon.calendar-days class="w-6 h-6" />
                </div>
                <h3 class="text-xl font-bold dark:text-white">Agenda y Recordatorios</h3>
                <p class="text-zinc-500 dark:text-zinc-400 text-sm leading-relaxed">
                    Nunca olvides una cita o un tratamiento. Nuestro sistema te notificará cuando sea el momento de la próxima visita al especialista.
                </p>
            </div>

            {{-- Servicio 3: Foro --}}
            <div class="space-y-4 group">
                <div class="w-12 h-12 bg-purple-100 dark:bg-purple-900/30 text-purple-600 rounded-2xl flex items-center justify-center shadow-sm group-hover:scale-110 transition-transform">
                    <flux:icon.chat-bubble-left-right class="w-6 h-6" />
                </div>
                <h3 class="text-xl font-bold dark:text-white">Foro Comunitario</h3>
                <p class="text-zinc-500 dark:text-zinc-400 text-sm leading-relaxed">
                    Comparte experiencias con otros dueños de mascotas. Un espacio seguro para aprender, consultar dudas y conectar con la comunidad.
                </p>
            </div>

            {{-- Servicio 4: Consultas --}}
            <div class="space-y-4 group">
                <div class="w-12 h-12 bg-green-100 dark:bg-green-900/30 text-green-600 rounded-2xl flex items-center justify-center shadow-sm group-hover:scale-110 transition-transform">
                    <flux:icon.chat-bubble-left-ellipsis class="w-6 h-6" />
                </div>
                <h3 class="text-xl font-bold dark:text-white">Consultas con Expertos</h3>
                <p class="text-zinc-500 dark:text-zinc-400 text-sm leading-relaxed">
                    Los planes Premium y VIP cuentan con acceso a chats directos con profesionales para orientarte en situaciones de salud no urgentes.
                </p>
            </div>

            {{-- Servicio 5: Descuentos --}}
            <div class="space-y-4 group">
                <div class="w-12 h-12 bg-pink-100 dark:bg-pink-900/30 text-pink-600 rounded-2xl flex items-center justify-center shadow-sm group-hover:scale-110 transition-transform">
                    <flux:icon.tag class="w-6 h-6" />
                </div>
                <h3 class="text-xl font-bold dark:text-white">Descuentos Exclusivos</h3>
                <p class="text-zinc-500 dark:text-zinc-400 text-sm leading-relaxed">
                    Acceso a beneficios y precios especiales en tiendas asociadas de productos para mascotas, alimentos y servicios de estética.
                </p>
            </div>

            {{-- Servicio 6: Seguridad --}}
            <div class="space-y-4 group">
                <div class="w-12 h-12 bg-zinc-100 dark:bg-zinc-800 text-zinc-600 rounded-2xl flex items-center justify-center shadow-sm group-hover:scale-110 transition-transform">
                    <flux:icon.shield-check class="w-6 h-6" />
                </div>
                <h3 class="text-xl font-bold dark:text-white">Seguridad de Datos</h3>
                <p class="text-zinc-500 dark:text-zinc-400 text-sm leading-relaxed">
                    Tu información y la de tus mascotas está protegida con los estándares más altos, respaldada por la infraestructura de Firebase.
                </p>
            </div>
        </div>
    </div>

    {{-- CTA Final --}}
    <div class="max-w-4xl mx-auto mb-20 p-8 md:p-12 bg-blue-600 rounded-[3rem] text-center text-white shadow-2xl mx-6 lg:mx-auto">
        <h2 class="text-3xl font-black mb-4 italic">¿Listo para darle lo mejor a tu mascota?</h2>
        <p class="text-blue-100 mb-8 max-w-lg mx-auto">Únete a los cientos de dueños que ya están transformando la gestión de salud de sus compañeros.</p>
        <div class="flex flex-col sm:flex-row gap-4 justify-center">
            <a href="/register" class="bg-white text-blue-600 px-8 py-3 rounded-2xl font-black hover:bg-blue-50 transition shadow-lg">Comenzar Ahora</a>
            <a href="/membresias" class="bg-blue-500 text-white border border-white/20 px-8 py-3 rounded-2xl font-black hover:bg-blue-400 transition">Ver Planes</a>
        </div>
    </div>
</div>
