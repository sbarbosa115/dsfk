import {request} from '@playwright/test';

/** Before any spec: the stack answers and holds the demo seed (backend/e2e/prepare.sh loads it). */
export default async function globalSetup(): Promise<void> {
  const baseURL = process.env.E2E_BASE_URL ?? 'http://localhost:18081';
  const api = await request.newContext({baseURL});
  const signIn = await api.post('/api/login', {
    headers: {'X-Requested-With': 'XMLHttpRequest'},
    data: {email: 'admin@demo.test', password: 'demo1234'},
  });
  if (!signIn.ok()) {
    throw new Error(
      `The stack at ${baseURL} has no demo seed (signing in as admin@demo.test answered ${signIn.status()}). Run backend/e2e/smoke.sh, which prepares it.`,
    );
  }
  await api.dispose();
}
