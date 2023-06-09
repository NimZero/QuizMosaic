import { resolve } from 'path'
import { defineConfig } from 'vite'
import { svelte } from '@sveltejs/vite-plugin-svelte'

export default defineConfig({
  plugins: [svelte(),],
  publicDir: false,
  build: {
    rollupOptions: {
      input: {
        admin: resolve(__dirname, 'src/admin/admin.ts'),
      },
      output: {
        format: 'es',
      },
    },
    minify: true,
  },
})