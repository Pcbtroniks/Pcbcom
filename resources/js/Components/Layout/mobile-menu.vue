<script lang="ts" setup>
import { Menu, X } from 'lucide-vue-next';
import { ref } from 'vue';

const isOpen = ref(false);

const toggleMenu = () => {
    isOpen.value = !isOpen.value;
};

const mainUrl = (import.meta.env.VITE_MAIN_PAGE_URL as string | undefined)?.replace(/\/+$/, '') ?? '';

const buildHref = (path: string) => {
    if (path.startsWith('http') || path.startsWith('/')) return path;
    return `${mainUrl}/${path.replace(/^\/+/, '')}`;
};

const menuItems = [
    { label: 'Inicio', href: buildHref('#topbar') },
    { label: 'Nosotros', href: buildHref('#about') },
    { label: 'Tienda', href: '/' },
    { label: 'Servicios', href: buildHref('#services') },
    { label: 'Solicitar Cotización', href: buildHref('#contact') },
    { label: 'Soy Cliente', href: `${mainUrl}/clientes` },
    { label: 'English', href: `${mainUrl}/en` },
];
</script>

<template>
    <button class="md:hidden" aria-label="Menú móvil" @click="toggleMenu">
        <Menu v-if="!isOpen" class="w-6 h-6" />
        <X v-else class="w-6 h-6 text-white z-30" />
    </button>

    <!-- Menú móvil -->
     <div class="fixed inset-0 bg-black bg-opacity-50 z-20 w-full" v-show="isOpen" @click="toggleMenu"></div>
     <Transition enter-active-class="transition duration-300 ease-out" enter-from-class="opacity-0" enter-to-class="opacity-100" leave-active-class="transition duration-300 ease-in" leave-from-class="opacity-100" leave-to-class="opacity-0">
     <X v-show="isOpen" class="w-7 h-7 text-white z-30 fixed top-5 right-6" @click="toggleMenu" />
        </Transition>
    <Transition enter-active-class="transition duration-300 ease-out" enter-from-class="-translate-x-full" enter-to-class="translate-x-0" leave-active-class="transition duration-300 ease-in" leave-from-class="translate-x-0" leave-to-class="-translate-x-full">
    <div class="fixed inset-y-0 left-0 w-64 z-30 p-4 px-5 bg-mobile-menu text-gray-200" v-show="isOpen">
        <span class="mb-4">
        </span>
        <nav class="mt-4">
            <ul class="space-y-5">
                <li v-for="item in menuItems" :key="item.label">
                    <a :href="item.href" class="hover:text-selected-menu active:text-selected-menu">{{ item.label }}</a>
                </li>
            </ul>
        </nav>
    </div>
    </Transition>
</template>