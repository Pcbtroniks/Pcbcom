import { ref, computed, readonly } from 'vue';
import axios from 'axios';

export interface CartItem {
    id: number;
    producto_id: number;
    sku: string | null;
    titulo: string;
    modelo: string | null;
    marca: string | null;
    img_portada: string | null;
    unit_price: number;
    qty: number;
    line_total: number;
    notes: string | null;
}

export interface Cart {
    id: number;
    status: string;
    currency: string;
    subtotal: number;
    shipping: number;
    tax: number;
    total: number;
    item_count: number;
    last_synced_at: string | null;
    items: CartItem[];
}

const cart = ref<Cart | null>(null);
const isLoading = ref(false);
const error = ref<string | null>(null);
const isMutating = ref(false);

function readCookie(name: string): string | null {
    const match = document.cookie.match(new RegExp('(?:^|; )' + name + '=([^;]*)'));
    return match ? decodeURIComponent(match[1]) : null;
}

function writeCookie(name: string, value: string, days: number = 30): void {
    const expires = new Date(Date.now() + days * 86400000).toUTCString();
    document.cookie = `${name}=${encodeURIComponent(value)}; expires=${expires}; path=/; SameSite=Lax`;
}

const api = axios.create();

api.interceptors.request.use((config) => {
    const token = readCookie('cart_token');
    if (token) {
        config.headers = config.headers ?? {};
        (config.headers as any)['X-Cart-Token'] = token;
    }
    return config;
});

api.interceptors.response.use((response) => {
    const setCookie = response.headers?.['set-cookie'];
    if (setCookie) {
        const cookies = Array.isArray(setCookie) ? setCookie : [setCookie];
        for (const raw of cookies) {
            const [pair] = raw.split(';');
            const [name, ...rest] = pair.split('=');
            if (name.trim() === 'cart_token') {
                writeCookie('cart_token', rest.join('=').trim());
            }
        }
    }
    return response;
});

function setCart(payload: Cart | null) {
    cart.value = payload;
}

function setError(message: string | null) {
    error.value = message;
}

async function refresh(): Promise<void> {
    isLoading.value = true;
    error.value = null;
    try {
        const { data } = await api.get<{ data: Cart }>('/api/cart');
        cart.value = data.data;
    } catch (e: any) {
        if (e?.response?.status !== 401) {
            error.value = e?.response?.data?.error ?? 'No se pudo cargar el carrito';
        }
        cart.value = null;
    } finally {
        isLoading.value = false;
    }
}

async function addItem(productoId: number, qty: number = 1, unitPrice?: number): Promise<void> {
    isMutating.value = true;
    error.value = null;
    try {
        const { data } = await api.post<{ data: Cart }>('/api/cart/items', {
            producto_id: productoId,
            qty,
            unit_price: unitPrice,
        });
        cart.value = data.data;
    } catch (e: any) {
        error.value = e?.response?.data?.error ?? 'No se pudo agregar al carrito';
        throw e;
    } finally {
        isMutating.value = false;
    }
}

async function updateQty(itemId: number, qty: number): Promise<void> {
    isMutating.value = true;
    try {
        const { data } = await api.patch<{ data: Cart }>(`/api/cart/items/${itemId}`, { qty });
        cart.value = data.data;
    } catch (e: any) {
        error.value = e?.response?.data?.error ?? 'No se pudo actualizar la cantidad';
        throw e;
    } finally {
        isMutating.value = false;
    }
}

async function removeItem(itemId: number): Promise<void> {
    isMutating.value = true;
    try {
        const { data } = await api.delete<{ data: Cart }>(`/api/cart/items/${itemId}`);
        cart.value = data.data;
    } catch (e: any) {
        error.value = e?.response?.data?.error ?? 'No se pudo eliminar el producto';
        throw e;
    } finally {
        isMutating.value = false;
    }
}

async function clear(): Promise<void> {
    isMutating.value = true;
    try {
        const { data } = await api.delete<{ data: Cart }>('/api/cart');
        cart.value = data.data;
    } catch (e: any) {
        error.value = e?.response?.data?.error ?? 'No se pudo vaciar el carrito';
        throw e;
    } finally {
        isMutating.value = false;
    }
}

const itemCount = computed(() => cart.value?.item_count ?? 0);
const isEmpty = computed(() => itemCount.value === 0);

export interface OrderSummary {
    id: number;
    number: string;
    status: string;
    currency: string;
    subtotal: number;
    shipping: number;
    tax: number;
    total: number;
    payment_method: string | null;
    payment_status: string | null;
    shipping_address: Record<string, any>;
    tracking_number: string | null;
    placed_at: string | null;
    paid_at: string | null;
    items: CartItem[];
}

async function previewCheckout(): Promise<void> {
    isMutating.value = true;
    try {
        const { data } = await api.get<{ data: Cart }>('/api/checkout/preview');
        cart.value = data.data;
    } catch (e: any) {
        error.value = e?.response?.data?.error ?? 'No se pudo obtener el preview del checkout';
        throw e;
    } finally {
        isMutating.value = false;
    }
}

async function confirmCheckout(payload: {
    shipping_address: Record<string, any>;
    billing_address?: Record<string, any> | null;
    payment_method?: string;
    notes?: string;
    idempotency_key?: string;
}): Promise<OrderSummary> {
    isMutating.value = true;
    error.value = null;
    try {
        const idempotencyKey = payload.idempotency_key ?? `ck_${Date.now()}_${Math.random().toString(36).slice(2)}`;
        const { data } = await api.post<{ data: OrderSummary }>('/api/checkout/confirm', {
            ...payload,
            idempotency_key: idempotencyKey,
        });
        cart.value = null;
        return data.data;
    } catch (e: any) {
        error.value = e?.response?.data?.error ?? 'No se pudo confirmar el pedido';
        throw e;
    } finally {
        isMutating.value = false;
    }
}

async function fetchOrder(number: string): Promise<OrderSummary> {
    isLoading.value = true;
    error.value = null;
    try {
        const { data } = await api.get<{ data: OrderSummary }>(`/api/checkout/orders/${number}`);
        return data.data;
    } catch (e: any) {
        error.value = e?.response?.data?.error ?? 'No se pudo cargar el pedido';
        throw e;
    } finally {
        isLoading.value = false;
    }
}

export function useCart() {
    return {
        cart: readonly(cart),
        isLoading: readonly(isLoading),
        isMutating: readonly(isMutating),
        error: readonly(error),
        itemCount,
        isEmpty,
        refresh,
        addItem,
        updateQty,
        removeItem,
        clear,
        previewCheckout,
        confirmCheckout,
        fetchOrder,
        setCart,
        setError,
    };
}
