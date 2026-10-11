import {ADMIN, TORRE_NORTE, expect, test} from './support/test';

// docs/tests/ui-regression.md § 6 (Presupuesto y plan): stages added to a running project (additions). Torre
// Norte's budget is approved in the seed. PLN-09 adds "Paisajismo"; PLN-12 deletes it, so the seed is as before.

test('PLN-09 · a stage added to a running project waits for the Admin', async ({
  signedInAs,
}) => {
  const page = await signedInAs(ADMIN);
  await page.goto(`/projects/${TORRE_NORTE}?tab=plan`);
  const total = await page
    .getByText('Total etapas', {exact: true})
    .locator('..')
    .innerText();

  await page.getByRole('button', {name: 'Agregar etapa'}).click();
  const dialog = page.getByRole('dialog');
  await expect(dialog).toContainText('se agrega como adición');
  await dialog.getByLabel('Nombre de la etapa').fill('Paisajismo');
  await dialog.getByRole('button', {name: 'Agregar etapa'}).click();
  await expect(dialog).toBeHidden();

  const stage = page.getByRole('region', {name: 'Paisajismo'});
  await expect(stage).toContainText('Adición 1 · Borrador');
  await expect(stage).toContainText('Pendiente de aprobación');
  await expect(
    stage.getByRole('button', {name: 'Agregar partida'}),
  ).toBeVisible();
  await expect(stage.getByRole('button', {name: 'Iniciar etapa'})).toHaveCount(
    0,
  );
  await expect(
    page
      .getByRole('region', {name: 'Estructura'})
      .getByRole('button', {name: 'Agregar partida'}),
    'what was approved stays locked',
  ).toHaveCount(0);
  await expect(
    page.getByRole('button', {name: 'Enviar adición'}),
  ).toBeDisabled();
  await expect(page.getByText('Para enviar la adición falta:')).toBeVisible();
  await expect(
    page.getByRole('table', {name: 'Adiciones al presupuesto'}),
  ).toContainText('Paisajismo');
  await expect(
    page.getByText('Total etapas', {exact: true}).locator('..'),
    'a pending stage counts in no figure',
  ).toHaveText(total);

  await page.goto(`/projects/${TORRE_NORTE}?tab=finance`);
  await expect(
    page.getByRole('row', {name: /Estructura/}).first(),
  ).toBeVisible();
  await expect(page.getByRole('row', {name: /Paisajismo/})).toHaveCount(0);
  await page.goto(`/projects/${TORRE_NORTE}?tab=expenses`);
  await page.getByRole('button', {name: 'Registrar gasto'}).click();
  await expect(
    page
      .getByRole('dialog')
      .getByLabel('Etapa')
      .locator('option', {hasText: 'Paisajismo'}),
  ).toHaveCount(0);
});

test('PLN-12 · an addition emptied of its stages goes away', async ({
  signedInAs,
}) => {
  const page = await signedInAs(ADMIN);
  await page.goto(`/projects/${TORRE_NORTE}?tab=plan`);
  const stage = page.getByRole('region', {name: 'Paisajismo'});
  await stage.getByRole('button', {name: 'Eliminar etapa'}).click();
  await page
    .getByRole('dialog')
    .getByRole('button', {name: /Eliminar/})
    .click();

  await expect(stage).toHaveCount(0);
  await expect(page.getByRole('button', {name: 'Enviar adición'})).toHaveCount(
    0,
  );
  await expect(
    page.getByRole('table', {name: 'Adiciones al presupuesto'}),
  ).toHaveCount(0);
});
