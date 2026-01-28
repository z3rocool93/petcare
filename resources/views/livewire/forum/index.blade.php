<?php

use function Livewire\Volt\{state, mount, layout, computed, usesFileUploads};
use Kreait\Firebase\Contract\Database;
use Carbon\Carbon;
use Illuminate\Support\Str;

usesFileUploads();

layout('components.layouts.app');

state([
    'posts' => [],
    'search' => '',
    'showCreateModal' => false,
    'newTitle' => '',
    'newBody' => '',
    'newImage' => null,
    'selectedPost' => null,
    'newComment' => '',
    'reportSuccessMessage' => null,
    'userSub' => null
]);

mount(function (Database $database) {
    $this->cargarPosts($database);

    $uid = auth()->id();
    $this->userSub = $database->getReference("user_subscriptions/$uid")->getValue();
});

$cargarPosts = function (Database $database) {
    $data = $database->getReference('forum_posts')->getValue() ?? [];
    $this->posts = collect($data)
        ->map(fn($item, $key) => array_merge($item, ['id' => $key]))
        ->sortByDesc('created_at')
        ->values()
        ->toArray();
};

$filteredPosts = computed(function () {
    if (empty($this->search)) return $this->posts;
    return collect($this->posts)->filter(function ($post) {
        return Str::contains(strtolower($post['title'] ?? ''), strtolower($this->search)) ||
            Str::contains(strtolower($post['body'] ?? ''), strtolower($this->search));
    })->all();
});

$savePost = function (Database $database) {
    // Verificamos si tiene plan (RF10)
    // Si no tiene nada o es 'basic', no puede publicar
    $planKey = $this->userSub['plan_id'] ?? 'basic';

    if ($planKey === 'basic') {
        // Disparamos la notificación de "Funcionalidad no incluida" (RF11)
        $this->js("Swal.fire({
            title: '¡Sube a Premium!',
            text: 'Tu plan actual solo permite leer. Para crear tus propios temas, elige un plan de pago.',
            icon: 'lock',
            showCancelButton: true,
            confirmButtonText: 'Ver Planes 🐾',
            cancelButtonText: 'Después',
            confirmButtonColor: '#3b82f6',
            borderRadius: '1.5rem'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = '/membresias';
            }
        })");
        return;
    }
    // 1. VALIDACIÓN: Ahora con mensajes visibles en el HTML
    $this->validate([
        'newTitle' => 'required|min:5',
        'newBody' => 'required|min:10',
        'newImage' => 'nullable|image|max:2048'
    ]);

    $imageUrl = null;
    if ($this->newImage) {
        $path = $this->newImage->store('forum-images', 'public');
        $imageUrl = asset('storage/' . $path);
    }

    $newPost = [
        'author' => auth()->user()->name ?? 'Usuario Anónimo',
        'author_id' => auth()->id(),
        'title' => $this->newTitle,
        'body' => $this->newBody,
        'image' => $imageUrl,
        'created_at' => time(),
        'reports' => 0,
        'comments' => []
    ];

    $database->getReference('forum_posts')->push($newPost);

    // Resetear estados y cerrar modal
    $this->reset(['newTitle', 'newBody', 'newImage', 'showCreateModal']);
    $this->cargarPosts($database);
};

$addComment = function (Database $database) {
    // REGLA RF10: El plan básico es solo lectura para el foro
    $planKey = $this->userSub['plan_id'] ?? 'basic';

    if ($planKey === 'basic') {
        $this->js("Swal.fire({
            title: '¡Sube de nivel!',
            text: 'Tu plan actual solo permite leer. Para comentar y participar, elige un plan Premium o VIP.',
            icon: 'info',
            showCancelButton: true,
            confirmButtonText: 'Ver Planes 🐾',
            confirmButtonColor: '#3b82f6',
            borderRadius: '1.5rem'
        }).then((result) => {
            if (result.isConfirmed) { window.location.href = '/membresias'; }
        })");
        return; // IMPORTANTE: Detiene la ejecución para que no se guarde en Firebase
    }

    if (!$this->selectedPost || empty($this->newComment)) return;

    $postId = $this->selectedPost['id'];
    $commentData = [
        'user' => auth()->user()->name ?? 'Usuario',
        'text' => $this->newComment,
        'date' => now()->format('d/m/Y H:i')
    ];

    $database->getReference("forum_posts/$postId/comments")->push($commentData);
    $this->newComment = '';
    $this->cargarPosts($database);

    // Actualizar el post seleccionado para ver el comentario nuevo
    $this->openPost($postId);
};

$reportPost = function (Database $database, $postId) {
    $currentReports = $database->getReference("forum_posts/$postId/reports")->getValue() ?? 0;
    $database->getReference("forum_posts/$postId")->update(['reports' => $currentReports + 1]);

    // Activamos el mensaje de éxito
    $this->reportSuccessMessage = 'Gracias. El contenido ha sido reportado para revisión.';
};

$openPost = function ($postId) {
    $postEncontrado = collect($this->posts)->firstWhere('id', $postId);
    if ($postEncontrado) {
        if (!isset($postEncontrado['comments'])) {
            $postEncontrado['comments'] = [];
        }
        $this->selectedPost = $postEncontrado;
    }
};

?>

<div class="p-6 max-w-5xl mx-auto min-h-screen relative">

    {{-- 1. TOAST DE REPORTE (CON ALPINE PARA QUE DESAPAREZCA SOLO) --}}
    @if($reportSuccessMessage)
        <div
            x-data="{ show: true }"
            x-init="setTimeout(() => { show = false; $wire.set('reportSuccessMessage', null) }, 4000)"
            x-show="show"
            x-transition:leave="transition ease-in duration-500"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed top-4 right-4 z-[60] bg-green-600 text-white px-6 py-3 rounded-2xl shadow-lg flex items-center gap-2"
        >
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
            <span class="font-bold text-sm">{{ $reportSuccessMessage }}</span>
        </div>
    @endif

    {{-- CABECERA --}}
    <div class="flex flex-col md:flex-row justify-between items-center gap-4 mb-8">
        <div>
            <h1 class="text-3xl font-black text-secondary dark:text-secondary">Foro de Comunidad</h1>
            <p class="text-zinc-500">Comparte experiencias con otros dueños de mascotas</p>
        </div>

        <div class="flex gap-3 w-full md:w-auto">
            <input wire:model.live="search" type="text" placeholder="Buscar temas..." class="bg-white dark:bg-secondary-light border border-zinc-200 dark:border-zinc-700 rounded-xl px-4 py-2 w-full md:w-64 focus:ring-2 focus:ring-primary-500 outline-none">
            <button wire:click="$set('showCreateModal', true)" class="bg-primary-600 hover:bg-primary-700 text-white px-4 py-2 rounded-xl font-bold whitespace-nowrap shadow-lg transition transform hover:scale-105">+ Crear Post</button>
        </div>
    </div>

    {{-- FEED DE NOTICIAS --}}
    <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
        @forelse($this->filteredPosts as $post)
            <div class="bg-white dark:bg-secondary-light border border-zinc-100 dark:border-zinc-800 rounded-3xl p-5 shadow-sm hover:shadow-md transition flex flex-col h-full relative group">

                {{-- 2. REPORTE CON SWEETALERT2 --}}
                <button
                    type="button"
                    @click="
                        Swal.fire({
                            title: '¿Reportar publicación?',
                            text: 'Esta acción notificará a los moderadores.',
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonColor: '#ef4444',
                            confirmButtonText: 'Sí, reportar',
                            cancelButtonText: 'Cancelar',
                            borderRadius: '1.5rem'
                        }).then((result) => {
                            if (result.isConfirmed) { $wire.reportPost('{{ $post['id'] }}') }
                        })
                    "
                    class="absolute top-4 right-4 text-zinc-300 hover:text-red-500 transition z-10"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"/><line x1="4" x2="4" y1="22" y2="15"/></svg>
                </button>

                @if(!empty($post['image']))
                    <div class="h-40 w-full bg-zinc-100 rounded-2xl mb-4 overflow-hidden">
                        <img src="{{ $post['image'] }}" class="w-full h-full object-cover">
                    </div>
                @endif

                <div class="flex-1">
                    <div class="flex items-center gap-2 mb-2">
                        <div class="h-6 w-6 rounded-full bg-primary-100 text-primary-600 flex items-center justify-center text-xs font-bold uppercase">
                            {{ substr($post['author'] ?? 'U', 0, 1) }}
                        </div>
                        <span class="text-xs text-zinc-500 font-medium">{{ $post['author'] }}</span>
                        <span class="text-xs text-zinc-300">• {{ Carbon::parse($post['created_at'])->diffForHumans() }}</span>
                    </div>

                    <h3 class="text-lg font-bold text-secondary dark:text-white mb-2 cursor-pointer hover:text-primary-600"
                        wire:click="openPost('{{ $post['id'] }}')">
                        {{ $post['title'] }}
                    </h3>

                    <p class="text-sm text-zinc-500 line-clamp-3">{{ $post['body'] }}</p>
                </div>

                <div class="mt-4 pt-4 border-t border-zinc-100 dark:border-zinc-800 flex justify-between items-center">
                    <button wire:click="openPost('{{ $post['id'] }}')" class="text-sm font-bold text-primary-600 hover:underline flex items-center gap-1">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/></svg>
                        {{ isset($post['comments']) ? count($post['comments']) : 0 }}
                    </button>
                </div>
            </div>
        @empty
            <div class="col-span-full text-center py-20 text-zinc-400">No hay publicaciones aún.</div>
        @endforelse
    </div>

    {{-- 3. MODAL CREAR POST CORREGIDO (CON VALIDACIONES VISIBLES) --}}
    @if($showCreateModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4 animate-in fade-in">
            <div class="bg-white dark:bg-secondary-light w-full max-w-lg rounded-3xl p-6 shadow-2xl">
                <h2 class="text-xl font-bold mb-4 dark:text-white">Crear Nueva Publicación</h2>

                <input wire:model="newTitle" type="text" placeholder="Título (min. 5 caracteres)"
                       class="w-full mb-1 rounded-xl border-zinc-200 dark:bg-secondary dark:border-zinc-700 p-3 font-bold focus:ring-2 focus:ring-blue-500 outline-none">
                @error('newTitle') <span class="text-red-500 text-xs mb-3 block">{{ $message }}</span> @enderror

                <textarea wire:model="newBody" rows="4" placeholder="Cuerpo (min. 10 caracteres)"
                          class="w-full mb-1 rounded-xl border-zinc-200 dark:bg-secondary dark:border-zinc-700 p-3 focus:ring-2 focus:ring-blue-500 outline-none"></textarea>
                @error('newBody') <span class="text-red-500 text-xs mb-3 block">{{ $message }}</span> @enderror

                <div class="mb-4 mt-2">
                    <label class="block text-xs font-bold text-zinc-500 uppercase mb-2">Adjuntar Imagen</label>
                    <input wire:model="newImage" type="file" class="block w-full text-sm text-zinc-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-xs file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"/>
                    @error('newImage') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                </div>

                <div class="flex justify-end gap-2">
                    {{-- Botón Cancelar --}}
                    <button wire:click="$set('showCreateModal', false)" class="px-4 py-2 text-zinc-500 font-bold">
                        Cancelar
                    </button>

                    {{-- Botón Publicar con Target Específico --}}
                    <button
                        wire:click="savePost"
                        wire:loading.attr="disabled"
                        wire:target="savePost"
                        wire:key="btn-publicar-foro" {{-- Agregamos una llave única para evitar conflictos de DOM --}}
                        class="bg-blue-600 text-white px-6 py-2 rounded-xl font-bold hover:bg-blue-700 disabled:opacity-50 inline-flex items-center justify-center min-w-[120px]"
                    >
                        {{-- Estado Normal: Se oculta cuando savePost está cargando --}}
                        <span wire:loading.class="hidden" wire:target="savePost">
        Publicar
    </span>

                        {{-- Estado de Carga: Oculto por defecto con 'hidden' --}}
                        {{-- Solo se muestra cuando Livewire remueve la clase 'hidden' al ejecutar savePost --}}
                        <span class="hidden" wire:loading.class.remove="hidden" wire:target="savePost">
        <div class="flex items-center gap-2">
            <svg class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span>Procesando...</span>
        </div>
    </span>
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- MODAL DETALLE (Igual que antes pero con cierre corregido) --}}
    @if($selectedPost)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4 animate-in fade-in">
            <div class="bg-white dark:bg-zinc-900 w-full max-w-2xl max-h-[90vh] overflow-y-auto rounded-3xl shadow-2xl relative">
                <button wire:click="$set('selectedPost', null)" class="absolute top-4 right-4 p-2 bg-zinc-100 rounded-full hover:bg-zinc-200 z-10 text-zinc-600">✕</button>

                <div class="p-6 md:p-8">
                    @if(!empty($selectedPost['image']))
                        <img src="{{ $selectedPost['image'] }}" class="w-full h-64 object-cover rounded-2xl mb-6">
                    @endif

                    <h2 class="text-2xl font-black mb-2 dark:text-white">{{ $selectedPost['title'] }}</h2>
                    <p class="text-zinc-600 dark:text-zinc-300 mb-8 leading-relaxed whitespace-pre-wrap">{{ $selectedPost['body'] }}</p>

                    <h3 class="font-bold text-lg mb-4 dark:text-white">Comentarios</h3>
                    <div class="space-y-4 mb-6">
                        @forelse($selectedPost['comments'] as $comment)
                            <div class="bg-zinc-50 dark:bg-zinc-800 p-4 rounded-xl">
                                <div class="flex justify-between mb-1">
                                    <span class="font-bold text-sm dark:text-white">{{ $comment['user'] }}</span>
                                    <span class="text-xs text-zinc-400">{{ $comment['date'] }}</span>
                                </div>
                                <p class="text-sm text-zinc-600 dark:text-zinc-300">{{ $comment['text'] }}</p>
                            </div>
                        @empty
                            <p class="text-zinc-400 text-sm italic">Sin comentarios.</p>
                        @endforelse
                    </div>

                    <div class="flex gap-2">
                        <input wire:model="newComment" wire:keydown.enter="addComment" type="text" placeholder="Escribir..." class="flex-1 bg-zinc-100 dark:bg-zinc-800 border-none rounded-xl px-4 py-2 focus:ring-2 focus:ring-blue-500 outline-none">
                        <button wire:click="addComment" class="bg-zinc-900 text-white px-4 rounded-xl">Enviar</button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
