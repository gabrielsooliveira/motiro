import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import path from 'path';

export default defineConfig(({ command }) => ({
    base: command === 'build' ? '/build/' : '',
    plugins: [
        laravel({
            input: ['resources/scss/app.scss', 'resources/js/app.js'],
            refresh: true,
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
    ],
    resolve: {
        alias: {
            '@': '/resources',
            'ziggy': path.resolve('vendor/tightenco/ziggy'),
        },
    },
    server: {
        https: false,
        host: 'motiro.test',
        port: 5174,
        hmr: {
            host: 'motiro.test',
        },
    },
}));
