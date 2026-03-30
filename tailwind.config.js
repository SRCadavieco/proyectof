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
                accent: {
                    DEFAULT: '#c2704f',
                    light: '#d4956e',
                    dark: '#a85a3a',
                },
            },
            keyframes: {
                'marquee': {
                    '0%': { transform: 'translateX(0)' },
                    '100%': { transform: 'translateX(-50%)' },
                },
                'fade-up': {
                    '0%': { opacity: '0', transform: 'translateY(20px)' },
                    '100%': { opacity: '1', transform: 'translateY(0)' },
                },
                'draw-line': {
                    '0%': { width: '0%' },
                    '100%': { width: '100%' },
                },
            },
            animation: {
                'marquee': 'marquee 30s linear infinite',
                'fade-up': 'fade-up 0.8s ease-out forwards',
                'draw-line': 'draw-line 1.2s ease-out forwards',
            },
        },
    },

    plugins: [forms],
};
