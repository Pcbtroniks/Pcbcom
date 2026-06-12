<script setup lang="ts">
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import { ChevronRight, ChevronDown, Folder, FolderOpen } from 'lucide-vue-next';
import type { Category } from '@/types/Categories';

interface Props {
    roots: Category[];
    currentCategoryId?: number | null;
    parentChain?: number[];
}

const props = withDefaults(defineProps<Props>(), {
    currentCategoryId: null,
    parentChain: () => [],
});

const expanded = defineModel<Record<number, boolean>>('expanded', { default: () => ({}) });

function toggle(id: number) {
    expanded.value = { ...expanded.value, [id]: !expanded.value[id] };
}

function isExpanded(id: number): boolean {
    if (id in expanded.value) return !!expanded.value[id];
    if (props.currentCategoryId && props.parentChain.includes(id)) return true;
    if (props.currentCategoryId === id) return true;
    return false;
}

function isCurrent(id: number): boolean {
    return props.currentCategoryId === id;
}

function isInPath(id: number): boolean {
    return props.parentChain.includes(id);
}
</script>

<template>
    <nav aria-label="Categorías" class="space-y-1">
        <h3 class="text-sm font-bold uppercase tracking-wide text-gray-500 mb-3 px-2">
            Categorías
        </h3>

        <ul role="tree" class="space-y-0.5">
            <li v-for="cat in roots" :key="cat.id" role="treeitem" :aria-expanded="isExpanded(cat.id)">
                <div
                    :class="[
                        'flex items-center justify-between gap-2 rounded-lg px-2 py-1.5 text-sm transition-colors',
                        isCurrent(cat.id)
                            ? 'bg-green-50 text-green-700 font-semibold'
                            : isInPath(cat.id)
                                ? 'bg-gray-50 text-gray-900'
                                : 'text-gray-700 hover:bg-gray-50',
                    ]"
                >
                    <Link
                        :href="`/?categoria=${cat.id}`"
                        class="flex-1 flex items-center gap-2 truncate"
                        @click="expanded = { ...expanded, [cat.id]: true }"
                    >
                        <component
                            :is="isExpanded(cat.id) ? FolderOpen : Folder"
                            class="w-4 h-4 flex-shrink-0"
                        />
                        <span class="truncate">{{ cat.nombre }}</span>
                        <span v-if="cat.product_count > 0" class="text-xs text-gray-400">
                            ({{ cat.product_count }})
                        </span>
                    </Link>

                    <button
                        v-if="cat.children.length > 0"
                        type="button"
                        :aria-label="isExpanded(cat.id) ? 'Colapsar' : 'Expandir'"
                        class="p-1 rounded hover:bg-gray-200 transition-colors"
                        @click.prevent="toggle(cat.id)"
                    >
                        <component
                            :is="isExpanded(cat.id) ? ChevronDown : ChevronRight"
                            class="w-4 h-4"
                        />
                    </button>
                </div>

                <ul
                    v-if="cat.children.length > 0 && isExpanded(cat.id)"
                    class="ml-4 mt-0.5 space-y-0.5 border-l-2 border-gray-100 pl-2"
                    role="group"
                >
                    <li v-for="child in cat.children" :key="child.id" role="treeitem">
                        <Link
                            :href="`/?categoria=${child.id}`"
                            :class="[
                                'flex items-center justify-between gap-2 rounded-md px-2 py-1 text-sm transition-colors',
                                isCurrent(child.id)
                                    ? 'bg-green-50 text-green-700 font-semibold'
                                    : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900',
                            ]"
                        >
                            <span class="truncate">{{ child.nombre }}</span>
                            <span v-if="child.product_count > 0" class="text-xs text-gray-400">
                                {{ child.product_count }}
                            </span>
                        </Link>
                    </li>
                </ul>
            </li>
        </ul>
    </nav>
</template>
