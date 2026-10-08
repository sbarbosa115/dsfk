import {render, screen} from '@testing-library/react';
import {describe, expect, it, vi} from 'vitest';
import {ConfirmModal} from './ConfirmModal';
import {
  ActionButton,
  Actions,
  DataTable,
  FormModal,
  ListView,
  Row,
  SubmitButton,
} from './ui';

const idle = {busy: false, formError: null};

describe('ActionButton', () => {
  it('is outlined in its colour, and filled when it is the main action', () => {
    render(
      <>
        <ActionButton action="setup">Agregar partida</ActionButton>
        <ActionButton action="setup" main>
          Agregar etapa
        </ActionButton>
      </>,
    );

    const outlined = screen.getByRole('button', {name: 'Agregar partida'});
    const filled = screen.getByRole('button', {name: 'Agregar etapa'});
    expect(outlined).toHaveClass('btn-action', 'btn-action-setup');
    expect(outlined).not.toHaveClass('is-main');
    expect(filled).toHaveClass('btn-action-setup', 'is-main');
  });
});

describe('SubmitButton', () => {
  it('saves in green by default and takes the colour of what the form does', () => {
    render(
      <>
        <SubmitButton />
        <SubmitButton action="danger">Rechazar</SubmitButton>
      </>,
    );

    const save = screen.getByRole('button', {name: 'Guardar'});
    expect(save).toHaveAttribute('type', 'submit');
    expect(save).toHaveClass('btn-action-confirm', 'is-main');
    expect(screen.getByRole('button', {name: 'Rechazar'})).toHaveClass(
      'btn-action-danger',
      'is-main',
    );
  });
});

describe('FormModal', () => {
  it("fills its submit in the colour of the form's action, with a plain Cancelar beside it", () => {
    render(
      <FormModal
        title="Rechazar gasto"
        onClose={vi.fn()}
        onSubmit={vi.fn()}
        submit={idle}
        submitLabel="Rechazar"
        action="danger"
      >
        <span>Motivo</span>
      </FormModal>,
    );

    expect(screen.getByRole('button', {name: 'Rechazar'})).toHaveClass(
      'btn-action-danger',
      'is-main',
    );
    expect(screen.getByRole('button', {name: 'Cancelar'})).toHaveClass(
      'btn-ghost',
    );
  });

  it('saves in green when it does not say otherwise', () => {
    render(
      <FormModal
        title="Editar"
        onClose={vi.fn()}
        onSubmit={vi.fn()}
        submit={idle}
      >
        <span>Campos</span>
      </FormModal>,
    );

    expect(screen.getByRole('button', {name: 'Guardar'})).toHaveClass(
      'btn-action-confirm',
      'is-main',
    );
  });
});

describe('ConfirmModal', () => {
  it('fills its confirming button in the colour of the action', () => {
    render(
      <ConfirmModal
        title="Firmar el ciclo 2"
        confirmLabel="Firmar"
        action="confirm"
        onConfirm={vi.fn()}
        onClose={vi.fn()}
      >
        Revisaste los movimientos del ciclo.
      </ConfirmModal>,
    );

    expect(screen.getByRole('button', {name: 'Firmar'})).toHaveClass(
      'btn-action-confirm',
      'is-main',
    );
  });
});

describe('DataTable', () => {
  it('names every cell\'s column, for a phone to show as "Label: value", except the title and the actions', () => {
    render(
      <DataTable
        columns={['Fecha', 'Gasto', 'Monto']}
        rows={[{id: 1}]}
        renderRow={(row) => (
          <Row key={row.id} status="expense_submitted" label="Pendiente">
            <td>4 oct 2026</td>
            <td>Cinta</td>
            <td className="num">$ 95.000</td>
            <Actions>
              <span>acciones</span>
            </Actions>
          </Row>
        )}
      />,
    );

    const cells = screen.getAllByRole('cell');
    expect(cells.map((cell) => cell.getAttribute('data-label'))).toEqual([
      null,
      'Gasto',
      'Monto',
      null,
    ]);
    expect(screen.getByRole('row', {name: /Cinta/})).toHaveClass(
      'row-tone-info',
    );
  });

  it('says it is empty inside the table, under its header', () => {
    render(
      <DataTable
        columns={['Etapa', 'Presupuesto']}
        rows={[]}
        renderRow={() => null}
        empty="Las etapas aparecen aquí cuando el presupuesto está aprobado."
      />,
    );

    expect(
      screen.getByRole('columnheader', {name: 'Presupuesto'}),
    ).toBeInTheDocument();
    const state = screen.getByRole('cell', {
      name: 'Las etapas aparecen aquí cuando el presupuesto está aprobado.',
    });
    expect(state).toHaveAttribute('colspan', '3');
    expect(state.closest('tr')).toHaveClass('table-state');
  });
});

describe('ListView', () => {
  const list = (items: object[], filters = {q: ''}) => ({
    data: {items, total: items.length, page: 1, perPage: 50},
    error: null,
    loading: false,
    reload: vi.fn(),
    filters,
    update: vi.fn(),
    setPage: vi.fn(),
  });

  it('keeps the column headers over an empty list, with what the section is for and its action', () => {
    render(
      <ListView
        list={list([])}
        columns={['Fecha', 'Movimiento']}
        renderRow={() => null}
        empty="Nada coincide."
        emptyAll="Aún no hay movimientos de dinero."
        emptyAction={<button type="button">Registrar depósito</button>}
      />,
    );

    expect(
      screen.getByRole('columnheader', {name: 'Movimiento'}),
    ).toBeInTheDocument();
    const state = screen
      .getByText('Aún no hay movimientos de dinero.')
      .closest('tr');
    expect(state).toHaveClass('table-state');
    expect(
      screen.getByRole('button', {name: 'Registrar depósito'}),
    ).toBeInTheDocument();
  });

  it('offers to show everything when the filters hide every row', async () => {
    const result = list([], {q: 'cemento'});
    render(
      <ListView
        list={result}
        columns={['Fecha']}
        renderRow={() => null}
        showAll={{}}
        empty="Nada coincide con la búsqueda."
      />,
    );

    screen.getByRole('button', {name: 'Ver todos'}).click();
    expect(result.update).toHaveBeenCalledWith({q: ''});
  });
});
