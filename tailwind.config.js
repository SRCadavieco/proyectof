import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Inter', ...defaultTheme.fontFamily.sans],
                serif: ['"DM Serif Display"', 'Georgia', ...defaultTheme.fontFamily.serif],
            },
            colors: {
                cream: {
                    50: '#fdfcfa',
                    100: '#faf8f4',
                    200: '#f5f1ea',
                    300: '#e8e3d9',
                    400: '#d4cdc0',
                },
                ink: {
                    DEFAULT: '#1a1a1a',
                    light: '#4a4a4a',
                    muted: '#8a8a8a',
                },
            },
        },
    },

    plugins: [forms],
};
