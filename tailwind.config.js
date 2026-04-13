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
            colors: {
                upsilon: {
                    cyan: '#00f2ff',
                    magenta: '#ff00ff',
                    lime: '#39ff13',
                    void: '#0a0a0b',
                    gunmetal: '#1a1a1e',
                    rust: '#3d2b1f',
                    steel: '#4a4a4f',
                }
            },
            fontFamily: {
                sans: ['Inter', ...defaultTheme.fontFamily.sans],
                scifi: ['Orbitron', 'sans-serif'],
                mono: ['JetBrains Mono', 'monospace'],
            },
            boxShadow: {
                'glow-cyan': '0 0 10px rgba(0, 242, 255, 0.5), 0 0 20px rgba(0, 242, 255, 0.2)',
                'glow-magenta': '0 0 10px rgba(255, 0, 255, 0.5), 0 0 20px rgba(255, 0, 255, 0.2)',
                'neon': '0 0 5px theme("colors.upsilon.cyan"), 0 0 20px theme("colors.upsilon.cyan")',
            },
            backgroundImage: {
                'panel-texture': "url('/assets/textures/rusty_metal.png')",
                'hero-bg': "url('/assets/textures/hero_bg.png')",
            }
        },
    },

    plugins: [forms],
};
