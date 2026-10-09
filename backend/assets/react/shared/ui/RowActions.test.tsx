import {fireEvent, render, screen, within} from '@testing-library/react';
import type {ReactElement} from 'react';
import {MemoryRouter} from 'react-router';
import {describe, expect, it, vi} from 'vitest';
import {RowActions, type RowActionGroup} from './RowActions';
import {Actions} from './ui';

function draw(ui: ReactElement) {
  return render(
    <MemoryRouter>
      <table>
        <tbody>
          <tr>
            <Actions>{ui}</Actions>
          </tr>
        </tbody>
      </table>
    </MemoryRouter>,
  );
}

const groups = (onVoid = vi.fn()): RowActionGroup[] => [
  {
    title: 'Este gasto',
    items: [
      {
        label: 'Ver gasto',
        action: 'open',
        icon: 'eye',
        description: 'Datos, recibos e historial',
        onClick: vi.fn(),
      },
      false,
    ],
  },
  {items: [{label: 'Anular', action: 'danger', icon: 'ban', onClick: onVoid}]},
];

const labels = () =>
  screen
    .getAllByRole('menuitem')
    .map((item) => item.querySelector('.row-menu-label')?.textContent);

describe('RowActions', () => {
  it('shows only the main action when the row has nothing else to do', () => {
    draw(
      <RowActions
        name="Cinta y señalización"
        main={{label: 'Aprobar', action: 'confirm', onClick: vi.fn()}}
      />,
    );
    expect(screen.getByRole('button', {name: 'Aprobar'})).toBeInTheDocument();
    expect(screen.queryByRole('button', {name: /Más acciones/})).toBeNull();
  });

  it('fills the main action and its chevron: the row colour says whose turn it is', () => {
    draw(
      <RowActions
        name="Cinta y señalización"
        main={{label: 'Aprobar', action: 'confirm', onClick: vi.fn()}}
        more={groups()}
      />,
    );
    expect(screen.getByRole('button', {name: 'Aprobar'})).toHaveClass(
      'is-main',
      'btn-action-confirm',
    );
    expect(
      screen.getByRole('button', {name: 'Más acciones: Cinta y señalización'}),
    ).toHaveClass('is-main');
  });

  it("colours each menu item with its own action, on that action's tint", () => {
    draw(
      <RowActions
        name="Cinta"
        main={{label: 'Aprobar', action: 'confirm', onClick: vi.fn()}}
        more={groups()}
      />,
    );
    fireEvent.click(screen.getByRole('button', {name: 'Más acciones: Cinta'}));
    expect(screen.getByRole('menu')).toHaveClass('is-tinted');
    expect(screen.getByRole('menuitem', {name: /Ver gasto/})).toHaveClass(
      'tone-open',
    );
    expect(screen.getByRole('menuitem', {name: /Anular/})).toHaveClass(
      'tone-danger',
    );
  });

  it('folds view, edit and on/off into the control: edit leads the menu, turning off is its last item', async () => {
    const toggle = vi.fn();
    draw(
      <RowActions
        name="Ana Torres"
        main={{label: 'Abrir', action: 'open', to: '/projects/1'}}
        more={groups()}
        edit={{onClick: vi.fn()}}
        toggle={{active: true, onClick: toggle}}
      />,
    );
    expect(
      screen.queryByRole('button', {name: 'Editar'}),
      'no icon beside the control',
    ).toBeNull();
    fireEvent.click(
      screen.getByRole('button', {name: 'Más acciones: Ana Torres'}),
    );
    expect(labels()).toEqual(['Editar', 'Ver gasto', 'Anular', 'Desactivar']);
    expect(screen.getByRole('menuitem', {name: /Desactivar/})).toHaveClass(
      'tone-danger',
    );
    fireEvent.click(screen.getByRole('menuitem', {name: /Desactivar/}));
    await vi.waitFor(() => expect(toggle).toHaveBeenCalled());
  });

  it('makes view, else edit, the main action when the row has no other', () => {
    const view = vi.fn();
    const {unmount} = draw(
      <RowActions name="Ciclo 1" view={{label: 'Ver ciclo', onClick: view}} />,
    );
    fireEvent.click(screen.getByRole('button', {name: 'Ver ciclo'}));
    expect(view, 'the main button runs at once').toHaveBeenCalled();
    expect(screen.getByRole('button', {name: 'Ver ciclo'})).toHaveClass(
      'is-main',
    );
    expect(screen.queryByRole('button', {name: /Más/})).toBeNull();
    unmount();

    draw(
      <RowActions
        name="Pedro"
        edit={{onClick: vi.fn()}}
        toggle={{active: false, onClick: vi.fn()}}
      />,
    );
    expect(screen.getByRole('button', {name: 'Editar'})).toHaveClass(
      'btn-action-edit',
    );
    fireEvent.click(screen.getByRole('button', {name: 'Más acciones: Pedro'}));
    expect(
      screen.getByRole('menuitem', {name: /Activar/}),
      'turning a row back on is a confirm',
    ).toHaveClass('tone-confirm');
  });

  it('opens the rest in a menu, grouped, with a line on each item, and runs an item', async () => {
    const more = groups();
    draw(
      <RowActions
        name="Cinta"
        main={{label: 'Aprobar', action: 'confirm', onClick: vi.fn()}}
        more={more}
      />,
    );
    fireEvent.click(screen.getByRole('button', {name: 'Más acciones: Cinta'}));
    const menu = screen.getByRole('menu', {name: 'Cinta'});
    expect(within(menu).getByText('Este gasto')).toBeInTheDocument();
    expect(
      within(menu).getByText('Datos, recibos e historial'),
    ).toBeInTheDocument();
    expect(labels()).toEqual(['Ver gasto', 'Anular']);
    expect(document.activeElement, 'the first item takes the focus').toBe(
      within(menu).getAllByRole('menuitem')[0],
    );

    fireEvent.click(within(menu).getByRole('menuitem', {name: /Ver gasto/}));
    const view = (more[0]!.items[0] as {onClick: () => void}).onClick;
    await vi.waitFor(() => expect(view).toHaveBeenCalled());
    expect(screen.queryByRole('menu'), 'an item closes the menu').toBeNull();
    expect(document.activeElement, 'the focus is back on the chevron').toBe(
      screen.getByRole('button', {name: 'Más acciones: Cinta'}),
    );
  });

  it('moves through the items with the arrows and closes on Esc, back on its chevron', () => {
    draw(
      <RowActions
        name="Cinta"
        main={{label: 'Aprobar', action: 'confirm', onClick: vi.fn()}}
        more={groups()}
      />,
    );
    const chevron = screen.getByRole('button', {name: 'Más acciones: Cinta'});
    chevron.focus();
    fireEvent.click(chevron);
    const items = screen.getAllByRole('menuitem');
    fireEvent.keyDown(items[0]!, {key: 'ArrowDown'});
    expect(document.activeElement).toBe(items[1]);
    fireEvent.keyDown(items[1]!, {key: 'ArrowDown'});
    expect(document.activeElement, 'it wraps around').toBe(items[0]);
    fireEvent.keyDown(document, {key: 'Escape'});
    expect(screen.queryByRole('menu')).toBeNull();
    expect(document.activeElement).toBe(chevron);
  });

  it('opens the menu from "Más" when the row has no main action', () => {
    draw(<RowActions name="Movimiento" more={groups()} />);
    const more = screen.getByRole('button', {name: /Más/});
    expect(more).toHaveAttribute('aria-haspopup', 'menu');
    fireEvent.click(more);
    expect(screen.getByRole('menu')).toBeInTheDocument();
  });

  it('opens a file in a new tab from the menu, and never a script', () => {
    draw(
      <RowActions
        name="Depósito"
        main={{
          label: 'Comprobante',
          action: 'file',
          href: 'javascript:alert(1)',
        }}
        more={[
          {
            items: [
              {
                label: 'Recibo',
                action: 'file',
                href: '/api/attachments/3',
              },
            ],
          },
        ]}
      />,
    );
    expect(
      screen.getByText('Comprobante').closest('a'),
      'a javascript: address is not a link',
    ).toBeNull();
    fireEvent.click(screen.getByRole('button', {name: /Más acciones/}));
    const receipt = screen.getByRole('menuitem', {name: /Recibo/});
    expect(receipt).toHaveAttribute('href', '/api/attachments/3');
    expect(receipt).toHaveAttribute('target', '_blank');
  });

  it('never links to another site through a path the browser reads as one (//host, /\\host)', () => {
    draw(
      <RowActions
        name="Depósito"
        more={[
          {
            items: [
              {label: 'Uno', action: 'file', href: '//evil.test/x'},
              {label: 'Dos', action: 'file', href: '/\\evil.test/x'},
            ],
          },
        ]}
      />,
    );
    fireEvent.click(screen.getByRole('button', {name: /Más/}));
    for (const item of screen.getAllByRole('menuitem')) {
      expect(item.tagName).toBe('BUTTON');
    }
  });

  it('closes once its row scrolls out of the window', () => {
    draw(
      <RowActions
        name="Cinta"
        main={{label: 'Aprobar', action: 'confirm', onClick: vi.fn()}}
        more={groups()}
      />,
    );
    const chevron = screen.getByRole('button', {name: 'Más acciones: Cinta'});
    fireEvent.click(chevron);
    fireEvent.scroll(window);
    expect(screen.getByRole('menu')).toBeInTheDocument();

    chevron.getBoundingClientRect = () => ({
      top: -80,
      bottom: -48,
      left: 0,
      right: 30,
      width: 30,
      height: 32,
      x: 0,
      y: -80,
      toJSON: () => ({}),
    });
    fireEvent.scroll(window);
    expect(screen.queryByRole('menu')).toBeNull();
  });

  it("carries its action's icon beside the words, except setup, which names its own", () => {
    const {unmount} = draw(
      <RowActions
        name="Cinta"
        main={{label: 'Aprobar', action: 'confirm', onClick: vi.fn()}}
      />,
    );
    expect(
      screen.getByRole('button', {name: 'Aprobar'}).querySelector('svg'),
    ).not.toBeNull();
    unmount();
    draw(
      <RowActions
        name="Depósito"
        main={{label: 'Adjuntar', action: 'setup', onClick: vi.fn()}}
      />,
    );
    expect(
      screen.getByRole('button', {name: 'Adjuntar'}).querySelector('svg'),
    ).toBeNull();
  });
});
