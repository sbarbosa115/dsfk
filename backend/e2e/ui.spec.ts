import {
  ADMIN,
  BODEGA_SUR,
  CASA_CAMPESTRE,
  TORRE_NORTE,
  expect,
  test,
} from './support/test';

// docs/tests/ui-regression.md § 13 (Interfaz): the house style of buttons, rows, empty states and the phone.

test('UI-01 · a tab’s main action ends its filter bar, filled', async ({
  signedInAs,
}) => {
  const page = await signedInAs(ADMIN);

  await page.goto(`/projects/${TORRE_NORTE}?tab=expenses`);
  const record = page.getByRole('button', {name: 'Registrar gasto'});
  await expect(record).toHaveClass(/is-main/);
  const bar = page.locator('.toolbar').filter({has: record});
  await expect(bar.getByLabel('Buscar')).toBeVisible();
  await expect(
    page.locator('.tab-intro').getByRole('button'),
    'nothing beside the intro sentence',
  ).toHaveCount(0);

  await page.goto(`/projects/${TORRE_NORTE}?tab=finance`);
  const deposit = page.getByRole('button', {name: 'Registrar depósito'});
  await expect(deposit).toHaveClass(/is-main/);
  await expect(
    page.locator('.toolbar').filter({has: deposit}).getByLabel('Buscar'),
  ).toBeVisible();

  await page.goto(`/projects/${BODEGA_SUR}?tab=plan`);
  await expect(page.getByText(/Aún no hay etapas/)).toBeVisible();
  await expect(page.getByRole('button', {name: 'Agregar etapa'})).toHaveCount(
    1,
  );
});

test('UI-02 · a modal’s main button is filled in the colour of what it does', async ({
  signedInAs,
}) => {
  const page = await signedInAs(ADMIN);

  await page.goto(`/projects/${TORRE_NORTE}?tab=expenses`);
  await page
    .getByRole('button', {name: 'Más acciones: Cinta y señalización'})
    .click();
  await page.getByRole('menuitem', {name: /Rechazar/}).click();
  const reject = page.getByRole('dialog');
  await expect(reject.getByRole('button', {name: 'Rechazar'})).toHaveClass(
    /btn-action-danger.*is-main|is-main.*btn-action-danger/,
  );
  await expect(reject.getByRole('button', {name: 'Cancelar'})).toHaveClass(
    /btn-ghost/,
  );
  await reject.getByRole('button', {name: 'Cancelar'}).click();

  await page.goto(`/projects/${TORRE_NORTE}?tab=finance`);
  await page
    .getByRole('button', {name: /^Más acciones: Depósito/})
    .first()
    .click();
  await page.getByRole('menuitem', {name: /Anular/}).click();
  const voiding = page.getByRole('dialog');
  await expect(voiding.getByRole('button', {name: 'Anular'})).toHaveClass(
    /btn-action-danger/,
  );
  await expect(voiding.getByRole('button', {name: 'Anular'})).toHaveClass(
    /is-main/,
  );
  await voiding.getByRole('button', {name: 'Cancelar'}).click();

  await page.goto(`/projects/${CASA_CAMPESTRE}?tab=plan`);
  await page.getByRole('button', {name: 'Aprobar presupuesto'}).click();
  const approve = page.getByRole('dialog');
  await expect(approve.getByRole('button', {name: /Aprobar/})).toHaveClass(
    /is-main/,
  );
  await approve.getByRole('button', {name: 'Cancelar'}).click();
});

test('UI-03 · a row has one main action and a menu with the rest, destructive last', async ({
  signedInAs,
}) => {
  const page = await signedInAs(ADMIN);
  await page.goto(`/projects/${TORRE_NORTE}?tab=expenses`);

  const row = page.getByRole('row', {name: /Cinta y señalización/});
  await expect(row.getByRole('button', {name: 'Aprobar'})).toHaveClass(
    /is-main/,
  );
  await row
    .getByRole('button', {name: 'Más acciones: Cinta y señalización'})
    .click();
  const menu = page.getByRole('menu', {name: 'Cinta y señalización'});
  await expect(menu.locator('.row-menu-label')).toHaveText([
    'Ver gasto',
    'Adjuntar recibo',
    'Recibo',
    'Rechazar',
  ]);
  await page.keyboard.press('Escape');
  await expect(menu).toBeHidden();
});

test('UI-04 · turning a user off asks first, from the last item of the menu', async ({
  signedInAs,
}) => {
  const page = await signedInAs(ADMIN);
  await page.goto('/users');

  await page.getByRole('button', {name: 'Más acciones: Carlos Ruiz'}).click();
  const items = page.getByRole('menuitem');
  await expect(items.last()).toContainText('Desactivar');
  await items.last().click();
  const dialog = page.getByRole('dialog');
  await expect(dialog).toContainText('Carlos Ruiz');
  await dialog.getByRole('button', {name: 'Cancelar'}).click();
  await expect(page.getByRole('row', {name: /Carlos Ruiz/})).toBeVisible();

  const me = page.getByRole('row', {name: /Sofía Restrepo/});
  await expect(me.getByRole('button', {name: 'Editar'})).toBeVisible();
  await expect(me.getByRole('button', {name: /Más acciones/})).toHaveCount(0);
});

test.describe('on a phone', () => {
  test.use({viewport: {width: 390, height: 844}});

  test('UI-05 · every expense is a card, its main button on screen', async ({
    signedInAs,
  }) => {
    const page = await signedInAs(ADMIN);
    await page.goto(`/projects/${TORRE_NORTE}?tab=expenses`);

    await expect(page.locator('.table thead').first()).toBeHidden();
    const card = page.getByRole('row', {name: /Cinta y señalización/});
    await expect(card.locator('td[data-label="Monto"]')).toBeVisible();
    const main = card.getByRole('button', {name: 'Aprobar'});
    await main.scrollIntoViewIfNeeded();
    const box = await main.boundingBox();
    expect(box, 'the main button is drawn').not.toBeNull();
    expect(
      (box?.x ?? 0) + (box?.width ?? 0),
      'inside the screen, not past its edge',
    ).toBeLessThanOrEqual(390);
    expect(box?.height ?? 0, 'a thumb’s height').toBeGreaterThanOrEqual(44);

    // Nothing in a card runs past its edge (a long date range wraps instead).
    await page.goto('/projects');
    await expect(page.getByRole('row', {name: /Torre Norte/})).toBeVisible();
    const clipped = await page
      .locator('.table tbody td')
      .evaluateAll((cells) =>
        cells
          .filter((cell) => cell.scrollWidth > cell.clientWidth + 1)
          .map((cell) => cell.textContent),
      );
    expect(clipped).toEqual([]);
  });

  test('UI-06 · the project tabs use short labels and keep the chosen one in view', async ({
    signedInAs,
  }) => {
    const page = await signedInAs(ADMIN);
    await page.goto(`/projects/${TORRE_NORTE}?tab=overview`);

    await expect(page.getByRole('tab', {name: /Caja menor/})).toContainText(
      'Caja',
    );
    await expect(
      page.getByRole('tab', {name: /Presupuesto y plan/}),
    ).toContainText('Plan');
    const chosen = page.getByRole('tab', {name: /Resumen/});
    await expect(chosen).toHaveAttribute('aria-selected', 'true');
    await expect(chosen).toBeInViewport({ratio: 1});
  });
});

test('UI-07 · an empty list says so inside its table, and an empty chart in words', async ({
  signedInAs,
}) => {
  const page = await signedInAs(ADMIN);

  await page.goto(`/projects/${BODEGA_SUR}?tab=finance`);
  const message = page.getByText(/Las etapas aparecen aquí con el dinero/);
  await expect(message).toBeVisible();
  await expect(
    page.locator('table').filter({has: message}).getByRole('columnheader', {
      name: 'Presupuesto',
    }),
  ).toBeVisible();

  await page.goto(`/projects/${BODEGA_SUR}?tab=dashboard`);
  await expect(page.getByText(/Aún no hay depósitos ni gastos/)).toBeVisible();
});

test('UI-08 · Auditoría names fields in Spanish and dates like the rest of the app', async ({
  signedInAs,
}) => {
  const page = await signedInAs(ADMIN);
  await page.goto('/audit');

  const table = page.getByRole('table');
  await expect(table.getByRole('row').nth(1)).toBeVisible();
  await expect(table).not.toContainText(/\b(status|projectId|voidedById)\b/);
  await expect(table.getByRole('row').nth(1)).toContainText(
    /\d{1,2} de [a-z]{3,4}\.? de \d{4}, \d{1,2}:\d{2}/,
  );
});

test('UI-10 · money columns and their headers align right', async ({
  signedInAs,
}) => {
  const page = await signedInAs(ADMIN);
  await page.goto(`/projects/${TORRE_NORTE}?tab=expenses`);

  const header = page.getByRole('columnheader', {name: 'Monto'});
  await expect(header).toHaveClass(/\bnum\b/);
  await expect(header).toHaveCSS('text-align', 'right');
});
