<script setup lang="ts">
import { ShoppingCart } from 'lucide-vue-next';
import { Link } from '@inertiajs/vue3';
import { useCart } from '@/composables/useCart';
import { onMounted } from 'vue';

const { itemCount, refresh } = useCart();

onMounted(() => {
    refresh();
});
</script>

<template>
    <div class="relative">
        <Link
            href="/cart"
            class="flex items-center gap-2 px-3 py-2 rounded-md text-gray-700 hover:bg-gray-100 transition-colors"
            aria-label="Ver carrito"
        >
            <ShoppingCart class="w-5 h-5" />
            <span class="text-sm font-medium">Carrito</span>
            <span
                v-if="itemCount > 0"
                class="absolute -top-1 -right-1 bg-green-600 text-white text-xs font-bold rounded-full h-5 min-w-5 px-1 flex items-center justify-center"
            >
                {{ itemCount > 99 ? '99+' : itemCount }}
            </span>
        </Link>
    </div>
</template>
