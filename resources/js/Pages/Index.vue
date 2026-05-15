<script setup lang="ts">
import ButtonQuote from '@/Components/button-quote.vue';
import CardProduct from '@/Components/card-product.vue';
import CategoryButton from '@/Components/category-button.vue';
import SkeletonCardProduct from '@/Components/Skeletons/skeleton-card-product.vue';
import BackToTop from '@/Components/Layout/back-to-top.vue';
import Header from '@/Components/Layout/header.vue';
import TopBar from '@/Components/Layout/top-bar.vue';
import QuotationRequest from '@/Components/quotation-request.vue';
import { Product } from '@/types/Products';
import { ProductResponse } from '@/types/ProductsResponse';
import { Category } from '@/types/Categories';
import { ref, watch, computed, onMounted } from 'vue';
import axios from 'axios';

const products = ref<Product[]>([]);
const productResponse = ref<ProductResponse>({} as ProductResponse);
const isLoading = ref(true);
const categories = ref(<Category[]>[]);
const selectCategory = ref<number | null>(null);
const currentPage = ref(1);

    const title: string = 'Tienda PCBtroniks';
    const description: string = 'Equipos listos para tu negocio, con soporte tecnico y garantia local.';


const fetchCategories = () => {
    axios.get('/api/syscom/categories')
          .then(response => {
            categories.value = response.data as Category[];
            console.log('Categories:', categories.value);
          })
          .catch(error => {
            console.error('Error fetching categories:', error);
          });
};

const fetchProducts = () => {
    isLoading.value = true;
    axios.get(`/api/syscom/products?categoria=${selectCategory.value ?? ""}&pagina=${currentPage.value}`)
          .then(response => {
            productResponse.value = response.data as ProductResponse;
            products.value = productResponse.value.productos;
          })
          .catch(error => {
            console.error('Error fetching products:', error);
          })
          .finally(() => {
            isLoading.value = false;
          });
};

const goToPage = (page: number) => {
    if (page < 1 || page > productResponse.value.paginas) return;
    currentPage.value = page;
    window.scrollTo({ top: 0, behavior: 'smooth' });
};

const visiblePages = computed(() => {
    const total = productResponse.value.paginas ?? 0;
    const current = currentPage.value;
    const pages: (number | '...')[] = [];

    if (total <= 7) {
        for (let i = 1; i <= total; i++) pages.push(i);
        return pages;
    }

    pages.push(1);
    if (current > 3) pages.push('...');
    for (let i = Math.max(2, current - 1); i <= Math.min(total - 1, current + 1); i++) {
        pages.push(i);
    }
    if (current < total - 2) pages.push('...');
    pages.push(total);

    return pages;
});

watch(selectCategory, () => {
    currentPage.value = 1;
    fetchProducts();
});

watch(currentPage, () => {
    fetchProducts();
});

onMounted(() => {
    fetchCategories();
    fetchProducts();
});
</script>

<template>
    <TopBar />
    <Header />
<main id="main" class="mx-auto">
  <section class="p-8  mx-auto">
    <div class="mx-auto px-12 flex flex-col gap-4">
        <div class="" data-aos="fade-up">
          <h2 class="text-3xl font-black">{{ title }}</h2>
          <div class="flex justify-between items-center">
          <p class="text-gray-600">
            {{ description }}
          </p>
        <ButtonQuote />
        </div>
      </div>

      <div class="flex gap-4 overflow-x-auto whitespace-nowrap py-4" data-aos="fade-up" data-aos-delay="100">
      <CategoryButton v-for="category in categories" :key="category.id" :category="category" :selected-category="selectCategory" @select-category="selectCategory = $event" />
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 2xl:grid-cols-6 gap-8 mx-auto ">
            <template v-if="isLoading">
              <SkeletonCardProduct v-for="n in 12" :key="n" />
            </template>
            <template v-else>
              <CardProduct v-for="product in products" :key="product.producto_id" :product="product" />
            </template>
      </div>

      <div v-if="!isLoading && productResponse.paginas > 1" class="flex justify-center items-center gap-1 mt-8">
        <button
          @click="goToPage(currentPage - 1)"
          :disabled="currentPage === 1"
          class="px-3 py-2 rounded-md text-sm font-medium bg-gray-100 hover:bg-gray-200 disabled:opacity-40 disabled:cursor-not-allowed transition-colors"
        >
          ‹ Anterior
        </button>

        <template v-for="page in visiblePages" :key="page">
          <span v-if="page === '...'" class="px-2 py-2 text-gray-400 select-none">…</span>
          <button
            v-else
            @click="goToPage(page)"
            :class="[
              'px-3 py-2 rounded-md text-sm font-medium transition-colors',
              page === currentPage
                ? 'bg-green-600 text-white'
                : 'bg-gray-100 hover:bg-gray-200 text-gray-700'
            ]"
          >
            {{ page }}
          </button>
        </template>

        <button
          @click="goToPage(currentPage + 1)"
          :disabled="currentPage === productResponse.paginas"
          class="px-3 py-2 rounded-md text-sm font-medium bg-gray-100 hover:bg-gray-200 disabled:opacity-40 disabled:cursor-not-allowed transition-colors"
        >
          Siguiente ›
        </button>
      </div>

        </div>

        <QuotationRequest />
        <BackToTop />
  </section>
</main>

</template>