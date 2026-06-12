<script setup lang="ts">
import { computed, ref, onMounted, onBeforeUnmount } from 'vue';
import { Link } from '@inertiajs/vue3';
import { Eye, Heart, X, ShieldCheck, Truck, Tag, ChevronRight } from 'lucide-vue-next';
import AddToCartButton from '@/Components/AddToCartButton.vue';
import axios from 'axios';

interface Price {
    precio_1?: number;
    precio_descuento?: number;
    precio_especial?: number;
    precio_lista?: number;
}

interface Product {
    producto_id: number;
    titulo?: string;
    modelo?: string;
    marca?: string;
    img_portada?: string | null;
    total_existencia?: number;
    precios?: Price;
    garantia?: string | null;
    categorias?: { id: number; nombre: string }[];
    descripcion?: string;
    descripcion_larga?: string;
    imagenes?: string[];
}

interface Props {
    product: Product;
    eager?: boolean;
}

const props = withDefaults(defineProps<Props>(), { eager: false });
const emit = defineEmits<{
    (e: 'wishlist', productoId: number, added: boolean): void;
}>();

const isWishlisted = ref(false);
const quickViewProduct = ref<Product | null>(null);
const isQuickViewLoading = ref(false);
const quickViewQty = ref(1);
const quickViewImage = ref(0);

const effectivePrice = computed(() => {
    const p = props.product.precios;
    if (!p) return 0;
    if (p.precio_especial && p.precio_especial > 0) return p.precio_especial;
    if (p.precio_descuento && p.precio_descuento > 0) return p.precio_descuento;
    if (p.precio_1 && p.precio_1 > 0) return p.precio_1;
    return p.precio_lista ?? 0;
});

const originalPrice = computed(() => props.product.precios?.precio_lista ?? 0);
const hasDiscount = computed(() => {
    const p = props.product.precios;
    if (!p) return false;
    return ((p.precio_descuento ?? 0) > 0 || (p.precio_especial ?? 0) > 0) && originalPrice.value > effectivePrice.value;
});
const discountPct = computed(() => {
    if (!hasDiscount.value || originalPrice.value === 0) return 0;
    return Math.round((1 - effectivePrice.value / originalPrice.value) * 100);
});

const stockStatus = computed(() => {
    const stock = props.product.total_existencia ?? 0;
    if (stock <= 0) return { label: 'Agotado', class: 'bg-red-100 text-red-700', canBuy: false };
    if (stock <= 5) return { label: `Últimas ${stock}`, class: 'bg-amber-100 text-amber-800', canBuy: true };
    return { label: 'En stock', class: 'bg-emerald-100 text-emerald-700', canBuy: true };
});

const placeholderSvg = `data:image/svg+xml;utf8,${encodeURIComponent(
    `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 400 400"><rect fill="#f3f4f6" width="400" height="400"/><text x="50%" y="50%" font-family="sans-serif" font-size="24" fill="#9ca3af" text-anchor="middle" dominant-baseline="middle">Sin imagen</text></svg>`
)}`;

const primaryCategory = computed(() => props.product.categorias?.[0]?.nombre);
const productHref = computed(() => `/productos/${props.product.producto_id}`);

function toggleWishlist() {
    isWishlisted.value = !isWishlisted.value;
    emit('wishlist', props.product.producto_id, isWishlisted.value);
}

async function openQuickView() {
    isQuickViewLoading.value = true;
    quickViewProduct.value = null;
    quickViewImage.value = 0;
    quickViewQty.value = 1;

    try {
        const { data } = await axios.get(`/api/syscom/products/${props.product.producto_id}`);
        quickViewProduct.value = data;
    } catch {
        quickViewProduct.value = props.product;
    } finally {
        isQuickViewLoading.value = false;
    }
}

function closeQuickView() {
    quickViewProduct.value = null;
}

const quickViewImages = computed(() => {
    if (!quickViewProduct.value) return [];
    const images: string[] = [];
    if (quickViewProduct.value.img_portada) images.push(quickViewProduct.value.img_portada);
    if (Array.isArray(quickViewProduct.value.imagenes)) {
        quickViewProduct.value.imagenes.forEach((img: string) => {
            if (img && !images.includes(img)) images.push(img);
        });
    }
    if (images.length === 0) {
        images.push(`data:image/svg+xml;utf8,${encodeURIComponent(
            '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 600 400"><rect fill="#f3f4f6" width="600" height="400"/><text x="50%" y="50%" font-family="sans-serif" font-size="24" fill="#9ca3af" text-anchor="middle" dominant-baseline="middle">Sin imagen</text></svg>'
        )}`);
    }
    return images;
});

const quickViewEffectivePrice = computed(() => {
    const p = quickViewProduct.value?.precios;
    if (!p) return 0;
    if ((p.precio_especial ?? 0) > 0) return p.precio_especial ?? 0;
    if ((p.precio_descuento ?? 0) > 0) return p.precio_descuento ?? 0;
    if ((p.precio_1 ?? 0) > 0) return p.precio_1 ?? 0;
    return p.precio_lista ?? 0;
});

const quickViewOriginalPrice = computed(() => quickViewProduct.value?.precios?.precio_lista ?? 0);
const quickViewHasDiscount = computed(() => quickViewOriginalPrice.value > quickViewEffectivePrice.value && quickViewEffectivePrice.value > 0);
const quickViewStock = computed(() => quickViewProduct.value?.total_existencia ?? 0);
const quickViewCanBuy = computed(() => quickViewStock.value > 0);

const quickViewDescription = computed(() => {
    if (!quickViewProduct.value) return '';
    return quickViewProduct.value.descripcion
        ?? quickViewProduct.value.descripcion_larga
        ?? `${quickViewProduct.value.titulo}${quickViewProduct.value.modelo ? ' (modelo ' + quickViewProduct.value.modelo + ')' : ''}. Producto distribuido por Syscom con garantía local PCBtroniks.`;
});

function onQuickViewKeydown(e: KeyboardEvent) {
    if (!quickViewProduct.value) return;
    if (e.key === 'Escape') closeQuickView();
}

onMounted(() => {
    window.addEventListener('keydown', onQuickViewKeydown);
});

onBeforeUnmount(() => {
    window.removeEventListener('keydown', onQuickViewKeydown);
});
</script>

<template>
    <article
        class="group relative flex flex-col bg-white rounded-2xl border border-gray-100 overflow-hidden shadow-sm hover:shadow-xl hover:-translate-y-0.5 transition-all duration-200"
    >
        <Link
            :href="productHref"
            class="absolute inset-0 z-10 rounded-2xl focus:outline-none focus-visible:ring-2 focus-visible:ring-green-500 focus-visible:ring-offset-2"
            :aria-label="`Ver ${product.titulo ?? 'producto'}`"
        />

        <div class="relative aspect-square bg-gray-50 overflow-hidden">
            <img
                :src="product.img_portada || placeholderSvg"
                :alt="product.titulo || 'Producto Syscom'"
                :loading="eager ? 'eager' : 'lazy'"
                class="w-full h-full object-contain p-2 transition-transform duration-300 group-hover:scale-105"
            />

            <span
                v-if="primaryCategory"
                class="absolute top-2 left-2 px-2 py-0.5 bg-white/90 backdrop-blur uppercase text-[10px] font-bold tracking-wider rounded text-gray-700 truncate max-w-[55%]"
            >
                {{ primaryCategory }}
            </span>

            <span
                :class="['absolute bottom-2 right-2 px-2 py-0.5 text-[10px] font-bold rounded-full', stockStatus.class]"
            >
                {{ stockStatus.label }}
            </span>

            <span
                v-if="hasDiscount"
                class="absolute bottom-2 left-2 px-2 py-0.5 bg-red-600 text-white text-[10px] font-bold rounded"
            >
                -{{ discountPct }}%
            </span>

            <div class="absolute top-2 right-2 flex flex-col gap-1.5 z-20">
                <button
                    type="button"
                    class="h-7 w-7 rounded-full bg-white/90 backdrop-blur shadow flex items-center justify-center text-gray-600 hover:text-red-500 opacity-0 group-hover:opacity-100 transition-opacity"
                    :aria-label="isWishlisted ? 'Quitar de favoritos' : 'Agregar a favoritos'"
                    :aria-pressed="isWishlisted"
                    @click.prevent.stop="toggleWishlist"
                >
                    <Heart
                        class="w-3.5 h-3.5 transition-transform"
                        :class="isWishlisted ? 'fill-red-500 text-red-500 scale-110' : ''"
                    />
                </button>
                <button
                    type="button"
                    class="h-7 w-7 rounded-full bg-white/90 backdrop-blur shadow flex items-center justify-center text-gray-600 hover:text-green-600 opacity-0 group-hover:opacity-100 transition-opacity"
                    aria-label="Vista rápida"
                    @click.prevent.stop="openQuickView"
                >
                    <Eye class="w-3.5 h-3.5" />
                </button>
            </div>
        </div>

        <div class="flex-1 flex flex-col p-4 gap-2">
            <p
                v-if="product.marca"
                class="text-[11px] font-bold uppercase tracking-wider text-green-600 truncate"
            >
                {{ product.marca }}
            </p>

            <h3 class="text-sm font-semibold text-gray-800 line-clamp-2 leading-snug min-h-[2.5em]">
                {{ product.titulo || 'Producto Syscom' }}
            </h3>

            <p v-if="product.modelo" class="text-[11px] text-gray-500 truncate">
                Modelo: {{ product.modelo }}
            </p>

            <div class="mt-auto pt-2 flex items-end justify-between gap-2">
                <div>
                    <p class="text-xl font-black text-gray-900 leading-none">
                        ${{ effectivePrice.toFixed(2) }}
                        <span class="text-xs font-medium text-gray-500">USD</span>
                    </p>
                    <p
                        v-if="hasDiscount"
                        class="text-xs text-gray-400 line-through"
                    >
                        ${{ originalPrice.toFixed(2) }}
                    </p>
                </div>
            </div>

            <div class="relative z-20">
                <AddToCartButton
                    :producto-id="product.producto_id"
                    :precio="effectivePrice"
                    :titulo="product.titulo"
                    :disabled="!stockStatus.canBuy"
                    class-name="w-full bg-green-600 hover:bg-green-700 text-white rounded-lg py-2 text-sm font-semibold transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                />
            </div>
        </div>
    </article>

    <Teleport to="body">
        <Transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="quickViewProduct"
                class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm"
                role="dialog"
                aria-modal="true"
                :aria-label="`Vista rápida de ${quickViewProduct.titulo}`"
                @click.self="closeQuickView"
            >
                <Transition
                    appear
                    enter-active-class="transition duration-200 ease-out"
                    enter-from-class="opacity-0 scale-95"
                    enter-to-class="opacity-100 scale-100"
                    leave-active-class="transition duration-150 ease-in"
                    leave-from-class="opacity-100 scale-100"
                    leave-to-class="opacity-0 scale-95"
                >
                    <div
                        class="relative bg-white rounded-2xl shadow-2xl max-w-4xl w-full max-h-[90vh] overflow-hidden flex flex-col"
                    >
                        <button
                            type="button"
                            class="absolute top-3 right-3 z-10 h-9 w-9 rounded-full bg-white/90 backdrop-blur shadow flex items-center justify-center text-gray-700 hover:text-gray-900"
                            aria-label="Cerrar"
                            @click="closeQuickView"
                        >
                            <X class="w-4 h-4" />
                        </button>

                        <div class="grid grid-cols-1 md:grid-cols-2 overflow-hidden flex-1">
                            <div class="bg-gray-50 flex items-center justify-center p-6 min-h-[280px]">
                                <img
                                    v-if="!isQuickViewLoading"
                                    :src="quickViewImages[quickViewImage]"
                                    :alt="quickViewProduct.titulo"
                                    class="max-w-full max-h-[400px] object-contain"
                                />
                                <div v-else class="w-full h-64 bg-gray-200 rounded-lg animate-pulse"></div>
                            </div>

                            <div class="p-6 overflow-y-auto">
                                <p
                                    v-if="quickViewProduct.marca"
                                    class="text-xs font-bold uppercase tracking-wider text-green-600"
                                >
                                    {{ quickViewProduct.marca }}
                                </p>
                                <h2 class="text-xl font-black text-gray-900 mt-1 leading-tight">
                                    {{ quickViewProduct.titulo }}
                                </h2>
                                <p
                                    v-if="quickViewProduct.modelo"
                                    class="text-xs text-gray-500 font-mono mt-1"
                                >
                                    {{ quickViewProduct.modelo }}
                                </p>

                                <div class="flex items-baseline gap-2 mt-4">
                                    <p class="text-3xl font-black text-gray-900">
                                        ${{ quickViewEffectivePrice.toFixed(2) }}
                                    </p>
                                    <p
                                        v-if="quickViewHasDiscount"
                                        class="text-sm text-gray-400 line-through"
                                    >
                                        ${{ quickViewOriginalPrice.toFixed(2) }}
                                    </p>
                                </div>
                                <p class="text-sm mt-1" :class="quickViewCanBuy ? 'text-emerald-600' : 'text-red-600'">
                                    <span class="font-semibold">●</span>
                                    {{ quickViewCanBuy ? `En stock (${quickViewStock})` : 'Agotado' }}
                                </p>

                                <p class="text-sm text-gray-600 mt-3 line-clamp-3">
                                    {{ quickViewDescription }}
                                </p>

                                <ul class="mt-3 space-y-1 text-xs text-gray-600">
                                    <li v-if="quickViewProduct.garantia" class="flex items-center gap-1.5">
                                        <ShieldCheck class="w-3.5 h-3.5 text-green-600" />
                                        Garantía: {{ quickViewProduct.garantia }}
                                    </li>
                                    <li class="flex items-center gap-1.5">
                                        <Truck class="w-3.5 h-3.5 text-green-600" />
                                        Envío gratis sobre $500 USD
                                    </li>
                                    <li v-if="quickViewProduct.categorias?.length" class="flex items-center gap-1.5">
                                        <Tag class="w-3.5 h-3.5 text-green-600" />
                                        <span class="truncate">{{ quickViewProduct.categorias.map((c) => c.nombre).join(', ') }}</span>
                                    </li>
                                </ul>

                                <div v-if="quickViewCanBuy" class="flex items-center gap-2 mt-4">
                                    <div class="flex items-center border border-gray-200 rounded-md">
                                        <button
                                            type="button"
                                            class="h-8 w-8 text-gray-600 hover:bg-gray-50"
                                            @click="quickViewQty = Math.max(1, quickViewQty - 1)"
                                        >−</button>
                                        <input
                                            v-model.number="quickViewQty"
                                            type="number"
                                            min="1"
                                            max="999"
                                            class="w-12 text-center border-x border-gray-200 h-8 focus:outline-none text-sm"
                                        />
                                        <button
                                            type="button"
                                            class="h-8 w-8 text-gray-600 hover:bg-gray-50"
                                            @click="quickViewQty = Math.min(999, quickViewQty + 1)"
                                        >+</button>
                                    </div>
                                </div>

                                <div class="flex flex-col gap-2 mt-4">
                                    <AddToCartButton
                                        v-if="quickViewCanBuy"
                                        :producto-id="quickViewProduct.producto_id"
                                        :precio="quickViewEffectivePrice"
                                        :titulo="quickViewProduct.titulo"
                                        :canBuy="quickViewCanBuy"
                                        class-name="w-full bg-green-600 hover:bg-green-700 text-white py-2.5 rounded-lg font-semibold transition-colors flex items-center justify-center"
                                    />
                                    <Link
                                        :href="`/productos/${quickViewProduct.producto_id}`"
                                        class="text-center text-sm font-medium text-green-600 hover:text-green-700 py-2 flex items-center justify-center gap-1"
                                        @click="closeQuickView"
                                    >
                                        Ver detalle completo
                                        <ChevronRight class="w-4 h-4" />
                                    </Link>
                                </div>
                            </div>
                        </div>
                    </div>
                </Transition>
            </div>
        </Transition>
    </Teleport>
</template>
