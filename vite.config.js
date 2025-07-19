import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import laravel from 'laravel-vite-plugin'
import { resolve } from 'path'

export default defineConfig({
  plugins: [
    laravel({
      input: [
        'resources/css/app.css',
        'resources/js/app.js',
        'resources/js/dashboard-builder.js'
      ],
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
      '@': resolve(__dirname, 'resources/js'),
      'vue': 'vue/dist/vue.esm-bundler.js'
    },
  },
  define: {
    __VUE_OPTIONS_API__: false,
  },
})