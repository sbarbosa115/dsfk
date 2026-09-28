import {screen, within} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import {afterEach, describe, expect, it, vi} from 'vitest';
import {mockApi, renderWithProviders} from '@/shared/test/render';
import {AuditLog} from './AuditLog';

afterEach(() => vi.unstubAllGlobals());

const entry = {
  id: 7,
  projectId: 9,
  projectName: 'Torre Norte',
  user: 'Laura Gómez (vía Administrador)',
  action: 'update',
  entityType: 'Expense',
  entityId: 71,
  changes: {status: ['SUBMITTED', 'APPROVED'], amount: [120000, 110000]},
  createdAt: '2026-10-15T10:00:00-05:00',
};

describe('AuditLog', () => {
  it('lists who changed what, and shows each field before and after', async () => {
    mockApi({
      'GET /api/audit': {
        items: [entry],
        total: 1,
        page: 1,
        perPage: 50,
        entityTypes: ['Expense', 'Project'],
      },
      'GET /api/projects': {items: [{id: 9, name: 'Torre Norte'}]},
    });
    renderWithProviders(<AuditLog />);

    const row = (
      await screen.findByText('Laura Gómez (vía Administrador)')
    ).closest('tr')!;
    expect(within(row).getByText('Gasto')).toBeInTheDocument();
    expect(within(row).getByText('Modificó')).toBeInTheDocument();
    expect(within(row).getByText('status, amount')).toBeInTheDocument();
    expect(screen.getByRole('option', {name: 'Proyecto'})).toBeInTheDocument();

    await userEvent.click(
      within(row).getByRole('button', {name: 'Ver el cambio'}),
    );
    const dialog = screen.getByRole('dialog');
    const status = within(dialog).getByText('status').closest('tr')!;
    expect(status).toHaveTextContent('SUBMITTED');
    expect(status).toHaveTextContent('APPROVED');
  });
});
