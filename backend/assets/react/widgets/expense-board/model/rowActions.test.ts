import {describe, expect, it} from 'vitest';
import {expenseFixture} from '@/entities/expense';
import {expenseRowActions} from './rowActions';

const none = {
  edit: false,
  attach: false,
  approve: false,
  reject: false,
  void: false,
  reimburse: false,
};

describe('expenseRowActions', () => {
  it('makes approving the main action of an expense waiting for this person, rejecting last', () => {
    const plan = expenseRowActions(expenseFixture());
    expect(plan.main).toBe('approve');
    expect(plan.items).toEqual(['view', 'attach', 'receipt']);
    expect(plan.last).toEqual(['reject']);
  });

  it('asks for the receipt first when one is missing and nothing waits on this person', () => {
    const plan = expenseRowActions(
      expenseFixture({
        status: 'APPROVED',
        attachments: [],
        permissions: {...none, attach: true, void: true},
      }),
    );
    expect(plan.main).toBe('attach');
    expect(plan.items).toEqual(['view']);
    expect(plan.last).toEqual(['void']);
  });

  it('offers to correct a rejected expense to the person who recorded it', () => {
    const plan = expenseRowActions(
      expenseFixture({
        status: 'REJECTED',
        permissions: {...none, edit: true},
      }),
    );
    expect(plan.main).toBe('edit');
    expect(plan.items).toEqual(['view', 'receipt']);
  });

  it('opens a settled expense, with its receipt in the menu and voiding last', () => {
    const plan = expenseRowActions(
      expenseFixture({
        status: 'APPROVED',
        permissions: {...none, attach: true, void: true},
      }),
    );
    expect(plan.main).toBe('view');
    expect(plan.items).toEqual(['receipt']);
    expect(plan.last).toEqual(['void']);
  });

  it('shows only view on a voided expense', () => {
    const plan = expenseRowActions(
      expenseFixture({status: 'VOIDED', attachments: [], permissions: none}),
    );
    expect(plan).toEqual({main: 'view', items: [], last: []});
  });
});
