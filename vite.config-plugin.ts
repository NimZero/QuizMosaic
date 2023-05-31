import { resolve } from 'path'
import { defineConfig } from 'vite'
import { svelte } from '@sveltejs/vite-plugin-svelte'

export default defineConfig({
  plugins: [svelte()],
  publicDir: false,
  optimizeDeps: {
    include: ['lodash.get', 'lodash.isequal', 'lodash.clonedeep']
  },
  build: {
    rollupOptions: {
      input: {
        plugin: resolve(__dirname, 'src/plugin/plugin.ts'),
      }
    },
  },
})
