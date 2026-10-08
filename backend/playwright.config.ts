import {defineConfig, devices} from '@playwright/test';

/**
 * The smoke suite: the simple cases of docs/tests/ui-regression.md, run against this checkout's Docker stack before
 * anything is checked by hand in a browser.
 *
 *   backend/e2e/smoke.sh         resets the stack to the seed and runs it (from the repository root)
 *   backend/e2e/smoke.sh ui      one spec file
 *
 * One worker, in file order: the specs share one database (the demo seed).
 */
export default defineConfig({
  testDir: './e2e',
  outputDir: './e2e/.results/artifacts',
  globalSetup: './e2e/support/globalSetup.ts',
  workers: 1,
  fullyParallel: false,
  forbidOnly: !!process.env.CI,
  retries: 0,
  timeout: 30_000,
  expect: {timeout: 7_000},
  reporter: [
    ['list'],
    ['json', {outputFile: './e2e/.results/report.json'}],
    ['html', {outputFolder: './e2e/.results/html', open: 'never'}],
  ],
  use: {
    baseURL: process.env.E2E_BASE_URL ?? 'http://localhost:18081',
    locale: 'es-CO',
    // Times on screen are Bogotá's whatever the browser's zone; the suite runs in the same zone people do.
    timezoneId: 'America/Bogota',
    trace: 'retain-on-failure',
    screenshot: 'only-on-failure',
  },
  projects: [
    {
      // The admin is used at a desk; a case that is about the phone sets its own viewport (390×844).
      name: 'desktop',
      use: {...devices['Desktop Chrome'], viewport: {width: 1440, height: 900}},
    },
  ],
});
