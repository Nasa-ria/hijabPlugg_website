import defaultTheme from 'tailwindcss/defaultTheme';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/**/*.blade.php',
        './resources/**/*.js',
        './resources/**/*.vue',
        './app/Models/*.php', // order status badge colours
    ],
    theme: {
        extend: {
            fontFamily: {
                sans: ['"Montserrat Variable"', 'Montserrat', ...defaultTheme.fontFamily.sans],
                script: ['Parisienne', 'cursive'],
                serif: ['"Bodoni Moda Variable"', '"Bodoni Moda"', ...defaultTheme.fontFamily.serif],
                // Craft section only (home page)
                craft: ['Jost', ...defaultTheme.fontFamily.sans],
                'craft-display': ['"Playfair Display"', ...defaultTheme.fontFamily.serif],
            },
            colors: {
                // Brand palette: dark plum #3A1234 · bright rose #E0708A · porcelain #FAF4EF

                // Plum: headings, links, dark sections
                brand: {
                    50: '#f8eef2',
                    100: '#f0dce4',
                    200: '#e2bccb',
                    300: '#c993aa',
                    400: '#a56580',
                    500: '#7e3f62',
                    600: '#66304f',
                    700: '#521f43',
                    800: '#46183b',
                    900: '#3a1234', // Dark plum
                    light: '#faf4ef', // Porcelain (page background)
                    gold: '#dfc980',  // Champagne gold
                    goldhover: '#c5a85c',
                    dark: '#3a1234',  // Dark plum (dark sections, footer, headings)
                    ivory: '#faf4ef', // Porcelain text on dark plum
                },
                // Bright rose: buttons and icons
                accent: {
                    50: '#fdf2f5',
                    100: '#fbe3ea',
                    200: '#f7c6d3',
                    300: '#f0a2b5',
                    400: '#e8849c',
                    500: '#e0708a', // Bright rose
                    600: '#c2506b', // Deeper rose for small text and hover (readable contrast)
                    700: '#a13d56',
                    800: '#833247',
                    900: '#6a2b3c',
                },
                // Porcelain: backgrounds and soft panels
                cream: {
                    50: '#fdfaf8',
                    100: '#faf4ef', // Porcelain
                    200: '#f3e9e2',
                    300: '#e8d8cf',
                    400: '#d9c0b3',
                    500: '#c4a393',
                },
            },
        },
    },
    plugins: [],
};
