import type {Page} from '@playwright/test';
import {ADMIN, TORRE_NORTE, expect, test} from './support/test';

// docs/tests/ui-regression.md § 7 (Fondos): the project fund. The seed leaves Torre Norte's fund at $ 15.000.000
// and Acabados (pending) with nothing; FIN-09 runs first, so the fund holds $ 16.000.000 when FIN-10 assigns.

/** The "Fondo del proyecto" tile on Fondos. */
const fundTile = (page: Page) =>
  page.getByText('Fondo del proyecto', {exact: true}).locator('..');

test('FIN-09 · a deposit can leave part of its money in the project fund', async ({
  signedInAs,
}) => {
  const page = await signedInAs(ADMIN);
  await page.goto(`/projects/${TORRE_NORTE}?tab=finance`);
  await expect(fundTile(page)).toContainText('$ 15.000.000');

  await page.getByRole('button', {name: 'Registrar depósito'}).click();
  const dialog = page.getByRole('dialog');
  await dialog.getByLabel(/^Referencia/).fill('FONDO-1');
  await dialog.getByLabel('Destino 1').selectOption('Fondo del proyecto');
  await dialog.getByLabel('Monto 1').fill('1000000');
  await dialog.getByRole('button', {name: 'Registrar'}).click();

  await expect(dialog).toBeHidden();
  await expect(fundTile(page)).toContainText('$ 16.000.000');
  await expect(
    page.getByRole('row', {name: /FONDO-1/}),
    'the movement says where the money went',
  ).toContainText('Fondo del proyecto');
});

test('FIN-10 · the Admin assigns project fund money to a stage', async ({
  signedInAs,
}) => {
  const page = await signedInAs(ADMIN);
  await page.goto(`/projects/${TORRE_NORTE}?tab=finance`);
  // The stage's row (its status names it); the movement it receives mentions Acabados too.
  const finishing = page.getByRole('row', {name: /^Pendiente: Acabados/});
  await expect(finishing).toBeVisible();

  await page.getByRole('button', {name: 'Asignar del fondo'}).click();
  const dialog = page.getByRole('dialog');
  await expect(dialog).toContainText('Disponible en el fondo: $ 16.000.000');
  await dialog.getByLabel('Destino').selectOption('Etapa: Acabados');
  await dialog.getByLabel(/^Monto/).fill('2000000');
  await dialog.getByRole('button', {name: 'Asignar'}).click();

  await expect(dialog).toBeHidden();
  await expect(fundTile(page)).toContainText('$ 14.000.000');
  await expect(finishing).toContainText('$ 2.000.000');
  await expect(
    page.getByRole('row', {name: /Asignación del fondo/}).first(),
  ).toContainText('Acabados');
});
