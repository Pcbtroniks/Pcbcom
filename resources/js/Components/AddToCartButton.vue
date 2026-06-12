<script setup lang="ts">
import { ref, onMounted, computed } from 'vue';
import { useCart } from '@/composables/useCart';

interface Props {
    productoId: number;
    precio?: number | null;
    titulo?: string | null;
    className?: string;
    label?: string;
    disabled?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    precio: null,
    titulo: null,
    className: 'w-full flex items-center justify-center mt-2 bg-green-600 hover:bg-green-700 text-white rounded-b p-2 transition-colors disabled:opacity-50',
    label: 'Agregar',
    disabled: false,
});

const { addItem, isMutating, error, refresh, cart } = useCart();
const justAdded = ref(false);

const inCartQty = computed(() => {
    if (!cart.value) return 0;
    const match = cart.value.items.find((i) => i.producto_id === props.productoId);
    return match ? match.qty : 0;
});

async function handleAdd() {
    if (props.disabled) return;

    try {
        await addItem(props.productoId, 1, props.precio ?? undefined);
        justAdded.value = true;
        setTimeout(() => (justAdded.value = false), 1500);
    } catch {
    }
}

onMounted(() => {
    if (!cart.value) {
        refresh();
    }
});
</script>

<template>
    <button
        type="button"
        :class="className"
        :disabled="isMutating || disabled"
        @click.prevent.stop="handleAdd"
    >
        <span v-if="isMutating">Agregando…</span>
        <span v-else-if="disabled">Agotado</span>
        <span v-else-if="justAdded">¡Agregado! ({{ inCartQty }})</span>
        <span v-else>{{ inCartQty > 0 ? `En carrito (${inCartQty})` : label }}</span>
    </button>
    <p v-if="error" class="text-xs text-red-500 mt-1">{{ error }}</p>
</template>
