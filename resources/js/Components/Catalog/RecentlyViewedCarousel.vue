<script setup lang="ts">
import { ref, computed, onMounted, onBeforeUnmount } from 'vue';
import { Link } from '@inertiajs/vue3';
import { ChevronLeft, ChevronRight, X, History, Eye } from 'lucide-vue-next';
import { useRecentlyViewed } from '@/composables/useRecentlyViewed';

interface Props {
    title?: string;
    subtitle?: string;
    excludeId?: number;
    showClear?: boolean;
    showViewAll?: boolean;
    showRemove?: boolean;
    emptyMessage?: string;
}

const props = withDefaults(defineProps<Props>(), {
    title: 'Vistos recientemente',
    subtitle: 'Productos que visitaste en esta sesión',
    excludeId: 0,
    showClear: true,
    showViewAll: true,
    showRemove: true,
    emptyMessage: 'Aún no has visto productos. ¡Explora el catálogo!',
});

const { recent, clear, remove } = useRecentlyViewed();

const scrollerEl = ref<HTMLElement | null>(null);
const canScrollLeft = ref(false);
const canScrollRight = ref(false);

const items = computed(() => {
    if (!props.excludeId) return recent.value;
    return recent.value.filter((i) => i.producto_id !== props.excludeId);
});

function updateScrollButtons() {
    const el = scrollerEl.value;
    if (!el) return;
    canScrollLeft.value = el.scrollLeft > 4;
    canScrollRight.value = el.scrollLeft < el.scrollWidth - el.clientWidth - 4;
}

function scrollBy(delta: number) {
    scrollerEl.value?.scrollBy({ left: delta, behavior: 'smooth' });
}

function scrollLeft() {
    if (!scrollerEl.value) return;
    const cardWidth = scrollerEl.value.clientWidth * 0.7;
    scrollBy(-cardWidth);
}

function scrollRight() {
    if (!scrollerEl.value) return;
    const cardWidth = scrollerEl.value.clientWidth * 0.7;
    scrollBy(cardWidth);
}

function onKeydown(e: KeyboardEvent) {
    if (e.key === 'ArrowLeft' && canScrollLeft.value) scrollLeft();
    if (e.key === 'ArrowRight' && canScrollRight.value) scrollRight();
}

function formatPrice(p: number) {
    return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(p);
}

function clearAll() {
    if (confirm('¿Limpiar tu historial de productos vistos?')) clear();
}

function removeItem(id: number) {
    remove(id);
}

onMounted(() => {
    updateScrollButtons();
    window.addEventListener('keydown', onKeydown);
    window.addEventListener('resize', updateScrollButtons);
});

onBeforeUnmount(() => {
    window.removeEventListener('keydown', onKeydown);
    window.removeEventListener('resize', updateScrollButtons);
});
</script>

<template>
    <section v-if="items.length > 0" class="my-8">
        <header class="flex items-end justify-between mb-4 gap-4 flex-wrap">
            <div>
                <h2 class="text-xl font-bold text-gray-900 flex items-center gap-2">
                    <History class="w-5 h-5 text-gray-500" />
                    {{ title }}
                </h2>
                <p class="text-sm text-gray-500 mt-0.5">{{ subtitle }}</p>
            </div>
            <div class="flex items-center gap-2">
                <button
                    v-if="showClear"
                    type="button"
                    class="text-xs text-gray-500 hover:text-red-600"
                    @click="clearAll"
                >
                    Limpiar historial
                </button>
                <Link
                    v-if="showViewAll"
                    href="/recientes"
                    class="text-sm font-medium text-green-600 hover:text-green-700 flex items-center gap-1"
                >
                    Ver todos
                    <ChevronRight class="w-4 h-4" />
                </Link>
            </div>
        </header>

        <div class="relative group">
            <button
                v-if="canScrollLeft"
                type="button"
                class="hidden md:flex absolute left-0 top-1/2 -translate-y-1/2 -translate-x-1/2 z-10 h-10 w-10 rounded-full bg-white shadow-lg border border-gray-200 items-center justify-center hover:bg-gray-50 transition-all"
                aria-label="Anterior"
                @click="scrollLeft"
            >
                <ChevronLeft class="w-5 h-5" />
            </button>
            <button
                v-if="canScrollRight"
                type="button"
                class="hidden md:flex absolute right-0 top-1/2 -translate-y-1/2 translate-x-1/2 z-10 h-10 w-10 rounded-full bg-white shadow-lg border border-gray-200 items-center justify-center hover:bg-gray-50 transition-all"
                aria-label="Siguiente"
                @click="scrollRight"
            >
                <ChevronRight class="w-5 h-5" />
            </button>

            <div
                ref="scrollerEl"
                class="flex gap-4 overflow-x-auto scroll-smooth snap-x snap-mandatory pb-2 -mx-1 px-1"
                style="scrollbar-width: thin;"
                @scroll.passive="updateScrollButtons"
            >
                <article
                    v-for="item in items"
                    :key="item.producto_id"
                    class="group/card relative flex-shrink-0 w-44 sm:w-52 snap-start bg-white border border-gray-100 rounded-xl overflow-hidden hover:shadow-lg hover:-translate-y-0.5 transition-all"
                >
                    <Link
                        :href="`/productos/${item.producto_id}`"
                        class="absolute inset-0 z-10 rounded-xl focus:outline-none focus-visible:ring-2 focus-visible:ring-green-500"
                        :aria-label="`Ver ${item.titulo}`"
                    />
                    <div class="relative aspect-square bg-gray-50 overflow-hidden">
                        <img
                            v-if="item.img_portada"
                            :src="item.img_portada"
                            :alt="item.titulo"
                            loading="lazy"
                            class="w-full h-full object-contain p-2 transition-transform duration-300 group-hover/card:scale-105"
                        />
                        <div v-else class="w-full h-full flex items-center justify-center text-gray-300">
                            <Eye class="w-10 h-10" />
                        </div>
                        <button
                            v-if="showRemove"
                            type="button"
                            class="absolute top-1.5 right-1.5 z-20 h-6 w-6 rounded-full bg-white/90 backdrop-blur shadow flex items-center justify-center text-gray-500 hover:text-red-600 opacity-0 group-hover/card:opacity-100 transition-opacity"
                            aria-label="Quitar del historial"
                            @click.prevent.stop="removeItem(item.producto_id)"
                        >
                            <X class="w-3.5 h-3.5" />
                        </button>
                    </div>
                    <div class="p-2.5">
                        <p v-if="item.marca" class="text-[10px] font-bold uppercase tracking-wider text-green-600 truncate">
                            {{ item.marca }}
                        </p>
                        <p class="text-xs font-medium text-gray-800 line-clamp-2 leading-snug min-h-[2.5em]">
                            {{ item.titulo }}
                        </p>
                        <p class="text-sm font-black text-gray-900 mt-1">
                            {{ formatPrice(item.precio) }}
                        </p>
                    </div>
                </article>
            </div>
        </div>
    </section>

    <section v-else class="my-8 border-2 border-dashed border-gray-200 rounded-2xl p-8 text-center">
        <History class="w-10 h-10 text-gray-300 mx-auto mb-2" />
        <p class="text-sm text-gray-500">{{ emptyMessage }}</p>
        <Link
            href="/"
            class="mt-3 inline-block text-sm text-green-600 hover:text-green-700 font-medium"
        >
            Ir al catálogo →
        </Link>
    </section>
</template>
