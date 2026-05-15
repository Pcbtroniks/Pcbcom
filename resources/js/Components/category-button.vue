<script setup lang="ts">
import { computed } from 'vue';

    const props = defineProps<{ category: { id: number; nombre: string }, selectedCategory: number | null }>();
    const emit = defineEmits(['select-category']);

    const isSelected = computed(() => props.selectedCategory === props.category.id);
</script>

<template>
    <div>
        <button :class="isSelected ? 'bg-green-600 text-white' : 'bg-gray-200 text-gray-700'"  @click="emit('select-category', props.category.id)" class="w-full px-4 py-2 rounded-md hover:bg-gray-300 transition-colors duration-300">
            {{ props.category.nombre }}
        </button>
    </div>
</template>