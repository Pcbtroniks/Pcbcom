<script setup lang="ts">
import { ChevronUp } from 'lucide-vue-next';
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
    <button @click="scrollToTop" :class="isActive ? 'opacity-100 pointer-events-auto' : 'opacity-0 pointer-events-none'" class="fixed bottom-4 right-4 bg-[#428BCA] text-white p-2 rounded-sm shadow-lg hover:bg-blue-100/80 transition-colors transform hover:scale-110 z-20">
            <a href="#" class="flex items-center justify-center">
                <ChevronUp class="w-6 h-6" />
            </a>
    </button>
</template>