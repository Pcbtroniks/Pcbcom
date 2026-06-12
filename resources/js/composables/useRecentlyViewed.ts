import { ref, computed } from 'vue';

interface RecentItem {
    producto_id: number;
    titulo: string;
    marca: string;
    img_portada: string | null;
    precio: number;
    viewed_at: number;
}

const STORAGE_KEY = 'pcbecom:recently_viewed';
const MAX_ITEMS = 12;

function read(): RecentItem[] {
    if (typeof window === 'undefined') return [];
    try {
        const raw = window.localStorage.getItem(STORAGE_KEY);
        if (!raw) return [];
        const parsed = JSON.parse(raw);
        return Array.isArray(parsed) ? parsed : [];
    } catch {
        return [];
    }
}

function write(items: RecentItem[]) {
    if (typeof window === 'undefined') return;
    try {
        window.localStorage.setItem(STORAGE_KEY, JSON.stringify(items.slice(0, MAX_ITEMS)));
    } catch {
    }
}

const items = ref<RecentItem[]>(read());

export function useRecentlyViewed() {
    const recent = computed(() => items.value);

    function add(p: {
        producto_id: number;
        titulo: string;
        marca?: string;
        img_portada?: string | null;
        precio?: number;
    }) {
        const filtered = items.value.filter((i) => i.producto_id !== p.producto_id);
        filtered.unshift({
            producto_id: p.producto_id,
            titulo: p.titulo,
            marca: p.marca ?? '',
            img_portada: p.img_portada ?? null,
            precio: p.precio ?? 0,
            viewed_at: Date.now(),
        });
        items.value = filtered.slice(0, MAX_ITEMS);
        write(items.value);
    }

    function remove(productoId: number) {
        items.value = items.value.filter((i) => i.producto_id !== productoId);
        write(items.value);
    }

    function clear() {
        items.value = [];
        write([]);
    }

    return { recent, add, remove, clear };
}
