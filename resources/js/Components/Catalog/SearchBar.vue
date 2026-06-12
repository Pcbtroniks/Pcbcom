<script setup lang="ts">
import { ref, watch, onMounted, onUnmounted } from 'vue';
import { router } from '@inertiajs/vue3';
import { Search, X, Loader2 } from 'lucide-vue-next';

interface Props {
    modelValue?: string;
    placeholder?: string;
    size?: 'sm' | 'md' | 'lg';
    autofocus?: boolean;
    showResults?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    modelValue: '',
    placeholder: 'Buscar productos, marcas, modelos…',
    size: 'md',
    autofocus: false,
    showResults: false,
});

const emit = defineEmits<{
    (e: 'update:modelValue', value: string): void;
    (e: 'submit', value: string): void;
}>();

const query = ref(props.modelValue);
const suggestions = ref<any[]>([]);
const isLoading = ref(false);
const showDropdown = ref(false);
const blurTimeout = ref<number | null>(null);
const inputEl = ref<HTMLInputElement | null>(null);

let debounceTimer: number | null = null;

watch(() => props.modelValue, (v) => {
    query.value = v;
});

watch(query, (q) => {
    if (debounceTimer) clearTimeout(debounceTimer);
    if (!props.showResults || q.length < 2) {
        suggestions.value = [];
        return;
    }
    debounceTimer = window.setTimeout(() => fetchSuggestions(q), 250);
});

async function fetchSuggestions(q: string) {
    isLoading.value = true;
    try {
        const { data } = await import('@/bootstrap').then(() =>
            window.axios.get('/api/syscom/products', { params: { busqueda: q, max: 6 } })
        );
        suggestions.value = data.productos ?? data.data?.productos ?? [];
    } catch {
        suggestions.value = [];
    } finally {
        isLoading.value = false;
    }
}

function onInput(e: Event) {
    const value = (e.target as HTMLInputElement).value;
    query.value = value;
    emit('update:modelValue', value);
    showDropdown.value = true;
}

function onFocus() {
    if (suggestions.value.length > 0) showDropdown.value = true;
}

function onBlur() {
    blurTimeout.value = window.setTimeout(() => {
        showDropdown.value = false;
    }, 200);
}

function onKeydown(e: KeyboardEvent) {
    if (e.key === 'Enter') {
        e.preventDefault();
        doSearch();
    } else if (e.key === 'Escape') {
        showDropdown.value = false;
    }
}

function doSearch() {
    if (blurTimeout.value) clearTimeout(blurTimeout.value);
    showDropdown.value = false;
    emit('submit', query.value);
    if (query.value.trim().length > 0) {
        router.visit(`/?busqueda=${encodeURIComponent(query.value.trim())}`);
    }
}

function pickSuggestion(p: any) {
    showDropdown = false;
    if (blurTimeout.value) clearTimeout(blurTimeout.value);
    router.visit(`/productos/${p.producto_id}`);
}

function clear() {
    query.value = '';
    suggestions.value = [];
    emit('update:modelValue', '');
    if (props.showResults) {
        router.visit('/');
    } else {
        inputEl.value?.focus();
    }
}

onMounted(() => {
    if (props.autofocus) {
        setTimeout(() => inputEl.value?.focus(), 100);
    }
});

onUnmounted(() => {
    if (debounceTimer) clearTimeout(debounceTimer);
    if (blurTimeout.value) clearTimeout(blurTimeout.value);
});

const sizeClass = {
    sm: 'h-9 text-sm',
    md: 'h-11 text-base',
    lg: 'h-14 text-lg',
}[props.size];
</script>

<template>
    <div class="relative w-full">
        <div
            :class="[
                'flex items-center w-full bg-white border-2 border-gray-200 rounded-full transition-all',
                'focus-within:border-green-500 focus-within:shadow-lg',
                sizeClass,
            ]"
        >
            <Search class="w-5 h-5 text-gray-400 ml-4 flex-shrink-0" />
            <input
                ref="inputEl"
                type="search"
                :value="query"
                :placeholder="placeholder"
                class="flex-1 bg-transparent border-0 outline-none px-3 placeholder-gray-400"
                autocomplete="off"
                @input="onInput"
                @focus="onFocus"
                @blur="onBlur"
                @keydown="onKeydown"
            />
            <Loader2 v-if="isLoading" class="w-4 h-4 text-gray-400 mr-3 animate-spin" />
            <button
                v-else-if="query.length > 0"
                type="button"
                class="p-1 mr-3 text-gray-400 hover:text-gray-600"
                @click="clear"
            >
                <X class="w-4 h-4" />
            </button>
            <button
                type="submit"
                class="bg-green-600 hover:bg-green-700 text-white font-medium rounded-full h-[calc(100%-8px)] px-5 mr-1 transition-colors"
                @click.prevent="doSearch"
            >
                Buscar
            </button>
        </div>

        <div
            v-if="showDropdown && suggestions.length > 0"
            class="absolute z-50 left-0 right-0 mt-2 bg-white rounded-lg shadow-xl border border-gray-100 max-h-96 overflow-y-auto"
        >
            <ul role="listbox">
                <li
                    v-for="s in suggestions"
                    :key="s.producto_id"
                    class="flex items-center gap-3 p-3 hover:bg-gray-50 cursor-pointer border-b last:border-b-0 border-gray-50"
                    @mousedown.prevent="pickSuggestion(s)"
                >
                    <img
                        :src="s.img_portada ?? 'https://placehold.co/60x60?text=?'"
                        :alt="s.titulo"
                        class="w-12 h-12 object-cover rounded bg-gray-100 flex-shrink-0"
                    />
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-900 truncate">{{ s.titulo }}</p>
                        <p class="text-xs text-gray-500">{{ s.marca }} · {{ s.modelo }}</p>
                    </div>
                    <span class="text-sm font-bold text-green-600 flex-shrink-0">
                        ${{ s.precios?.precio_1 ?? 0 }}
                    </span>
                </li>
            </ul>
        </div>
    </div>
</template>
