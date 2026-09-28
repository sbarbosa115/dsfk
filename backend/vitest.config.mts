// Component and unit tests for the React side: `npm test` (Vitest + Testing Library, in jsdom). Test files sit next
// to what they test.
import {fileURLToPath} from 'node:url';
import {defineConfig} from 'vitest/config';

export default defineConfig({
  resolve: {
    alias: {'@': fileURLToPath(new URL('./assets/react', import.meta.url))},
  },
  test: {
    environment: 'jsdom',
    globals: false,
    include: ['assets/**/*.test.{ts,tsx}'],
    setupFiles: ['assets/react/shared/test/setup.ts'],
    css: false,
  },
});
