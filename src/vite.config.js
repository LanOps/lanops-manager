import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
// import react from '@vitejs/plugin-react';
// import vue from '@vitejs/plugin-vue';

export default defineConfig({
    // Vite 8 minifies CSS with Lightning CSS, which rewrites some Bootstrap 3 rules
    // (e.g. the normalize.css "monospace, monospace" hack); keep the old esbuild output
    build: {
        cssMinify: 'esbuild',
    },
    plugins: [
        laravel([
            'resources/assets/sass/app.scss',
            'resources/assets/sass/admin.scss',
        ]),
        // react(),
        // vue({
        //     template: {
        //         transformAssetUrls: {
        //             base: null,
        //             includeAbsolute: false,
        //         },
        //     },
        // }),
    ],
});