import { ref, computed, watch, onMounted } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import type { Product, ProductsResponse } from '@/types/Products';
import type { Category, CategoryBreadcrumb } from '@/types/Categories';
import type { CatalogFilters } from '@/types/Filters';

export function useCatalog(initialFilters: CatalogFilters = {}) {
    const products = ref<Product[]>([]);
    const productResponse = ref<ProductsResponse | null>(null);
    const categories = ref<Category[]>([]);
    const breadcrumb = ref<CategoryBreadcrumb[]>([]);
    const brands = ref<{ id: number; nombre: string }[]>([]);
    const isLoading = ref(true);
    const error = ref<string | null>(null);

    const filters = ref<CatalogFilters>({ ...initialFilters });

    const totalProducts = computed(() => productResponse.value?.cantidad ?? 0);
    const currentPage = computed(() => productResponse.value?.pagina ?? 1);
    const totalPages = computed(() => productResponse.value?.paginas ?? 1);
    const hasProducts = computed(() => products.value.length > 0);

    const page = usePage();

    function syncFromUrl() {
        const url = new URL(window.location.href);
        const f: CatalogFilters = {};
        const cat = url.searchParams.get('categoria');
        if (cat) f.categoria = Number(cat);
        const marca = url.searchParams.get('marca');
        if (marca) f.marca = marca;
        const busqueda = url.searchParams.get('busqueda');
        if (busqueda) f.busqueda = busqueda;
        const stock = url.searchParams.get('stock');
        if (stock) f.stock = stock as any;
        const pmin = url.searchParams.get('precio_min');
        if (pmin) f.precio_min = Number(pmin);
        const pmax = url.searchParams.get('precio_max');
        if (pmax) f.precio_max = Number(pmax);
        const orden = url.searchParams.get('orden');
        if (orden) f.orden = orden as any;
        const pagina = url.searchParams.get('pagina');
        if (pagina) f.pagina = Number(pagina);
        filters.value = f;
    }

    function pushToUrl() {
        const url = new URL(window.location.href);
        const keys: (keyof CatalogFilters)[] = ['categoria', 'marca', 'busqueda', 'stock', 'precio_min', 'precio_max', 'orden', 'pagina'];
        keys.forEach((k) => url.searchParams.delete(k));
        Object.entries(filters.value).forEach(([k, v]) => {
            if (v !== undefined && v !== null && v !== '' && !(k === 'pagina' && Number(v) === 1) && !(k === 'orden' && v === 'recientes')) {
                url.searchParams.set(k, String(v));
            }
        });
        router.visit(url.pathname + url.search, { preserveScroll: true, preserveState: true, replace: true });
    }

    async function fetchProducts() {
        isLoading.value = true;
        error.value = null;
        try {
            const { data } = await axios.get<ProductsResponse>('/api/syscom/products', {
                params: filters.value,
            });
            productResponse.value = data;
            products.value = data.productos ?? [];
        } catch (e: any) {
            if (e?.response?.status !== 401) {
                error.value = e?.response?.data?.error ?? 'No se pudieron cargar los productos.';
            }
            products.value = [];
        } finally {
            isLoading.value = false;
        }
    }

    async function fetchCategories() {
        try {
            const { data } = await axios.get<{ data: Category[] }>('/api/syscom/categories/tree');
            categories.value = data.data ?? [];
        } catch {
            categories.value = [];
        }
    }

    async function fetchBreadcrumb() {
        if (!filters.value.categoria) {
            breadcrumb.value = [];
            return;
        }
        try {
            const { data } = await axios.get<{ data: CategoryBreadcrumb[] }>(`/api/syscom/categories/${filters.value.categoria}/path`);
            breadcrumb.value = data.data ?? [];
        } catch {
            breadcrumb.value = [];
        }
    }

    async function fetchBrands() {
        try {
            const params: any = {};
            if (filters.value.categoria) params.categoria = filters.value.categoria;
            const { data } = await axios.get<{ data: { id: number; nombre: string }[] }>('/api/syscom/products/brands', { params });
            brands.value = data.data ?? [];
        } catch {
            brands.value = [];
        }
    }

    function setCategoria(id: number | null) {
        filters.value = { ...filters.value, categoria: id ?? undefined, pagina: 1 };
        pushToUrl();
    }

    function updateFilters(f: CatalogFilters) {
        filters.value = { ...f, pagina: 1 };
        pushToUrl();
    }

    function goToPage(p: number) {
        if (p < 1 || p > totalPages.value) return;
        filters.value = { ...filters.value, pagina: p };
        pushToUrl();
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    function reset() {
        filters.value = {};
        pushToUrl();
    }

    watch(
        () => JSON.stringify(filters.value),
        () => {
            fetchProducts();
            fetchBreadcrumb();
            fetchBrands();
        },
        { deep: true }
    );

    watch(
        () => page.url,
        () => {
            syncFromUrl();
        }
    );

    onMounted(async () => {
        syncFromUrl();
        await Promise.all([fetchCategories(), fetchProducts(), fetchBreadcrumb(), fetchBrands()]);
    });

    return {
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
        fetchProducts,
        fetchCategories,
        fetchBreadcrumb,
        fetchBrands,
        setCategoria,
        updateFilters,
        goToPage,
        reset,
    };
}
