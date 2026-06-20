<script setup lang="ts">
import { ref, watch, computed } from 'vue';
import { Filter, X } from 'lucide-vue-next';
import type { CatalogFilters, SortOption } from '@/types/Filters';
import { SORT_OPTIONS } from '@/types/Filters';

interface Props {
    modelValue: CatalogFilters;
    brands: { id: number; nombre: string }[];
    showCategoryFilter?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    showCategoryFilter: false,
});

const emit = defineEmits<{
    (e: 'update:modelValue', value: CatalogFilters): void;
}>();

const local = ref<CatalogFilters>({ ...props.modelValue });

watch(() => props.modelValue, (v) => {
    local.value = { ...v };
}, { deep: true });

function commit() {
    emit('update:modelValue', { ...local.value, pagina: 1 });
}

function clearAll() {
    local.value = { ...local.value, marca: undefined, stock: undefined, precio_min: undefined, precio_max: undefined, orden: undefined, pagina: 1 };
    commit();
}

const activeCount = computed(() => {
    let n = 0;
    if (local.value.marca) n++;
    if (local.value.stock) n++;
    if (local.value.precio_min || local.value.precio_max) n++;
    if (local.value.orden && local.value.orden !== 'relevancia') n++;
    return n;
});


</script>

<template>
    <div class="space-y-4">
        <div class="flex items-center justify-between">
            <h3 class="flex items-center gap-2 text-sm font-bold uppercase tracking-wide text-gray-700">
                <Filter class="w-4 h-4" />
                Filtros
            </h3>
            <button
                v-if="activeCount > 0"
                type="button"
                class="flex items-center gap-1 text-xs text-red-600 hover:text-red-800"
                @click="clearAll"
            >
                <X class="w-3 h-3" />
                Limpiar ({{ activeCount }})
            </button>
        </div>

        <div class="space-y-1">
            <label class="text-xs font-medium text-gray-600">Ordenar por</label>
            <select
                v-model="local.orden"
                class="w-full border border-gray-200 rounded-md text-sm py-1.5 px-2 focus:ring-2 focus:ring-green-500 focus:border-transparent"
                @change="commit"
            >
                <option v-for="o in SORT_OPTIONS" :key="o.value" :value="o.value">{{ o.label }}</option>
            </select>
        </div>

        <div class="space-y-1">
            <label class="text-xs font-medium text-gray-600">Marca</label>
            <select
                v-model="local.marca"
                class="w-full border border-gray-200 rounded-md text-sm py-1.5 px-2 focus:ring-2 focus:ring-green-500 focus:border-transparent"
                @change="commit"
            >
                <option :value="undefined">Todas las marcas</option>
                <option v-for="b in brands" :key="b.id" :value="b.id">{{ b.nombre }}</option>
            </select>
        </div>
    </div>
</template>
