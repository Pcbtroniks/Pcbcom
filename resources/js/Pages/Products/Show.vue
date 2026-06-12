<script setup lang="ts">
import { onMounted, ref, computed, watch, onBeforeUnmount } from 'vue';
import { Link } from '@inertiajs/vue3';
import Header from '@/Components/Layout/header.vue';
import TopBar from '@/Components/Layout/top-bar.vue';
import BackToTop from '@/Components/Layout/back-to-top.vue';
import CardProduct from '@/Components/Catalog/CardProduct.vue';
import AddToCartButton from '@/Components/AddToCartButton.vue';
import Breadcrumb from '@/Components/Catalog/Breadcrumb.vue';
import RecentlyViewedCarousel from '@/Components/Catalog/RecentlyViewedCarousel.vue';
import { useRecentlyViewed } from '@/composables/useRecentlyViewed';
import { useCart } from '@/composables/useCart';
import {
    Package, ShieldCheck, Truck, ChevronLeft, ChevronRight,
    ZoomIn, X, FileText, Tag, Layers, Share2, Copy, Check,
} from 'lucide-vue-next';
import axios from 'axios';

interface Props {
    productoId: number | string;
}

const props = defineProps<Props>();

const numericProductoId = computed(() => {
    const id = props.productoId;
    if (typeof id === 'number' && Number.isFinite(id) && id > 0) return id;
    if (typeof id === 'string') {
        const parsed = parseInt(id, 10);
        return Number.isFinite(parsed) && parsed > 0 ? parsed : 0;
    }
    return 0;
});

const product = ref<any | null>(null);
const related = ref<any[]>([]);
const isLoading = ref(true);
const error = ref<string | null>(null);
const currentImage = ref(0);
const quantity = ref(1);
const activeTab = ref<'descripcion' | 'especificaciones' | 'envio'>('descripcion');
const isLightboxOpen = ref(false);
const shareCopied = ref(false);

let abortController: AbortController | null = null;

const { refresh } = useCart();
const { add: addToRecentlyViewed } = useRecentlyViewed();

const effectivePrice = computed(() => {
    if (!product.value?.precios) return 0;
    const p = product.value.precios;
    if (p.precio_especial > 0) return p.precio_especial;
    if (p.precio_descuento > 0) return p.precio_descuento;
    if (p.precio_1 > 0) return p.precio_1;
    return p.precio_lista ?? 0;
});

const originalPrice = computed(() => product.value?.precios?.precio_lista ?? 0);
const hasDiscount = computed(() => originalPrice.value > effectivePrice.value && effectivePrice.value > 0);
const discountPct = computed(() => {
    if (!hasDiscount.value || originalPrice.value === 0) return 0;
    return Math.round((1 - effectivePrice.value / originalPrice.value) * 100);
});

const stockStatus = computed(() => {
    const stock = product.value?.total_existencia ?? 0;
    if (stock <= 0) return { label: 'Agotado', class: 'text-red-600', canBuy: false };
    if (stock <= 5) return { label: `Últimas ${stock} unidades`, class: 'text-amber-600', canBuy: true };
    return { label: 'En stock (+10 disponibles)', class: 'text-emerald-600', canBuy: true };
});

const galleryImages = computed(() => {
    if (!product.value) return [];
    const images: string[] = [];
    if (product.value.img_portada) images.push(product.value.img_portada);
    if (Array.isArray(product.value.imagenes)) {
        const sorted = [...product.value.imagenes].sort((a: any, b: any) => {
            const oa = parseInt(a?.orden ?? '0', 10) || 0;
            const ob = parseInt(b?.orden ?? '0', 10) || 0;
            return oa - ob;
        });
        sorted.forEach((item: any) => {
            const img = item?.imagen;
            if (img && !images.includes(img)) images.push(img);
        });
    }
    if (images.length === 0) {
        images.push(`data:image/svg+xml;utf8,${encodeURIComponent(
            '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 600 600"><rect fill="#f3f4f6" width="600" height="600"/><text x="50%" y="50%" font-family="sans-serif" font-size="24" fill="#9ca3af" text-anchor="middle" dominant-baseline="middle">Sin imagen</text></svg>'
        )}`);
    }
    return images;
});

const especificaciones = computed(() => {
    if (!product.value) return [];
    const specs: { label: string; value: string }[] = [];
    if (product.value.marca) specs.push({ label: 'Marca', value: product.value.marca });
    if (product.value.modelo) specs.push({ label: 'Modelo', value: product.value.modelo });
    if (product.value.garantia) specs.push({ label: 'Garantía', value: product.value.garantia });
    if (product.value.nombre && product.value.nombre !== product.value.titulo) {
        specs.push({ label: 'Nombre comercial', value: product.value.nombre });
    }
    if (product.value.etiqueta) specs.push({ label: 'Etiqueta', value: product.value.etiqueta });
    if (product.value.total_existencia !== undefined) {
        specs.push({ label: 'Existencias', value: `${product.value.total_existencia} unidades` });
    }
    if (product.value.categorias?.length) {
        specs.push({ label: 'Categorías', value: product.value.categorias.map((c: any) => c.nombre).join(', ') });
    }
    return specs;
});

const description = computed(() => {
    if (!product.value) return '';
    return product.value.descripcion ?? product.value.descripcion_larga
        ?? `${product.value.titulo}${product.value.modelo ? ' (modelo ' + product.value.modelo + ')' : ''}. Producto distribuido por Syscom con garantía local PCBtroniks.`;
});

const breadcrumbItems = computed(() => {
    if (!product.value) return [];
    const cat = product.value.categorias?.[0];
    const items: { label: string; href?: string }[] = [];
    items.push({ label: 'Catálogo', href: '/' });
    if (cat) {
        items.push({ label: cat.nombre, href: `/?categoria=${cat.id}` });
    }
    items.push({ label: product.value.titulo ?? 'Producto' });
    return items;
});

function nextImage() {
    if (currentImage.value < galleryImages.value.length - 1) currentImage.value++;
    else currentImage.value = 0;
}
function prevImage() {
    if (currentImage.value > 0) currentImage.value--;
    else currentImage.value = galleryImages.value.length - 1;
}

function openLightbox(idx: number) {
    currentImage.value = idx;
    isLightboxOpen.value = true;
    document.body.style.overflow = 'hidden';
}

function closeLightbox() {
    isLightboxOpen.value = false;
    document.body.style.overflow = '';
}

function onLightboxKeydown(e: KeyboardEvent) {
    if (!isLightboxOpen.value) return;
    if (e.key === 'Escape') closeLightbox();
    if (e.key === 'ArrowRight') nextImage();
    if (e.key === 'ArrowLeft') prevImage();
}

function copyShareLink() {
    if (typeof window === 'undefined') return;
    const url = window.location.href;
    if (navigator.clipboard) {
        navigator.clipboard.writeText(url).then(() => {
            shareCopied.value = true;
            setTimeout(() => (shareCopied.value = false), 2000);
        });
    } else {
        const ta = document.createElement('textarea');
        ta.value = url;
        document.body.appendChild(ta);
        ta.select();
        try { document.execCommand('copy'); shareCopied.value = true; } catch {}
        document.body.removeChild(ta);
        setTimeout(() => (shareCopied.value = false), 2000);
    }
}

async function loadProduct() {
    abortController?.abort();
    abortController = new AbortController();

    isLoading.value = true;
    error.value = null;

    const productId = numericProductoId.value;
    if (productId <= 0) {
        error.value = 'ID de producto inválido.';
        isLoading.value = false;
        return;
    }

    const timeoutId = setTimeout(() => abortController?.abort(), 20000);

    try {
        const { data } = await axios.get(`/api/syscom/products/${productId}`, {
            signal: abortController.signal,
        });
        product.value = data;
        currentImage.value = 0;
        activeTab.value = 'descripcion';

        addToRecentlyViewed({
            producto_id: data.producto_id,
            titulo: data.titulo,
            marca: data.marca,
            img_portada: data.img_portada,
            precio: effectivePrice.value,
        });

        if (data.categorias?.[0]?.id) {
            try {
                const { data: relData } = await axios.get('/api/syscom/products', {
                    params: { categoria: data.categorias[0].id, max: 5 },
                    signal: abortController.signal,
                });
                related.value = (relData.productos ?? []).filter(
                    (p: any) => p.producto_id !== data.producto_id
                ).slice(0, 4);
            } catch {
                related.value = [];
            }
        }
    } catch (e: any) {
        if (e?.name === 'CanceledError' || e?.code === 'ERR_CANCELED') {
            error.value = 'La solicitud fue cancelada o tardó demasiado. Intenta de nuevo.';
        } else {
            error.value = e?.response?.data?.error
                ?? e?.message
                ?? 'No se pudo cargar el producto.';
        }
    } finally {
        clearTimeout(timeoutId);
        isLoading.value = false;
    }
}

watch(() => props.productoId, () => {
    loadProduct();
});

onMounted(() => {
    loadProduct();
    refresh();
    window.addEventListener('keydown', onLightboxKeydown);
});

onBeforeUnmount(() => {
    window.removeEventListener('keydown', onLightboxKeydown);
    document.body.style.overflow = '';
    abortController?.abort();
});
</script>

<template>
    <TopBar />
    <Header />

    <main class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-6">
        <div v-if="isLoading" class="grid grid-cols-1 lg:grid-cols-2 gap-8 animate-pulse">
            <div class="aspect-square bg-gray-100 rounded-2xl"></div>
            <div class="space-y-4">
                <div class="h-6 bg-gray-100 rounded w-1/3"></div>
                <div class="h-8 bg-gray-100 rounded w-3/4"></div>
                <div class="h-4 bg-gray-100 rounded w-1/2"></div>
                <div class="h-12 bg-gray-100 rounded w-1/2 mt-6"></div>
                <div class="h-12 bg-gray-100 rounded w-full"></div>
            </div>
        </div>

        <div v-else-if="error" class="text-center py-16">
            <Package class="w-16 h-16 text-gray-300 mx-auto mb-4" />
            <p class="text-lg text-gray-700">{{ error }}</p>
            <Link href="/" class="text-green-600 hover:underline mt-4 inline-block">Volver al catálogo</Link>
        </div>

        <div v-else-if="product">
            <div class="mb-4">
                <Breadcrumb :items="breadcrumbItems" />
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <div>
                    <div
                        class="relative aspect-square bg-gray-50 rounded-2xl overflow-hidden group cursor-zoom-in"
                        @click="openLightbox(currentImage)"
                    >
                        <img
                            :src="galleryImages[currentImage]"
                            :alt="product.titulo"
                            class="w-full h-full object-contain p-4 transition-transform duration-300 group-hover:scale-105"
                        />
                        <span
                            v-if="product.categorias?.[0]"
                            class="absolute top-3 left-3 px-2 py-1 bg-white/90 backdrop-blur text-[10px] font-bold uppercase tracking-wider rounded text-gray-700"
                        >
                            {{ product.categorias[0].nombre }}
                        </span>
                        <span
                            v-if="hasDiscount"
                            class="absolute top-3 right-3 px-2 py-1 bg-red-600 text-white text-xs font-bold rounded"
                        >
                            -{{ discountPct }}%
                        </span>
                        <div class="absolute bottom-3 right-3 h-9 w-9 rounded-full bg-white/90 backdrop-blur shadow flex items-center justify-center text-gray-600 opacity-0 group-hover:opacity-100 transition-opacity">
                            <ZoomIn class="w-4 h-4" />
                        </div>
                        <button
                            v-if="galleryImages.length > 1"
                            type="button"
                            class="absolute left-3 top-1/2 -translate-y-1/2 h-10 w-10 rounded-full bg-white/90 backdrop-blur shadow-md flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity"
                            @click.stop.prevent="prevImage"
                        >
                            <ChevronLeft class="w-5 h-5" />
                        </button>
                        <button
                            v-if="galleryImages.length > 1"
                            type="button"
                            class="absolute right-3 top-1/2 -translate-y-1/2 h-10 w-10 rounded-full bg-white/90 backdrop-blur shadow-md flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity"
                            @click.stop.prevent="nextImage"
                        >
                            <ChevronRight class="w-5 h-5" />
                        </button>
                    </div>
                    <div v-if="galleryImages.length > 1" class="flex gap-2 mt-3 overflow-x-auto">
                        <button
                            v-for="(img, i) in galleryImages"
                            :key="i"
                            type="button"
                            :class="[
                                'flex-shrink-0 w-16 h-16 rounded-md overflow-hidden border-2 transition-colors',
                                i === currentImage ? 'border-green-500' : 'border-transparent hover:border-gray-300',
                            ]"
                            @click="currentImage = i"
                        >
                            <img :src="img" :alt="`Imagen ${i + 1}`" class="w-full h-full object-cover" />
                        </button>
                    </div>
                </div>

                <div class="space-y-5">
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex-1">
                            <p
                                v-if="product.marca"
                                class="text-xs font-bold uppercase tracking-wider text-green-600"
                            >
                                {{ product.marca }}
                            </p>
                            <h1 class="text-2xl sm:text-3xl font-black text-gray-900 leading-tight mt-1">
                                {{ product.titulo }}
                            </h1>
                            <p
                                v-if="product.modelo"
                                class="text-sm text-gray-500 mt-1"
                            >
                                Modelo: <span class="font-mono">{{ product.modelo }}</span>
                            </p>
                        </div>
                        <button
                            type="button"
                            class="flex-shrink-0 p-2 text-gray-500 hover:text-green-600 border border-gray-200 rounded-md"
                            aria-label="Compartir"
                            @click="copyShareLink"
                        >
                            <Check v-if="shareCopied" class="w-4 h-4 text-green-600" />
                            <Share2 v-else class="w-4 h-4" />
                        </button>
                    </div>

                    <div class="flex items-baseline gap-3 pt-2">
                        <p class="text-4xl font-black text-gray-900">${{ effectivePrice }}</p>
                        <p
                            v-if="hasDiscount"
                            class="text-lg text-gray-400 line-through"
                        >
                            ${{ originalPrice }}
                        </p>
                        <span
                            v-if="hasDiscount"
                            class="px-2 py-0.5 bg-red-600 text-white text-xs font-bold rounded"
                        >
                            AHORRA ${{ (originalPrice - effectivePrice).toFixed(2) }}
                        </span>
                    </div>
                    <p class="text-sm flex items-center gap-2" :class="stockStatus.class">
                        <span class="font-semibold">●</span> {{ stockStatus.label }}
                    </p>

                    <div class="border-t border-gray-200 pt-4 space-y-3">
                        <div v-if="stockStatus.canBuy" class="flex items-center gap-3">
                            <label class="text-sm text-gray-600">Cantidad:</label>
                            <div class="flex items-center border border-gray-200 rounded-md">
                                <button
                                    type="button"
                                    class="h-9 w-9 text-gray-600 hover:bg-gray-50"
                                    :disabled="quantity <= 1"
                                    @click="quantity = Math.max(1, quantity - 1)"
                                >−</button>
                                <input
                                    v-model.number="quantity"
                                    type="number"
                                    min="1"
                                    max="999"
                                    class="w-14 text-center border-x border-gray-200 h-9 focus:outline-none"
                                />
                                <button
                                    type="button"
                                    class="h-9 w-9 text-gray-600 hover:bg-gray-50"
                                    @click="quantity = Math.min(999, quantity + 1)"
                                >+</button>
                            </div>
                        </div>
                        <AddToCartButton
                            v-if="stockStatus.canBuy"
                            :producto-id="product.producto_id"
                            :precio="effectivePrice"
                            :titulo="product.titulo"
                            class-name="w-full bg-green-600 hover:bg-green-700 text-white py-3 rounded-lg font-bold text-base disabled:opacity-50"
                            @click="quantity = 1"
                        />
                        <button
                            v-else
                            disabled
                            class="w-full bg-gray-200 text-gray-500 py-3 rounded-lg font-bold cursor-not-allowed"
                        >
                            Producto agotado
                        </button>
                    </div>

                    <ul class="border-t border-gray-200 pt-4 space-y-2 text-sm">
                        <li v-if="product.garantia" class="flex items-center gap-2 text-gray-700">
                            <ShieldCheck class="w-4 h-4 text-green-600" />
                            Garantía: <span class="font-medium">{{ product.garantia }}</span>
                        </li>
                        <li class="flex items-center gap-2 text-gray-700">
                            <Truck class="w-4 h-4 text-green-600" />
                            Envío a todo México (gratis sobre $500 USD)
                        </li>
                        <li v-if="product.categorias?.length" class="flex items-center gap-2 text-gray-700">
                            <Layers class="w-4 h-4 text-green-600" />
                            <Link
                                v-for="(c, i) in product.categorias"
                                :key="c.id"
                                :href="`/?categoria=${c.id}`"
                                class="text-green-600 hover:underline"
                            >
                                {{ c.nombre }}{{ i < product.categorias.length - 1 ? ', ' : '' }}
                            </Link>
                        </li>
                    </ul>
                </div>
            </div>

            <section class="mt-10">
                <div role="tablist" class="flex border-b border-gray-200 overflow-x-auto">
                    <button
                        v-for="tab in [
                            { key: 'descripcion', label: 'Descripción', icon: FileText },
                            { key: 'envio', label: 'Envío y devoluciones', icon: Truck },
                        ]"
                        :key="tab.key"
                        type="button"
                        role="tab"
                        :aria-selected="activeTab === tab.key"
                        :class="[
                            'flex items-center gap-2 px-4 py-3 text-sm font-medium border-b-2 transition-colors whitespace-nowrap',
                            activeTab === tab.key
                                ? 'border-green-600 text-green-700'
                                : 'border-transparent text-gray-500 hover:text-gray-700',
                        ]"
                        @click="activeTab = tab.key as any"
                    >
                        <component :is="tab.icon" class="w-4 h-4" />
                        {{ tab.label }}
                    </button>
                </div>

                <div class="py-6">
                    <div v-if="activeTab === 'descripcion'" class="prose max-w-none text-gray-700 leading-relaxed" v-html="description"></div>

                    <div v-else-if="activeTab === 'especificaciones'">
                        <dl v-if="specifications.length > 0" class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-2">
                            <template v-for="(spec, i) in specifications" :key="i">
                                <dt class="text-sm font-medium text-gray-500">{{ spec.label }}</dt>
                                <dd class="text-sm text-gray-900">{{ spec.value }}</dd>
                            </template>
                        </dl>
                        <p v-else class="text-sm text-gray-500">No hay especificaciones disponibles.</p>
                    </div>

                    <div v-else class="space-y-3 text-sm text-gray-700">
                        <p><strong class="text-gray-900">Envío estándar:</strong> 3–5 días hábiles a todo México. Gratis en pedidos superiores a $500 USD.</p>
                        <p><strong class="text-gray-900">Envío express:</strong> 1–2 días hábiles en zonas metropolitanas (cotización al confirmar).</p>
                        <p><strong class="text-gray-900">Devoluciones:</strong> 30 días naturales desde la entrega. El producto debe estar en su empaque original sin uso.</p>
                        <p><strong class="text-gray-900">Garantía:</strong> Cobertura directa con PCBtroniks para defectos de fábrica. El plazo depende del fabricante.</p>
                    </div>
                </div>
            </section>

            <section v-if="related.length > 0" class="mt-10">
                <h2 class="text-xl font-bold text-gray-900 mb-4">Productos relacionados</h2>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <CardProduct v-for="p in related" :key="p.producto_id" :product="p" />
                </div>
            </section>

            <RecentlyViewedCarousel
                title="También te puede interesar"
                subtitle="Basado en lo que has visitado recientemente"
                :exclude-id="product.producto_id"
                :show-clear="true"
                :show-view-all="true"
            />
        </div>

        <Teleport to="body">
            <div
                v-if="isLightboxOpen"
                class="fixed inset-0 z-[100] bg-black/95 flex items-center justify-center p-4"
                @click="closeLightbox"
            >
                <button
                    type="button"
                    class="absolute top-4 right-4 h-10 w-10 rounded-full bg-white/10 backdrop-blur text-white flex items-center justify-center hover:bg-white/20"
                    aria-label="Cerrar"
                    @click.stop="closeLightbox"
                >
                    <X class="w-5 h-5" />
                </button>
                <button
                    v-if="galleryImages.length > 1"
                    type="button"
                    class="absolute left-4 top-1/2 -translate-y-1/2 h-12 w-12 rounded-full bg-white/10 backdrop-blur text-white flex items-center justify-center hover:bg-white/20"
                    @click.stop.prevent="prevImage"
                >
                    <ChevronLeft class="w-6 h-6" />
                </button>
                <button
                    v-if="galleryImages.length > 1"
                    type="button"
                    class="absolute right-4 top-1/2 -translate-y-1/2 h-12 w-12 rounded-full bg-white/10 backdrop-blur text-white flex items-center justify-center hover:bg-white/20"
                    @click.stop.prevent="nextImage"
                >
                    <ChevronRight class="w-6 h-6" />
                </button>
                <img
                    :src="galleryImages[currentImage]"
                    :alt="product.titulo"
                    class="max-w-full max-h-full object-contain"
                    @click.stop
                />
                <div
                    v-if="galleryImages.length > 1"
                    class="absolute bottom-4 left-1/2 -translate-x-1/2 px-3 py-1 rounded-full bg-white/10 backdrop-blur text-white text-sm"
                >
                    {{ currentImage + 1 }} / {{ galleryImages.length }}
                </div>
            </div>
        </Teleport>
    </main>

    <BackToTop />
</template>
