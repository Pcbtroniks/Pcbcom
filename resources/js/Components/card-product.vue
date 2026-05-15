<script setup lang="ts">
    const props = defineProps(['product']);

    console.log('product from cardProduct', props.product);
</script>

<template>  
    <div>
        <div data-aos="zoom-in" data-aos-delay="100" class="col-span-1 rounded-xl py-4">
          <article class="overflow-hidden rounded-2xl shadow-lg">
            <div class="bg-gray-200 h-64 w-full relative">
              <span :class="`px-2 text-center bg-gray-100 uppercase text-xs font-semibold rounded-md absolute top-4 left-2 ${product.categorias[0]?.nombre ? 'block' : 'hidden'}`">
                {{ product.categorias[0]?.nombre || product.category }}
              </span>          
              <img :src="product.img_portada || product.image" :alt="product.nombre || product.title" class="bg-cover mx-auto h-64 w-full object-cover">
            </div>
            <div class="p-4">

              <div class="mb-2 flex flex-col gap-1 border-t border-gray-200 pt-2">
              <p class="text-green-600 font-extrabold text-xs">{{ product.marca || product.brand }}</p>
              <a href="product.url" class="text-md text-blue-600 hover:text-blue-800 hover:underline">
                <h5 class="font-bold text-md text-blue-600 hover:text-blue-800 hover:underline line-clamp-3 ">{{ product.titulo || product.title }}</h5>
              </a>
              <small class="text-gray-500">{{ product.modelo  || product.model }}</small>
              </div>
              <div class="flex font-bold mt-4 gap-2">
                <strong class="text-xl font-bold">${{ product.precios.precio_1 || product.precio }}</strong>
                <span class="text-lg">USD</span>
              </div>
            </div>
            <div class="w-full flex items-center justify-center mt-2 bg-green-600 text-white rounded-b p-2">
            <a href="" class="">Agregar</a>
            </div>
            </article>
          </div>
      </div>
</template>