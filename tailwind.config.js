import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.vue',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Open Sans', ...defaultTheme.fontFamily.sans],
            raleway: ['Raleway', 'sans-serif'],
            },
            colors: {
                'green-pcbtroniks': '#5AB131',
                'gray-pcbtroniks': '#F5F5F5',
                'mobile-menu': '#1F3548',
                'selected-menu': '#9eccf4',
            },
        },
    },

    plugins: [forms],
};
