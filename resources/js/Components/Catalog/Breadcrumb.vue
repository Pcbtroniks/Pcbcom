<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ChevronRight, Home } from 'lucide-vue-next';

interface Crumb {
    label: string;
    href?: string;
}

interface Props {
    items: Crumb[];
}

defineProps<Props>();
</script>

<template>
    <nav aria-label="Breadcrumb" class="flex items-center gap-1 text-sm text-gray-600 flex-wrap">
        <Link href="/" class="flex items-center gap-1 hover:text-green-600">
            <Home class="w-4 h-4" />
            <span class="sr-only">Inicio</span>
        </Link>
        <template v-for="(item, i) in items" :key="i">
            <ChevronRight class="w-4 h-4 text-gray-400" />
            <Link
                v-if="item.href && i < items.length - 1"
                :href="item.href"
                class="hover:text-green-600 truncate max-w-[200px]"
            >
                {{ item.label }}
            </Link>
            <span
                v-else
                :class="[
                    'truncate max-w-[300px]',
                    i === items.length - 1 ? 'text-gray-900 font-semibold' : '',
                ]"
            >
                {{ item.label }}
            </span>
        </template>
    </nav>
</template>
