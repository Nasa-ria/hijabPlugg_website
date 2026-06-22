import defaultTheme from 'tailwindcss/defaultTheme';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/**/*.blade.php',
        './resources/**/*.js',
        './resources/**/*.vue',
    ],
    theme: {
        extend: {
            fontFamily: {
                sans: ['Outfit', 'Figtree', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                brand: {
                    50: '#faf5ff',
                    100: '#f3e8ff',
                    200: '#e9d5ff',
                    300: '#d8b4fe',
                    400: '#c084fc',
                    500: '#8b5cf6', // Amethyst base
                    600: '#7c3aed',
                    700: '#6d28d9', // Deep Amethyst
                    800: '#5b21b6',
                    900: '#4c1d95',
                    light: '#fdfcfb', // Warm neutral light
                    gold: '#dfc980',  // Champagne gold
                    goldhover: '#c5a85c',
                    dark: '#110c1a',  // Luxury near-black
                }
            },
        },
    },
    plugins: [],
};
