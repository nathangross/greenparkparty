import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';
import container from '@tailwindcss/container-queries';

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
                // cheesecake: ['Cheesecake', 'serif'],
                // config: ['config-variable', 'sans-serif',],
                // 'display-light': ['config-variable', 'sans-serif', {
                //     fontVariationSettings: '"wght" 300',
                // }],
                // 'display-medium': ['config-variable', 'sans-serif', {
                //     fontVariationSettings: '"wght" 500',
                // }],
                // 'display-bold': ['config-variable', 'sans-serif', {
                //     fontVariationSettings: '"wght" 700',
                // }],
                // 'display-black': ['config-variable', 'sans-serif', {
                //     fontVariationSettings: '"wght" 900',
                // }],
            },
            colors: {
                'green': 'hsl(160, 100%, 36%)',
                'green-dark': 'hsl(160, 56%, 18%)',
                'green-light': 'hsl(160, 100%, 76%)',
            },
        },
    },

    plugins: [forms, container],
};
