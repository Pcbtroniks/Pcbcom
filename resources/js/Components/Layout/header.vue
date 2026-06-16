<script setup lang="ts">
import { History } from 'lucide-vue-next';
import { Link } from '@inertiajs/vue3';
import CartButton from '@/Components/CartButton.vue';
import MenuMobile from '@/Components/Layout/mobile-menu.vue';

const mainUrl = (import.meta.env.VITE_MAIN_PAGE_URL as string | undefined)?.replace(/\/+$/, '') ?? '';

const buildHref = (path: string) => {
    if (path.startsWith('http') || path.startsWith('/')) return path;
    return `${mainUrl}/${path.replace(/^\/+/, '')}`;
};

const pages = <Record<string, any>[]>[
    {
        name: 'Inicio',
        href: buildHref('#topbar')
    },
    {
        name: 'Nosotros',
        href: buildHref('#about')
    },
    {
        name: 'Tienda',
        href: '/'
    },
    {
        name: 'Servicios',
        href: buildHref('#services')
    },
    {
        name: 'Solicitar Cotización',
        href: buildHref('#contact')
    },
    {
        name: 'Soy Cliente',
        href: `${mainUrl}/clientes`
    },
    {
        name: 'English',
        href: `${mainUrl}/en`
    }
]
</script>

<template>
      <header class="sticky top-0 z-50 bg-white">

    <div class="mx-auto items-center mx-auto flex px-4 md:px-20 justify-between h-[70px]">
      <div class="raleway">
        <h1 class="uppercase text-[28px] tracking-widest"><a href="#">
            <span>PCB</span>
            <span class="text-green-pcbtroniks">troniks</span></a></h1>
      </div>

      <nav class="md:px-4 flex items-center gap-2 md:gap-8  md:flex justify-end px-1">
        <ul class="flex gap-8 text-sm text-gray-600 hidden md:inline-flex">
            <li v-for="page in pages" :key="page.name"><a :href="page.href">{{ page.name }}</a></li>
        </ul>
        <Link
        href="/recientes"
        class="flex items-center gap-1 text-sm text-gray-600 hover:text-green-600 transition-colors"
        aria-label="Vistos recientemente"
        >
        <History class="w-4 h-4" />
        <span class="hidden md:inline">Vistos</span>
    </Link>
    <CartButton />
        <MenuMobile />
      </nav><!-- .nav-menu -->



    </div>

  </header><!-- End Header -->
</template>