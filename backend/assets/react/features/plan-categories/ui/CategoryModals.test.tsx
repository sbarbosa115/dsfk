import {screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import {afterEach, describe, expect, it, vi} from 'vitest';
import {planFixture} from '@/entities/plan';
import {json, mockApi, renderWithProviders} from '@/shared/test/render';
import {AddCategoryModal} from './CategoryModals';

afterEach(() => vi.unstubAllGlobals());

describe('AddCategoryModal', () => {
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
    const onClose = vi.fn();
    renderWithProviders(
      <AddCategoryModal plan={planFixture()} onClose={onClose} />,
    );

    await userEvent.type(screen.getByLabelText('Categoría'), 'materiales');
    await userEvent.click(screen.getByRole('button', {name: 'Agregar'}));
    expect(
      await screen.findByText('Ya existe una categoría con ese nombre.'),
    ).toBeInTheDocument();
    expect(onClose).not.toHaveBeenCalled();

    await userEvent.type(screen.getByLabelText(/^Categoría/), 's');
    expect(
      screen.queryByText('Ya existe una categoría con ese nombre.'),
    ).not.toBeInTheDocument();
  });
});
