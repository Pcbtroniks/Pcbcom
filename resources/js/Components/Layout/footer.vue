<script setup lang="ts">
import { ref } from 'vue';
import { ChevronRight, Facebook } from 'lucide-vue-next';

const email = ref('');
const submitted = ref(false);
const error = ref<string | null>(null);

const handleSubmit = () => {
    error.value = null;
    if (!email.value || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.value)) {
        error.value = 'Por favor ingresa un correo válido.';
        return;
    }
    submitted.value = true;
    email.value = '';
    setTimeout(() => (submitted.value = false), 4000);
};

const mainUrl = (import.meta.env.VITE_MAIN_PAGE_URL as string | undefined)?.replace(/\/+$/, '') ?? '';

const buildHref = (path: string) => {
    if (path.startsWith('http')) return path;
    if (path.startsWith('/') && !path.startsWith('//')) return path;
    return `${mainUrl}/${path.replace(/^\/+/, '')}`;
};

const primaryLinks: { label: string; href: string }[] = [
    { label: 'Inicio', href: buildHref('/#topbar') },
    { label: 'Nostros', href: buildHref('/#about') },
    { label: 'Servicios', href: buildHref('/#services') },
    { label: 'Terminos de servicio', href: buildHref('/#terminos') },
    { label: 'Politica de privacidad', href: buildHref('/#privacidad') },
];

const secondaryLinks: { label: string; href: string }[] = [
    { label: 'Trabajo', href: buildHref('/#contact') },
    { label: 'Clientes', href: `${mainUrl}/clientes` },
    { label: 'Cotizaciones', href: '/solicitar-cotizacion' },
    { label: 'Enviar Reporte', href: '/clientes/soporte' },
];
</script>

<template>
    <footer id="footer" class="font-raleway">
        <div class="footer-top bg-[#5c768d] border-t border-[#768fa6] border-b border-[#67839c] py-12 ">
            <div class="container mx-auto px-4 sm:px-6 lg:px-8 max-w-6xl">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-2">
                    <div class="footer-info mb-8">
                        <h3 class="text-2xl font-bold leading-none mb-5">
                            <span class="text-black">PCB</span><span class="text-green-pcbtroniks">TRONIKS</span>
                        </h3>
                        <p class="text-sm leading-6 text-white mb-0">
                            Guadalajara, Jalisco<br />
                            México<br /><br />
                            <strong>Tel:</strong> +52 33 1305 9432<br />
                            <strong>Email:</strong> info@pcbtroniks.com<br />
                        </p>
                        <div class="social-links flex mt-3">
                            <a
                                href="https://wa.me/523313059432"
                                aria-label="WhatsApp"
                                class="w-9 h-9 inline-flex items-center justify-center bg-[#768fa6] text-white rounded-full mr-1 text-lg leading-none transition-colors duration-300 hover:bg-[#428bca]"
                            >
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    width="18"
                                    height="18"
                                    viewBox="0 0 24 24"
                                    fill="currentColor"
                                >
                                    <path
                                        d="M.057 24l1.687-6.163a11.867 11.867 0 0 1-1.587-5.946C.16 5.335 5.495 0 12.05 0a11.817 11.817 0 0 1 8.413 3.488 11.824 11.824 0 0 1 3.48 8.414c-.003 6.557-5.338 11.892-11.893 11.892a11.9 11.9 0 0 1-5.688-1.448L.057 24zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884a9.86 9.86 0 0 0 1.595 5.385l.273.463-1.027 3.747 3.748-1.025zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413z"
                                    />
                                </svg>
                            </a>
                            <a
                                href="https://www.facebook.com/PCBTRONIKS/"
                                aria-label="Facebook"
                                class="w-9 h-9 inline-flex items-center justify-center bg-[#768fa6] text-white rounded-full mr-1 text-lg leading-none transition-colors duration-300 hover:bg-[#428bca]"
                            >
                                <Facebook class="w-[18px] h-[18px]" />
                            </a>
                        </div>
                    </div>

                    <div class="footer-links mb-8">
                        <h4 class="text-base font-semibold text-white relative pb-3 mb-2 after:bg-selected-menu">
                            Links
                        </h4>
                        <ul class="list-none p-0 m-0">
                            <li
                                v-for="(link, i) in primaryLinks"
                                :key="`primary-${i}`"
                                :class="['flex items-center', i === 0 ? 'pt-0' : 'pt-2.5', 'pb-2.5']"
                            >
                                <ChevronRight class="w-[18px] h-[18px] text-selected-menu pr-0.5 shrink-0" />
                                <a
                                    :href="link.href"
                                    class="text-white text-sm leading-none inline-block transition-colors duration-300 hover:text-selected-menu"
                                >
                                    {{ link.label }}
                                </a>
                            </li>
                        </ul>
                    </div>

                    <div class="footer-links mb-8">
                        <h4 class="text-base font-semibold text-white relative pb-3 mb-2 after:bg-selected-menu">
                            Te podría interesar
                        </h4>
                        <ul class="list-none p-0 m-0">
                            <li
                                v-for="(link, i) in secondaryLinks"
                                :key="`secondary-${i}`"
                                :class="['flex items-center', i === 0 ? 'pt-0' : 'pt-2.5', 'pb-2.5']"
                            >
                                <ChevronRight class="w-[18px] h-[18px] text-selected-menu pr-0.5 shrink-0" />
                                <a
                                    :href="link.href"
                                    class="text-white text-sm leading-none inline-block transition-colors duration-300 hover:text-selected-menu"
                                >
                                    {{ link.label }}
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <div class="copyright text-center pt-7 pb-0 text-sm text-white bg-[#587187] mx-auto">
            <div class="container mx-auto px-4 sm:px-6 lg:px-8 max-w-6xl">
            <span class="w-full text-center">
            © Copyright <strong><span class="text-green-pcbtroniks">PCB</span>troniks</strong>. Todos los derechos reservados.
            </span>
            </div>
        </div>
        <div class="bg-[#587187] py-4 text-center text-sm text-white">
        </div>
    </footer>
</template>
