<script setup lang="ts">
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import Header from '@/Components/Layout/header.vue';
import TopBar from '@/Components/Layout/top-bar.vue';
import BackToTop from '@/Components/Layout/back-to-top.vue';
import { useRecentlyViewed } from '@/composables/useRecentlyViewed';
import { History, Trash2, Eye, Calendar } from 'lucide-vue-next';

const { recent, clear, remove } = useRecentlyViewed();

const items = computed(() => recent.value);

function formatPrice(p: number) {
    return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(p);
}

function formatDate(ts: number) {
    return new Date(ts).toLocaleString('es-MX', { dateStyle: 'medium', timeStyle: 'short' });
}

function clearAll() {
    if (confirm('¿Limpiar tu historial completo de productos vistos?')) clear();
}
</script>

<template>
    <TopBar />
    <Header />

    <main class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-8">
        <header class="mb-6 flex items-end justify-between flex-wrap gap-3">
            <div>
                <h1 class="text-3xl font-black text-gray-900 flex items-center gap-2">
                    <History class="w-7 h-7 text-gray-500" />
                    Vistos recientemente
                </h1>
                <p class="text-gray-500 mt-1">
                    {{ items.length }}
                    {{ items.length === 1 ? 'producto' : 'productos' }}
                    vistos en este navegador
                </p>
            </div>
            <button
                v-if="items.length > 0"
                type="button"
                class="flex items-center gap-1.5 px-3 py-1.5 text-sm border border-gray-200 rounded-md text-red-600 hover:bg-red-50"
                @click="clearAll"
            >
                <Trash2 class="w-4 h-4" />
                Limpiar todo
            </button>
        </header>

        <div v-if="items.length === 0" class="border-2 border-dashed border-gray-200 rounded-2xl p-16 text-center">
            <Eye class="w-16 h-16 text-gray-300 mx-auto mb-4" />
            <h2 class="text-lg font-semibold text-gray-700 mb-2">Sin historial todavía</h2>
            <p class="text-sm text-gray-500 mb-4 max-w-md mx-auto">
                Los productos que visites aparecerán aquí. Útil para comparar o retomar una búsqueda.
            </p>
            <Link
                href="/"
                class="inline-block bg-green-600 hover:bg-green-700 text-white font-semibold px-5 py-2.5 rounded-lg transition-colors"
            >
                Explorar catálogo
            </Link>
        </div>

        <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
            <article
                v-for="item in items"
                :key="item.producto_id"
                class="group relative flex flex-col bg-white border border-gray-100 rounded-2xl overflow-hidden shadow-sm hover:shadow-xl hover:-translate-y-0.5 transition-all"
            >
                <Link
                    :href="`/productos/${item.producto_id}`"
                    class="block relative aspect-square bg-gray-50 overflow-hidden"
                >
                    <img
                        v-if="item.img_portada"
                        :src="item.img_portada"
                        :alt="item.titulo"
                        loading="lazy"
                        class="w-full h-full object-contain p-2 transition-transform duration-300 group-hover:scale-105"
                    />
                    <div v-else class="w-full h-full flex items-center justify-center text-gray-300">
                        <Eye class="w-12 h-12" />
                    </div>
                </Link>

                <div class="flex-1 flex flex-col p-4 gap-2">
                    <p v-if="item.marca" class="text-[11px] font-bold uppercase tracking-wider text-green-600 truncate">
                        {{ item.marca }}
                    </p>
                    <Link :href="`/productos/${item.producto_id}`" class="block">
                        <h3 class="text-sm font-semibold text-gray-800 hover:text-green-700 line-clamp-2 leading-snug min-h-[2.5em]">
                            {{ item.titulo }}
                        </h3>
                    </Link>
                    <p class="text-xs text-gray-400 flex items-center gap-1">
                        <Calendar class="w-3 h-3" />
                        Visto el {{ formatDate(item.viewed_at) }}
                    </p>
                    <p class="text-lg font-black text-gray-900 mt-auto">
                        {{ formatPrice(item.precio) }}
                    </p>
                </div>

                <div class="flex border-t border-gray-100">
                    <Link
                        :href="`/productos/${item.producto_id}`"
                        class="flex-1 text-center text-sm font-medium text-green-600 hover:bg-green-50 py-2.5 transition-colors"
                    >
                        Ver producto
                    </Link>
                    <button
                        type="button"
                        class="px-3 text-red-500 hover:bg-red-50 border-l border-gray-100 transition-colors"
                        aria-label="Quitar del historial"
                        @click="remove(item.producto_id)"
                    >
                        <Trash2 class="w-4 h-4" />
                    </button>
                </div>
            </article>
        </div>
    </main>

    <BackToTop />
</template>
