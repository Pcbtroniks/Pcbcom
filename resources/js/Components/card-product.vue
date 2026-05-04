<script setup lang="ts">
    const props = defineProps(['product']);

    console.log('product from cardProduct', props.product);
</script>

<template>  
    <div>
        <div  data-aos="zoom-in" data-aos-delay="100" class="col-span-1 rounded-xl">
          <article class="overflow-hidden rounded-2xl shadow-lg">
            <div>            
                <img :src="product.imagen || product.image" :alt="product.nombre || product.title" class="bg-cover mx-auto">
            </div>
            <div class="p-4">
              <span :class="`px-2 text-center bg-gray-100 text-green-600 text-xs font-semibold rounded-md `">
                {{ product.etiqueta }}
              </span>
              <div class="mt-2 mb-2">
              <h5 class="font-bold text-2xl">{{ product.title || product.nombre }}</h5>
              <small class="text-muted text-gray-500">{{ product.category  || product.categoria }}</small>
              </div>
              <p class="text-gray-600 ">{{ product.description || product.descripcion }}</p>
              <div class="flex items-center justify-between mt-4">
                <strong class="text-md font-bold text-green-600">${{ product.price || product.precio }}</strong>
                <div class="bg-white">
                <a href="" class="text-sm border p-1 border-green-600 text-green-600 hover:bg-green-600 hover:text-white">Agregar</a>
                </div>
              </div>
              </div>
            </article>
          </div>
      </div>
</template>