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
    css: {
        preprocessorOptions: {
            scss: {
                // Bootstrap 3 and 5 both still use @import and the old global Sass functions;
                // hide those warnings until Bootstrap moves to the Sass module system
                quietDeps: true,
                silenceDeprecations: ['import', 'global-builtin', 'color-functions', 'if-function', 'slash-div'],
            },
        },
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