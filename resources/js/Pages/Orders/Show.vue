<script setup lang="ts">
import { ref, onMounted, computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import Header from '@/Components/Layout/header.vue';
import TopBar from '@/Components/Layout/top-bar.vue';
import BackToTop from '@/Components/Layout/back-to-top.vue';
import { useCart } from '@/composables/useCart';

const page = usePage<{ orderNumber?: string }>();
const { fetchOrder, isLoading, error } = useCart();

const order = ref<any>(null);

const number = computed(() => {
    const fromUrl = window.location.pathname.split('/').pop();
    return fromUrl ?? '';
});

function statusColor(status: string): string {
    return {
        pending: 'bg-yellow-100 text-yellow-800',
        paid: 'bg-green-100 text-green-800',
        processing: 'bg-blue-100 text-blue-800',
        shipped: 'bg-indigo-100 text-indigo-800',
        delivered: 'bg-emerald-100 text-emerald-800',
        cancelled: 'bg-red-100 text-red-800',
        refunded: 'bg-gray-200 text-gray-700',
    }[status] ?? 'bg-gray-100 text-gray-700';
}

function format(amount: number): string {
    return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(amount);
}

onMounted(async () => {
    try {
        order.value = await fetchOrder(number.value);
    } catch {
    }
});
</script>

<template>
    <TopBar />
    <Header />

    <main class="mx-auto p-8 max-w-3xl">
        <div v-if="isLoading && !order" class="py-16 text-center text-gray-500">Cargando pedido…</div>

        <div v-else-if="error" class="py-16 text-center text-red-500">{{ error }}</div>

        <div v-else-if="order" class="space-y-6">
            <header class="text-center space-y-2">
                <p class="text-sm text-gray-500">Pedido confirmado</p>
                <h1 class="text-3xl font-black">{{ order.number }}</h1>
                <span :class="['inline-block px-3 py-1 rounded-full text-xs font-semibold uppercase', statusColor(order.status)]">
                    {{ order.status }}
                </span>
            </header>

            <section class="border rounded-2xl p-6 bg-white">
                <h2 class="text-lg font-bold mb-4">Artículos</h2>
                <ul class="divide-y">
                    <li v-for="item in order.items" :key="item.id" class="py-3 flex justify-between gap-2">
                        <div>
                            <p class="font-semibold">{{ item.titulo }}</p>
                            <p class="text-xs text-gray-500">{{ item.marca }} · {{ item.modelo }}</p>
                            <p class="text-sm text-gray-700">Cantidad: {{ item.qty }}</p>
                        </div>
                        <div class="text-right">
                            <p class="font-bold">{{ format(item.line_total) }}</p>
                            <p class="text-xs text-gray-500">{{ format(item.unit_price) }} c/u</p>
                        </div>
                    </li>
                </ul>
            </section>

            <section class="border rounded-2xl p-6 bg-white">
                <h2 class="text-lg font-bold mb-4">Totales</h2>
                <dl class="space-y-1 text-sm">
                    <div class="flex justify-between"><dt>Subtotal</dt><dd>{{ format(order.subtotal) }}</dd></div>
                    <div class="flex justify-between"><dt>Envío</dt><dd>{{ format(order.shipping) }}</dd></div>
                    <div class="flex justify-between"><dt>Impuestos</dt><dd>{{ format(order.tax) }}</dd></div>
                    <div class="flex justify-between font-bold text-base pt-2 border-t">
                        <dt>Total</dt><dd>{{ format(order.total) }}</dd>
                    </div>
                </dl>
            </section>

            <section class="border rounded-2xl p-6 bg-white">
                <h2 class="text-lg font-bold mb-4">Envío a</h2>
                <p class="text-sm text-gray-700 whitespace-pre-line">{{ order.shipping_address?.name }}<br />
                    {{ order.shipping_address?.line1 }}<br />
                    <template v-if="order.shipping_address?.line2">{{ order.shipping_address.line2 }}<br /></template>
                    {{ order.shipping_address?.city }}, {{ order.shipping_address?.state }} {{ order.shipping_address?.zip }}<br />
                    {{ order.shipping_address?.country }}
                </p>
            </section>

            <div class="text-center text-sm text-gray-500">
                <p v-if="order.payment_status">Pago: <span class="font-semibold">{{ order.payment_status }}</span></p>
                <p v-if="order.tracking_number">Guía: <span class="font-mono">{{ order.tracking_number }}</span></p>
                <a href="/" class="mt-4 inline-block text-blue-600 hover:underline">Seguir comprando</a>
            </div>
        </div>
    </main>

    <BackToTop />
</template>
