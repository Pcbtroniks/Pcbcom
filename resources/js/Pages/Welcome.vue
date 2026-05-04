<script setup lang="ts">
import ButtonQuote from '@/Components/button-quote.vue';
import CardProduct from '@/Components/card-product.vue';
import BackToTop from '@/Components/Layout/back-to-top.vue';
import Header from '@/Components/Layout/header.vue';
import TopBar from '@/Components/Layout/top-bar.vue';
import QuotationRequest from '@/Components/quotation-request.vue';
import axios from 'axios';

    const productos = <Record<string, any>[]>[
          {
            'nombre': 'NAS 4 Bahias',
            'categoria': 'Respaldo',
            'precio': '$12,400 MXN',
            'descripcion': 'Almacenamiento centralizado para respaldos automáticos, archivos y sincronizacion de equipo.',
            'imagen': 'https://images.unsplash.com/photo-1531297484001-80022131f5a1?auto=format&fit=crop&w=800&q=80',
            'etiqueta': 'Backup'
          },
          {
            'nombre': 'Firewall UTM PyME',
            'categoria': 'Seguridad',
            'precio': '$8,950 MXN',
            'descripcion': 'Proteccion perimetral, filtrado web y control de acceso para redes empresariales.',
            'imagen': 'https://images.unsplash.com/photo-1516321318423-f06f85e504b3?auto=format&fit=crop&w=800&q=80',
            'etiqueta': 'Seguridad'
          },
          {
            'nombre': 'Licencia Microsoft 365',
            'categoria': 'Software',
            'precio': '$3,250 MXN',
            'descripcion': 'Correo corporativo, Word, Excel, Teams y herramientas colaborativas para tu equipo.',
            'imagen': 'https://images.unsplash.com/photo-1515879218367-8466d910aaa4?auto=format&fit=crop&w=800&q=80',
            'etiqueta': 'Licencia'
          },
          {
            'nombre': 'Paq. Soporte Remoto Mensual',
            'categoria': 'Servicios',
            'precio': '$2,900 MXN',
            'descripcion': 'Monitoreo, soporte preventivo y atencion remota para mantener tus equipos operando.',
            'imagen': 'https://images.unsplash.com/photo-1552664730-d307ca884978?auto=format&fit=crop&w=800&q=80',
            'etiqueta': 'Servicio'
          }
        ];

    const title: string = 'Tienda PCBtroniks';
    const description: string = 'Equipos listos para tu negocio, con soporte tecnico y garantia local.';

    const products = <Record<string, any>[]>[];
    axios.get('https://fakestoreapi.com/products')
          .then(response => {
            products.push(...response.data);
            console.log(products);
          })
            .catch(error => {
            console.error('Error fetching products:', error);
          });
console.log(productos);
</script>

<template>
    <TopBar />
    <Header />
<main id="main" class="mx-auto">
  <section class="p-8 max-w-6xl mx-auto">
    <div class="mx-auto max-w-7xl flex flex-col gap-4">
        <div class="" data-aos="fade-up">
          <h2 class="text-3xl font-black">{{ title }}</h2>
          <div class="flex justify-between items-center">
          <p class="text-gray-600">
            {{ description }}
          </p>
        <ButtonQuote />
        </div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8 mx-auto ">
            <CardProduct v-for="product in productos" :key="product.id" :product="product" />
      </div>   

        </div>

        <QuotationRequest />
        <BackToTop />
  </section>
</main>

</template>