<script setup lang="ts">
import { History } from 'lucide-vue-next';
import { Link } from '@inertiajs/vue3';
import CartButton from '@/Components/CartButton.vue';

const pages = <Record<string, any>[]>[
    {
        name: 'Inicio',
        href: '#topbar'
    },
    {
        name: 'Nosotros',
        href: '#about'
    },
    {
        name: 'Tienda',
        href: '/'
    },
    {
        name: 'Servicios',
        href: '#services'
    },
    {
        name: 'Solicitar Cotización',
        href: '#contact'
    },
    {
        name: 'Soy Cliente',
        href: '<?= base_url(); ?>clientes'
    },
    {
        name: 'English',
        href: '<?= base_url(); ?>clientes'
    }
]
</script>

<template>
      <header class="sticky top-0 z-50 bg-white">

    <div class="mx-auto items-center mx-auto flex px-4 md:px-20 justify-between h-[70px]">
      <div class="">
        <h1 class="uppercase text-[29px] tracking-widest"><a href="#">
            <span>PCB</span>
            <span class="text-green-pcbtroniks">troniks</span></a></h1>
      </div>

      <nav class="px-4 flex items-center gap-6 hidden md:flex">
        <ul class="flex gap-8 text-sm text-gray-600">
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
      </nav><!-- .nav-menu -->



    </div>

  </header><!-- End Header -->
</template>