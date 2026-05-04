<script setup lang="ts">
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
        href: '#about'
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

    <div class="mx-auto items-center mx-auto flex px-20 justify-between h-[70px]">
      <div class="">
        <h1 class="uppercase text-[29px] tracking-widest"><a href="#">
            <span>PCB</span>
            <span class="text-green-pcbtroniks">troniks</span></a></h1>
      </div>

      <nav class="px-4">
        <ul class="flex gap-8 text-sm text-gray-600">
            <li v-for="page in pages" :key="page.name"><a :href="page.href">{{ page.name }}</a></li>
        </ul>

      </nav><!-- .nav-menu -->



    </div>

  </header><!-- End Header --> 
</template>