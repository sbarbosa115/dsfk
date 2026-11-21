import {screen, within} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import {afterEach, describe, expect, it, vi} from 'vitest';
import {financeFixture} from '@/entities/finance';
import {planFixture} from '@/entities/plan';
import {mockApi, renderWithProviders} from '@/shared/test/render';
import {CategoryBoard} from './CategoryBoard';

// Intl puts non-breaking spaces in amounts.
const text = (value: string) => value.replace(/\s/g, ' ');

afterEach(() => vi.unstubAllGlobals());

const base = planFixture();
const plan = planFixture({
  categories: [
    {id: 1, name: 'Materiales'},
    {id: 2, name: 'Nómina'},
    {id: 3, name: 'Acero'},
  ],
  budget: {
    ...base.budget!,
    byCategory: [
      {categoryId: 1, total: '1000000.00'},
      {categoryId: 2, total: '400000.00'},
    ],
  },
});
const finance = financeFixture({
  categories: [
    {
      id: 1,
      name: 'Materiales',
      budget: '1000000.00',
      spent: '250000.00',
      executed: 2500,
    },
    {
      id: 2,
      name: 'Nómina',
      budget: '400000.00',
      spent: '500000.00',
      executed: 12500,
    },
  ],
});

describe('CategoryBoard', () => {
  it('compares budget and spending per category, the largest budget first', async () => {
    mockApi({
      'GET /api/projects/9/plan': plan,
      'GET /api/projects/9/finance': finance,
    });
    renderWithProviders(<CategoryBoard projectId={9} />);

    const table = await screen.findByRole('table', {name: 'Categorías'});
    const rows = within(table).getAllByRole('row').slice(1);
    expect(
      rows.map((r) => within(r).getAllByRole('cell')[0]!.textContent),
    ).toEqual(['Materiales', 'Sobre presupuesto: Nómina', 'Acero']);
    expect(text(rows[0]!.textContent ?? '')).toContain('$ 750.000');
    expect(rows[1], 'spent more than it budgeted').toHaveClass(
      'row-tone-danger',
    );
    expect(text(rows[1]!.textContent ?? '')).toContain('-$ 100.000');
    expect(rows[2]).not.toHaveClass('row-tone');
    expect(
      screen.getByText(
        (_, el) =>
          el?.tagName === 'P' &&
          el.textContent === '3 categorías · 1 sobre presupuesto',
      ),
    ).toBeInTheDocument();
    expect(
      text(
        screen.getByText('Gastado', {selector: '.stat-label'}).parentElement!
          .textContent ?? '',
      ),
    ).toContain('53,6% ejecutado');
    expect(
      within(rows[0]!).getByRole('button', {name: /Renombrar/}),
      'renaming is not the main thing on the page',
    ).not.toHaveClass('is-main');
  });

  it('adds a category from the header', async () => {
    mockApi({
      'GET /api/projects/9/plan': plan,
      'GET /api/projects/9/finance': finance,
    });
    renderWithProviders(<CategoryBoard projectId={9} />);

    await userEvent.click(
      await screen.findByRole('button', {name: 'Nueva categoría'}),
    );
    expect(
      screen.getByRole('dialog', {name: 'Nueva categoría'}),
    ).toBeInTheDocument();
  });

  it('shows a Team Lead the names only, without asking for the money', async () => {
    const api = mockApi({
      'GET /api/projects/9/plan': planFixture({
        ...plan,
        budget: null,
        permissions: {
          ...plan.permissions,
          viewFinancials: false,
          manageCategories: false,
        },
      }),
    });
    renderWithProviders(<CategoryBoard projectId={9} />);

    const list = await screen.findByRole('list');
    expect(
      within(list)
        .getAllByRole('listitem')
        .map((li) => li.textContent),
    ).toEqual(['Acero', 'Materiales', 'Nómina']);
    expect(screen.queryByText('Presupuestado')).not.toBeInTheDocument();
    expect(
      screen.queryByRole('button', {name: 'Nueva categoría'}),
    ).not.toBeInTheDocument();
    expect(api.mock.calls.map(([url]) => url)).not.toContain(
      '/api/projects/9/finance',
    );
  });
});
