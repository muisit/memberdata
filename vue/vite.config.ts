import { fileURLToPath, URL } from 'node:url'
import { resolve } from 'path'

import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import vueJsx from '@vitejs/plugin-vue-jsx'

export default defineConfig({
  plugins: [
    vue(),
    vueJsx(),
  ],
  resolve: {
    alias: {
      '@': fileURLToPath(new URL('./src', import.meta.url))
    }
  },
  build: {
    manifest: true,
    rollupOptions: {
      input: {
        admin: resolve(__dirname, '/src/admin.ts'),
        frontend: resolve(__dirname, '/src/frontend.ts'),
      },
    },
    outDir: '../dist',
    minify: false
  },
})
