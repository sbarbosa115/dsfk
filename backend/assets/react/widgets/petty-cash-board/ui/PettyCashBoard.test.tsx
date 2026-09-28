import {screen, within} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import {afterEach, describe, expect, it, vi} from 'vitest';
import {pettyCashFixture} from '@/entities/petty-cash';
import {mockApi, renderWithProviders, sentBody} from '@/shared/test/render';
import {PettyCashBoard} from './PettyCashBoard';

const text = (value: string) => value.replace(/\s/g, ' ');

afterEach(() => vi.unstubAllGlobals());

describe('PettyCashBoard', () => {
  it('shows the balance, the current cycle’s movements and the closed cycles', async () => {
    mockApi({'GET /api/projects/9/petty-cash': pettyCashFixture()});
    renderWithProviders(<PettyCashBoard projectId={9} />);

    const balance = await screen.findByText('Saldo');
    expect(text(balance.parentElement!.textContent ?? '')).toContain(
      '$ 255.000',
    );
    expect(screen.getByText('Ciclo 2 (actual)')).toBeInTheDocument();
    const clavos = screen.getByText('Clavos').closest('tr')!;
    expect(text(clavos.textContent ?? '')).toContain('-$ 45.000');
    expect(within(clavos).getByRole('link', {name: 'Recibo'})).toHaveAttribute(
      'href',
      '/api/attachments/9',
    );
    const closed = screen.getByText('#1').closest('tr')!;
    expect(closed).toHaveAttribute('title', 'Cerrado, por firmar');
    expect(
      within(closed).queryByRole('button', {name: 'Firmar'}),
    ).not.toBeInTheDocument();
  });

  it('closes the current cycle with a note', async () => {
    const fetchMock = mockApi({
      'GET /api/projects/9/petty-cash': pettyCashFixture(),
      'POST /api/projects/9/petty-cash/close': pettyCashFixture().current,
    });
    renderWithProviders(<PettyCashBoard projectId={9} />);

    await userEvent.click(
      await screen.findByRole('button', {name: 'Cerrar ciclo'}),
    );
    const dialog = screen.getByRole('dialog');
    expect(text(dialog.textContent ?? '')).toContain('saldo de $ 255.000');
    await userEvent.type(
      within(dialog).getByLabelText(/^Nota de cierre/),
      'Fin de mes',
    );
    await userEvent.click(
      within(dialog).getByRole('button', {name: 'Cerrar ciclo'}),
    );

    await vi.waitFor(() =>
      expect(
        sentBody(fetchMock, 'POST', '/api/projects/9/petty-cash/close'),
      ).toEqual({note: 'Fin de mes'}),
    );
  });

  it('lets an Admin sign off a closed cycle', async () => {
    const fetchMock = mockApi({
      'GET /api/projects/9/petty-cash': pettyCashFixture({
        permissions: {close: true, signOff: true},
      }),
      'POST /api/petty-cash-cycles/1/sign-off': pettyCashFixture().history[0],
    });
    renderWithProviders(<PettyCashBoard projectId={9} />);

    const closed = (await screen.findByText('#1')).closest('tr')!;
    await userEvent.click(within(closed).getByRole('button', {name: 'Firmar'}));
    await userEvent.click(
      within(screen.getByRole('dialog')).getByRole('button', {name: 'Firmar'}),
    );

    await vi.waitFor(() =>
      expect(
        fetchMock.mock.calls.some(
          ([url, init]) =>
            url === '/api/petty-cash-cycles/1/sign-off' &&
            init?.method === 'POST',
        ),
      ).toBe(true),
    );
  });
});
