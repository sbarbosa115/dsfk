import fs from 'node:fs';
import path from 'node:path';
import {test as base, expect, type Page} from '@playwright/test';

export {expect};

/** The demo seed's accounts (DemoFixtures; every password is demo1234). */
export const ADMIN = 'admin@demo.test';
export const PM = 'pm@demo.test';
const PASSWORD = 'demo1234';

/** The demo seed's projects, by the ids `make seed` gives them. */
export const TORRE_NORTE = 1;
export const CASA_CAMPESTRE = 2;
export const BODEGA_SUR = 3;

const AUTH_DIR = path.join(process.cwd(), 'e2e', '.results', 'auth');

interface Fixtures {
  /** A page signed in as this email, the way a person signs in; the session is kept for the whole run. */
  signedInAs: (email: string) => Promise<Page>;
}

export const test = base.extend<Fixtures>({
  signedInAs: async ({browser, baseURL}, use) => {
    const pages: Page[] = [];
    await use(async (email) => {
      const file = path.join(AUTH_DIR, `${email.replace(/\W+/g, '_')}.json`);
      if (!fs.existsSync(file)) {
        const context = await browser.newContext({baseURL});
        const page = await context.newPage();
        await page.goto('/login');
        await page.getByLabel('Correo electrónico').fill(email);
        await page.getByLabel('Contraseña').fill(PASSWORD);
        await page.getByRole('button', {name: 'Ingresar'}).click();
        await page.waitForURL((url) => !url.pathname.startsWith('/login'));
        fs.mkdirSync(AUTH_DIR, {recursive: true});
        await context.storageState({path: file});
        await context.close();
      }
      const context = await browser.newContext({baseURL, storageState: file});
      const page = await context.newPage();
      pages.push(page);
      return page;
    });
    await Promise.all(pages.map((page) => page.context().close()));
  },
});
