<script setup lang="ts">
import { onMounted, computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import Header from '@/Components/Layout/header.vue';
import TopBar from '@/Components/Layout/top-bar.vue';
import BackToTop from '@/Components/Layout/back-to-top.vue';
import { useCart } from '@/composables/useCart';

const { cart, isLoading, error, itemCount, isEmpty, refresh, updateQty, removeItem, clear } = useCart();

onMounted(() => {
    refresh();
});

const subtotal = computed(() => cart.value?.subtotal ?? 0);
const shipping = computed(() => cart.value?.shipping ?? 0);
const tax = computed(() => cart.value?.tax ?? 0);
const total = computed(() => cart.value?.total ?? 0);

function format(amount: number): string {
    return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(amount);
}

async function onQtyChange(itemId: number, value: number) {
    if (Number.isNaN(value) || value < 0) return;
    await updateQty(itemId, value);
}

async function onRemove(itemId: number) {
    await removeItem(itemId);
}

async function onClear() {
    if (!confirm('¿Vaciar el carrito completo?')) return;
    await clear();
}
</script>

<template>
    <TopBar />
    <Header />

    <main class="mx-auto p-8 max-w-6xl">
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-3xl font-black">Tu carrito</h1>
            <Link href="/" class="text-sm text-blue-600 hover:underline">Seguir comprando</Link>
        </div>

        <div v-if="isLoading && !cart" class="py-16 text-center text-gray-500">Cargando carrito…</div>

        <div v-else-if="error" class="py-16 text-center text-red-500">{{ error }}</div>

        <div v-else-if="isEmpty" class="py-16 text-center text-gray-500">
            <p class="text-lg">Tu carrito está vacío.</p>
            <Link href="/" class="mt-4 inline-block text-blue-600 hover:underline">Ver productos</Link>
        </div>

        <div v-else class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <section class="lg:col-span-2 space-y-4">
                <div
                    v-for="item in cart?.items"
                    :key="item.id"
                    class="flex gap-4 border rounded-2xl p-4 bg-white"
                >
                    <img
                        :src="item.img_portada ?? 'https://placehold.co/120x120?text=Sin+imagen'"
                        :alt="item.titulo"
                        class="h-24 w-24 object-cover rounded-md bg-gray-100"
                    />
                    <div class="flex-1 min-w-0">
                        <h3 class="font-semibold text-gray-800 line-clamp-2">{{ item.titulo }}</h3>
                        <p v-if="item.marca" class="text-xs text-green-600 font-bold uppercase mt-1">{{ item.marca }}</p>
                        <p v-if="item.modelo" class="text-xs text-gray-500">Modelo: {{ item.modelo }}</p>
                        <p class="text-sm text-gray-700 mt-2">Unitario: {{ format(item.unit_price) }}</p>
                    </div>
                    <div class="flex flex-col items-end justify-between gap-2">
                        <button
                            type="button"
                            class="text-xs text-red-500 hover:underline"
                            @click="onRemove(item.id)"
                        >
                            Quitar
                        </button>
                        <div class="flex items-center gap-2">
                            <button
                                type="button"
                                class="h-8 w-8 rounded border text-gray-700 hover:bg-gray-100"
                                @click="onQtyChange(item.id, item.qty - 1)"
                            >-</button>
                            <input
                                type="number"
                                min="0"
                                :value="item.qty"
                                @change="(e) => onQtyChange(item.id, Number((e.target as HTMLInputElement).value))"
                                class="w-14 text-center border rounded h-8"
                            />
                            <button
                                type="button"
                                class="h-8 w-8 rounded border text-gray-700 hover:bg-gray-100"
                                @click="onQtyChange(item.id, item.qty + 1)"
                            >+</button>
                        </div>
                        <p class="font-bold">{{ format(item.line_total) }}</p>
                    </div>
                </div>

                <button
                    type="button"
                    class="text-sm text-gray-500 hover:underline"
                    @click="onClear"
                >Vaciar carrito</button>
            </section>

            <aside class="border rounded-2xl p-6 bg-white h-fit">
                <h2 class="text-lg font-bold mb-4">Resumen</h2>
                <dl class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <dt>Subtotal ({{ itemCount }} ítems)</dt>
                        <dd>{{ format(subtotal) }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt>Envío</dt>
                        <dd>{{ format(shipping) }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt>Impuestos</dt>
                        <dd>{{ format(tax) }}</dd>
                    </div>
                    <div class="flex justify-between font-bold text-base pt-2 border-t">
                        <dt>Total</dt>
                        <dd>{{ format(total) }}</dd>
                    </div>
                </dl>

                <button
                    type="button"
                    class="mt-6 w-full bg-green-600 text-white py-3 rounded-lg font-bold hover:bg-green-700 transition-colors"
                    @click="$inertia.visit('/checkout')"
                >
                    Proceder al checkout
                </button>
            </aside>
        </div>
    </main>

    <BackToTop />
</template>
