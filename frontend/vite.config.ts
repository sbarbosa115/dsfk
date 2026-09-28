/// <reference types="vitest/config" />
import react from '@vitejs/plugin-react';
import {fileURLToPath} from 'node:url';
import {defineConfig} from 'vite';

// In production Symfony serves the built app from public/app/.
export default defineConfig(({command}) => ({
  plugins: [react()],
  base: command === 'build' ? '/app/' : '/',
  resolve: {
    alias: {'@': fileURLToPath(new URL('./src', import.meta.url))},
  },
  build: {
    outDir: '../backend/public/app',
    emptyOutDir: true,
  },
  server: {
    proxy: {
      '/api': {
        target: process.env.API_PROXY_TARGET ?? 'http://php',
        changeOrigin: false,
      },
    },
  },
  test: {
    environment: 'jsdom',
    setupFiles: ['./src/shared/test/setup.ts'],
    globals: false,
  },
}));
