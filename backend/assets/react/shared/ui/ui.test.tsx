import {render, screen} from '@testing-library/react';
import {describe, expect, it, vi} from 'vitest';
import {ConfirmModal} from './ConfirmModal';
import {ActionButton, FormModal, SubmitButton} from './ui';

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
