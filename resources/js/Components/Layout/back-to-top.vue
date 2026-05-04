<script setup lang="ts">
import { ArrowUp } from 'lucide-vue-next';
import { ref, onMounted, onUnmounted } from 'vue';

const isActive = ref(false);
const handleScroll = () => {
    isActive.value = window.scrollY > 200;
};

const scrollToTop = () => {
    window.scrollTo({ top: 0, behavior: 'smooth' });
};

onMounted(() => {
    window.addEventListener('scroll', handleScroll);
});

onUnmounted(() => {
    window.removeEventListener('scroll', handleScroll);
});
</script>

<template>
    <button @click="scrollToTop" :class="isActive ? 'opacity-100 pointer-events-auto' : 'opacity-0 pointer-events-none'" class="fixed bottom-4 right-4 bg-blue-400 text-white p-2 rounded-sm shadow-lg hover:bg-blue-100/80 transition-colors transform hover:scale-110">
            <a href="#" class="flex items-center justify-center">
                <ArrowUp class="w-5 h-5" />
            </a>
    </button>
</template>