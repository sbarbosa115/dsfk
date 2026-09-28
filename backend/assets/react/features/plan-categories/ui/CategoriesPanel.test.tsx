import {screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import {afterEach, describe, expect, it, vi} from 'vitest';
import {planFixture} from '@/entities/plan';
import {json, mockApi, renderWithProviders} from '@/shared/test/render';
import {CategoriesPanel} from './CategoriesPanel';

afterEach(() => vi.unstubAllGlobals());

describe('CategoriesPanel', () => {
  it('refuses a name that exists and forgets the message once the user types again', async () => {
    mockApi({
      'POST /api/projects/9/categories': json(
        {
          error: 'validation_failed',
          violations: {name: ['Ya existe una categoría con ese nombre.']},
        },
        422,
      ),
    });
    renderWithProviders(<CategoriesPanel plan={planFixture()} />);

    await userEvent.type(
      screen.getByLabelText('Nueva categoría'),
      'materiales',
    );
    await userEvent.click(screen.getByRole('button', {name: 'Agregar'}));
    expect(
      await screen.findByText('Ya existe una categoría con ese nombre.'),
    ).toBeInTheDocument();

    await userEvent.type(screen.getByLabelText(/^Nueva categoría/), 's');
    expect(
      screen.queryByText('Ya existe una categoría con ese nombre.'),
    ).not.toBeInTheDocument();
  });
});
