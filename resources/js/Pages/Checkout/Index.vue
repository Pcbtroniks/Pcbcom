<script setup lang="ts">
import { computed, reactive, ref } from 'vue';
import { router } from '@inertiajs/vue3';
import Header from '@/Components/Layout/header.vue';
import TopBar from '@/Components/Layout/top-bar.vue';
import BackToTop from '@/Components/Layout/back-to-top.vue';
import { useCart } from '@/composables/useCart';

const { cart, isLoading, isMutating, error, previewCheckout, confirmCheckout } = useCart();

const isEmpty = computed(() => !cart.value || cart.value.items.length === 0);

const form = reactive({
    shipping_address: {
        name: '',
        phone: '',
        line1: '',
        line2: '',
        city: '',
        state: '',
        zip: '',
        country: 'MX',
        notes: '',
    },
    billing_address: null as Record<string, any> | null,
    useBillingDifferent: false,
    payment_method: 'null',
    notes: '',
});

const paymentMethods = [
    { value: 'null', label: 'Pago en mostrador / confirmación manual' },
    { value: 'transfer', label: 'Transferencia bancaria (SPEI)' },
    { value: 'stripe', label: 'Tarjeta de crédito/débito (Stripe)' },
];

const subtotal = computed(() => cart.value?.subtotal ?? 0);
const shipping = computed(() => cart.value?.shipping ?? 0);
const tax = computed(() => cart.value?.tax ?? 0);
const total = computed(() => cart.value?.total ?? 0);

function format(amount: number): string {
    return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(amount);
}

async function recompute() {
    try {
        await previewCheckout();
    } catch {
    }
}

async function submit() {
    const payload: any = {
        shipping_address: { ...form.shipping_address },
        payment_method: form.payment_method,
        notes: form.notes || undefined,
    };
    if (form.useBillingDifferent && form.billing_address) {
        payload.billing_address = form.billing_address;
    }
    try {
        const order = await confirmCheckout(payload);
        router.visit(`/orders/${order.number}`);
    } catch {
    }
}

import { onMounted } from 'vue';
onMounted(() => {
    recompute();
});
</script>

<template>
    <TopBar />
    <Header />

    <main class="mx-auto p-8 max-w-6xl">
        <h1 class="text-3xl font-black mb-6">Finalizar compra</h1>

        <div v-if="isLoading && !cart" class="py-16 text-center text-gray-500">Cargando…</div>

        <div v-else-if="isEmpty" class="py-16 text-center text-gray-500">
            <p class="text-lg">Tu carrito está vacío.</p>
            <a href="/" class="mt-4 inline-block text-blue-600 hover:underline">Ver productos</a>
        </div>

        <div v-else class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <form @submit.prevent="submit" class="lg:col-span-2 space-y-6">
                <section class="border rounded-2xl p-6 bg-white">
                    <h2 class="text-lg font-bold mb-4">Dirección de envío</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <label class="block">
                            <span class="text-sm text-gray-700">Nombre completo *</span>
                            <input v-model="form.shipping_address.name" required class="mt-1 w-full border rounded p-2" />
                        </label>
                        <label class="block">
                            <span class="text-sm text-gray-700">Teléfono *</span>
                            <input v-model="form.shipping_address.phone" required class="mt-1 w-full border rounded p-2" />
                        </label>
                        <label class="block md:col-span-2">
                            <span class="text-sm text-gray-700">Calle y número *</span>
                            <input v-model="form.shipping_address.line1" required class="mt-1 w-full border rounded p-2" />
                        </label>
                        <label class="block md:col-span-2">
                            <span class="text-sm text-gray-700">Colonia / Interior</span>
                            <input v-model="form.shipping_address.line2" class="mt-1 w-full border rounded p-2" />
                        </label>
                        <label class="block">
                            <span class="text-sm text-gray-700">Ciudad *</span>
                            <input v-model="form.shipping_address.city" required class="mt-1 w-full border rounded p-2" />
                        </label>
                        <label class="block">
                            <span class="text-sm text-gray-700">Estado *</span>
                            <input v-model="form.shipping_address.state" required class="mt-1 w-full border rounded p-2" />
                        </label>
                        <label class="block">
                            <span class="text-sm text-gray-700">Código postal *</span>
                            <input v-model="form.shipping_address.zip" required class="mt-1 w-full border rounded p-2" />
                        </label>
                        <label class="block">
                            <span class="text-sm text-gray-700">País *</span>
                            <input v-model="form.shipping_address.country" maxlength="2" required class="mt-1 w-full border rounded p-2 uppercase" />
                        </label>
                        <label class="block md:col-span-2">
                            <span class="text-sm text-gray-700">Notas de entrega</span>
                            <textarea v-model="form.shipping_address.notes" rows="2" class="mt-1 w-full border rounded p-2"></textarea>
                        </label>
                    </div>
                </section>

                <section class="border rounded-2xl p-6 bg-white">
                    <h2 class="text-lg font-bold mb-4">Método de pago</h2>
                    <div class="space-y-2">
                        <label v-for="m in paymentMethods" :key="m.value" class="flex items-start gap-3 p-3 border rounded-lg cursor-pointer hover:bg-gray-50">
                            <input type="radio" v-model="form.payment_method" :value="m.value" name="payment_method" class="mt-1" />
                            <span class="text-sm">{{ m.label }}</span>
                        </label>
                    </div>
                    <label class="block mt-4">
                        <span class="text-sm text-gray-700">Notas adicionales (opcional)</span>
                        <textarea v-model="form.notes" rows="2" class="mt-1 w-full border rounded p-2" placeholder="PO, referencia, comentarios…"></textarea>
                    </label>
                </section>

                <p v-if="error" class="text-sm text-red-500">{{ error }}</p>

                <button
                    type="submit"
                    :disabled="isMutating"
                    class="w-full bg-green-600 text-white py-3 rounded-lg font-bold hover:bg-green-700 disabled:opacity-50 transition-colors"
                >
                    <span v-if="isMutating">Procesando…</span>
                    <span v-else>Confirmar pedido · {{ format(total) }}</span>
                </button>
            </form>

            <aside class="border rounded-2xl p-6 bg-white h-fit">
                <h2 class="text-lg font-bold mb-4">Resumen</h2>
                <ul class="space-y-2 text-sm">
                    <li v-for="item in cart?.items" :key="item.id" class="flex justify-between gap-2">
                        <span class="truncate">{{ item.qty }}× {{ item.titulo }}</span>
                        <span class="font-semibold">{{ format(item.line_total) }}</span>
                    </li>
                </ul>
                <dl class="mt-4 space-y-1 text-sm border-t pt-3">
                    <div class="flex justify-between">
                        <dt>Subtotal</dt>
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
            </aside>
        </div>
    </main>

    <BackToTop />
</template>
