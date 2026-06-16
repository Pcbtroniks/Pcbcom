<script setup lang="ts">
import { ref, computed, watch, onMounted } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import Header from '@/Components/Layout/header.vue';
import TopBar from '@/Components/Layout/top-bar.vue';
import QuotationRequest from '@/Components/quotation-request.vue';
import BackToTop from '@/Components/Layout/back-to-top.vue';
import Footer from '@/Components/Layout/footer.vue';
import SkeletonCardProduct from '@/Components/Skeletons/skeleton-card-product.vue';
import CategorySidebar from '@/Components/Catalog/CategorySidebar.vue';
import ProductFilters from '@/Components/Catalog/ProductFilters.vue';
import Breadcrumb from '@/Components/Catalog/Breadcrumb.vue';
import SearchBar from '@/Components/Catalog/SearchBar.vue';
import CardProduct from '@/Components/Catalog/CardProduct.vue';
import RecentlyViewedCarousel from '@/Components/Catalog/RecentlyViewedCarousel.vue';
import { useCatalog } from '@/composables/useCatalog';
import { useCart } from '@/composables/useCart';
import { ChevronLeft, ChevronRight, Package, X } from 'lucide-vue-next';
import type { Category } from '@/types/Categories';
import type { CatalogFilters } from '@/types/Filters';

const page = usePage<{
    initialCategoria?: number;
    initialBusqueda?: string;
}>();

const { refresh } = useCart();

const initial: CatalogFilters = {
    categoria: page.props.initialCategoria,
    busqueda: page.props.initialBusqueda,
};

const {
    products,
    productResponse,
    categories,
    breadcrumb,
    brands,
    filters,
    isLoading,
    error,
    totalProducts,
    currentPage,
    totalPages,
    hasProducts,
    goToPage,
    setCategoria,
    updateFilters,
} = useCatalog(initial);

const expanded = ref<Record<number, boolean>>({});
const sidebarOpen = ref(false);
const showMobileFilters = ref(false);

const parentChain = computed<number[]>(() => breadcrumb.value.slice(0, -1).map((c) => c.id));

const breadcrumbItems = computed(() => {
    const items: { label: string; href?: string }[] = [];
    if (filters.value.busqueda) {
        items.push({ label: `Resultados para "${filters.value.busqueda}"` });
    } else if (filters.value.categoria) {
        breadcrumb.value.forEach((c, i) => {
            const isLast = i === breadcrumb.value.length - 1;
            items.push({
                label: c.nombre,
                href: isLast ? undefined : `/?categoria=${c.id}`,
            });
        });
    } else {
        items.push({ label: 'Todos los productos' });
    }
    return items;
});

const visiblePages = computed(() => {
    const total = totalPages.value;
    const current = currentPage.value;
    const pages: (number | '…')[] = [];
    if (total <= 7) {
        for (let i = 1; i <= total; i++) pages.push(i);
        return pages;
    }
    pages.push(1);
    if (current > 3) pages.push('…');
    for (let i = Math.max(2, current - 1); i <= Math.min(total - 1, current + 1); i++) {
        pages.push(i);
    }
    if (current < total - 2) pages.push('…');
    pages.push(total);
    return pages;
});

onMounted(() => {
    refresh();
});

const title = computed(() => {
    if (filters.value.busqueda) return `Resultados para "${filters.value.busqueda}"`;
    if (breadcrumb.value.length > 0) return breadcrumb.value[breadcrumb.value.length - 1].nombre;
    return 'Todos los productos';
});

const description = computed(() => {
    return 'Equipos listos para tu negocio, con soporte técnico y garantía local.';
});

</script>

<template>
    <TopBar />
    <Header />

    <main id="main" class="mx-auto">
        <section class="bg-gradient-to-br from-green-50 to-white border-b border-green-100">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
                <div class="text-center space-y-4">
                    <p class="text-gray-600 max-w-2xl mx-auto">{{ description }}</p>
                    <div class="max-w-2xl mx-auto pt-2">
                        <SearchBar
                            :model-value="filters.busqueda"
                            :show-results="true"
                            placeholder="Buscar productos por nombre, marca o modelo…"
                            size="lg"
                            :autofocus="!!filters.busqueda"
                        />
                    </div>
                </div>
            </div>
        </section>

        <section class="max-w-7xl 2xl:max-w-full mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <div class="mb-4">
                <Breadcrumb :items="breadcrumbItems" />
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-[280px_1fr] gap-6">
                <aside class="hidden lg:block">
                    <div class="sticky top-24 space-y-6">
                      <div class="overflow-y-auto h-96 2xl:h-full">
                        <CategorySidebar
                            v-model:expanded="expanded"
                            :roots="categories"
                            :current-category-id="filters.categoria"
                            :parent-chain="parentChain"
                        />
                        </div>
                        <div class="border-t border-gray-200 pt-4">
                            <ProductFilters
                                :model-value="filters"
                                :brands="brands"
                                @update:model-value="updateFilters"
                            />
                        </div>
                    </div>
                </aside>

                <div>
                    <div class="flex items-center justify-between mb-4 flex-wrap gap-2">
                        <p class="text-sm text-gray-600">
                            <span v-if="isLoading" class="inline-block w-24 h-4 bg-gray-200 rounded animate-pulse"></span>
                            <span v-else>
                                <strong class="text-gray-900">{{ totalProducts }}</strong>
                                {{ totalProducts === 1 ? 'producto' : 'productos' }}
                            </span>
                        </p>

                        <div class="flex items-center gap-2">
                            <button
                                type="button"
                                class="lg:hidden flex items-center gap-1 px-3 py-1.5 text-sm border border-gray-200 rounded-md hover:bg-gray-50"
                                @click="showMobileFilters = !showMobileFilters"
                            >
                                Filtros
                            </button>
                        </div>
                    </div>

                    <div v-if="showMobileFilters" class="lg:hidden mb-4 p-4 bg-gray-50 rounded-lg space-y-4">
                        <CategorySidebar
                            v-model:expanded="expanded"
                            :roots="categories"
                            :current-category-id="filters.categoria"
                            :parent-chain="parentChain"
                        />
                        <div class="border-t border-gray-200 pt-4">
                            <ProductFilters
                                :model-value="filters"
                                :brands="brands"
                                @update:model-value="updateFilters"
                            />
                        </div>
                    </div>

                    <div v-if="error" class="bg-red-50 border border-red-200 text-red-800 rounded-lg p-4 mb-4">
                        {{ error }}
                    </div>

                    <div v-if="isLoading" class="grid grid-cols-2 sm:grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-4">
                        <SkeletonCardProduct v-for="n in 12" :key="n" />
                    </div>

                    <div
                        v-else-if="!hasProducts"
                        class="bg-white border-2 border-dashed border-gray-200 rounded-2xl p-12 text-center"
                    >
                        <Package class="w-16 h-16 text-gray-300 mx-auto mb-4" />
                        <h3 class="text-lg font-semibold text-gray-700 mb-2">No encontramos productos</h3>
                        <p class="text-sm text-gray-500 mb-4">
                            Intenta ajustar los filtros o explora otras categorías.
                        </p>
                        <button
                            type="button"
                            class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700"
                            @click="updateFilters({})"
                        >
                            Limpiar filtros
                        </button>
                    </div>

                    <div
                        v-else
                        :key="JSON.stringify(filters)"
                        class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 2xl:grid-cols-5 gap-4"
                    >
                        <CardProduct
                            v-for="p in products"
                            :key="p.producto_id"
                            :product="p"
                        />
                    </div>

                    <nav
                        v-if="!isLoading && totalPages > 1"
                        class="mt-8 flex items-center justify-center gap-1"
                        aria-label="Paginación"
                    >
                        <button
                            type="button"
                            :disabled="currentPage === 1"
                            class="h-9 w-9 flex items-center justify-center rounded-md border border-gray-200 disabled:opacity-40 hover:bg-gray-50 transition-colors"
                            @click="goToPage(currentPage - 1)"
                            aria-label="Anterior"
                        >
                            <ChevronLeft class="w-4 h-4" />
                        </button>

                        <template v-for="(p, i) in visiblePages" :key="i">
                            <span
                                v-if="p === '…'"
                                class="h-9 w-9 flex items-center justify-center text-gray-400"
                            >
                                …
                            </span>
                            <button
                                v-else
                                type="button"
                                :class="[
                                    'h-9 min-w-9 px-2 rounded-md text-sm font-medium transition-colors',
                                    p === currentPage
                                        ? 'bg-green-600 text-white'
                                        : 'border border-gray-200 hover:bg-gray-50 text-gray-700',
                                ]"
                                @click="goToPage(p)"
                            >
                                {{ p }}
                            </button>
                        </template>

                        <button
                            type="button"
                            :disabled="currentPage === totalPages"
                            class="h-9 w-9 flex items-center justify-center rounded-md border border-gray-200 disabled:opacity-40 hover:bg-gray-50 transition-colors"
                            @click="goToPage(currentPage + 1)"
                            aria-label="Siguiente"
                        >
                            <ChevronRight class="w-4 h-4" />
                        </button>
                    </nav>
                </div>
            </div>
        </section>

        <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <RecentlyViewedCarousel
                title="Vistos recientemente"
                subtitle="Continúa explorando donde lo dejaste"
                :show-clear="true"
                :show-view-all="true"
            />
        </section>

        <QuotationRequest />
        <BackToTop />
    </main>
    <Footer />
</template>
