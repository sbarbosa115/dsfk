import {
  type KeyboardEvent,
  type ReactNode,
  type RefObject,
  useCallback,
  useEffect,
  useId,
  useLayoutEffect,
  useRef,
  useState,
} from 'react';
import {createPortal} from 'react-dom';
import {Link} from 'react-router';
import {t} from '@/shared/i18n';
import Icon, {type IconName} from './Icon';
import {type Action, actionClass} from './ui';

/**
 * One thing a row lets you do. The main one is a button; the others are items of the row's menu, where
 * `description` says in a line what the item does. A risky item opens its own modal (with its reason), so the menu
 * never asks by itself.
 */
export interface RowAction {
  label: string;
  action: Action;
  icon?: IconName;
  /** A line under the item in the menu; not shown on the main button. */
  description?: string;
  onClick?: () => void;
  /** An app route (a router Link) instead of onClick. */
  to?: string;
  /** A file or a page outside the app: opens in a new tab. */
  href?: string;
  busy?: boolean;
  disabled?: boolean;
  /** Why it is disabled, as the button's tooltip. */
  title?: string;
}

/** View and edit, folded into the control: a label of their own when "Ver" or "Editar" is not the word. */
export type CommonAction = Omit<RowAction, 'label' | 'action'> & {
  label?: string;
};

/** Turning the row off or on: the last item of the menu. */
export interface ToggleAction {
  active: boolean;
  onClick: () => void;
  busy?: boolean;
  disableLabel?: string;
  enableLabel?: string;
  disableHelp?: string;
  enableHelp?: string;
}

export interface RowActionGroup {
  /** A heading over the group's items ("Este gasto"); none for the last, destructive group. */
  title?: string;
  items: ReadonlyArray<RowAction | false | null | undefined>;
}

type Groups = ReadonlyArray<RowActionGroup | false | null | undefined>;

interface RowActionsProps {
  /** The row in words ("Cinta y señalización"): names the menu's button and heads the sheet on a phone. */
  name: string;
  main?: RowAction | false | null;
  more?: Groups;
  /** Opening the record: a menu item, or the main action when the row has no other. */
  view?: CommonAction | false | null;
  /** Editing the record: the first item of the menu, or the main action when the row has neither another nor view. */
  edit?: CommonAction | false | null;
  /** Turning the row off or on: always the menu's last item. */
  toggle?: ToggleAction | false | null;
  /** A control that changes the row in place (a role dropdown), before the main action. */
  lead?: ReactNode;
}

// The same width as the phone layout of tables in app.css.
const PHONE = '(max-width: 600px)';

/**
 * Folds view, edit and on/off into a row's main action and menu, so no row has icons beside its control: with no
 * main action, view (else edit) becomes it; otherwise they lead the first titled group of the menu. Turning the row
 * off or on is the last item of the last, destructive group.
 */
export function foldCommon(
  main: RowAction | false | null | undefined,
  more: Groups,
  {
    view,
    edit,
    toggle,
  }: {
    view?: CommonAction | false | null;
    edit?: CommonAction | false | null;
    toggle?: ToggleAction | false | null;
  },
): {main: RowAction | null; more: RowActionGroup[]} {
  const groups = more
    .filter((group): group is RowActionGroup => !!group)
    .map((group) => ({...group, items: [...group.items]}));
  const leading: RowAction[] = [];
  if (view) {
    leading.push({
      action: 'open',
      icon: 'eye',
      ...view,
      label: view.label ?? t('rowActions.view'),
    });
  }
  if (edit) {
    leading.push({
      action: 'edit',
      icon: 'pencil',
      ...edit,
      label: edit.label ?? t('common.edit'),
    });
  }

  let first: RowAction | null = main || null;
  if (!first && leading.length > 0) {
    first = leading.shift() ?? null;
  }
  let added: RowActionGroup | null = null;
  if (leading.length > 0) {
    const titled = groups.find((group) => group.title);
    if (titled) {
      titled.items = [...leading, ...titled.items];
    } else {
      groups.unshift((added = {items: leading}));
    }
  }

  if (toggle) {
    const item: RowAction = toggle.active
      ? {
          label: toggle.disableLabel ?? t('common.disable'),
          action: 'danger',
          icon: 'ban',
          description: toggle.disableHelp ?? t('rowActions.disableHelp'),
          onClick: toggle.onClick,
          busy: toggle.busy,
        }
      : {
          label: toggle.enableLabel ?? t('common.enable'),
          action: 'confirm',
          icon: 'check',
          description: toggle.enableHelp ?? t('rowActions.enableHelp'),
          onClick: toggle.onClick,
          busy: toggle.busy,
        };
    const last = groups[groups.length - 1];
    if (last && !last.title && last !== added) {
      last.items = [...last.items, item];
    } else {
      groups.push({items: [item]});
    }
  }
  return {main: first, more: groups};
}

function isPhone(): boolean {
  return (
    typeof window.matchMedia === 'function' && window.matchMedia(PHONE).matches
  );
}

/**
 * Everything a row lets you do, in one control: the row's main action, worded and filled, and a chevron beside it
 * that opens the rest in a menu, grouped and in one order everywhere (worded actions, view and edit, the destructive
 * ones last). Without other actions the chevron goes; without a main action the menu opens from a "Más" button.
 * Goes inside <Actions>. (QA-0004; ported from the MDX kit.)
 */
export function RowActions({
  name,
  main: given,
  more: givenMore = [],
  view,
  edit,
  toggle,
  lead,
}: RowActionsProps) {
  const {main, more} = foldCommon(given, givenMore, {view, edit, toggle});
  const groups = more
    .map((group) => ({
      ...group,
      items: group.items.filter((item): item is RowAction => !!item),
    }))
    .filter((group) => group.items.length > 0);
  const [open, setOpen] = useState(false);
  const trigger = useRef<HTMLButtonElement>(null);
  const menuId = useId();
  const hasMenu = groups.length > 0;

  return (
    <>
      {lead}
      {(main || hasMenu) && (
        <span className={`split${main && hasMenu ? ' is-split' : ''}`}>
          {main && <MainButton action={main} />}
          {hasMenu && (
            <button
              ref={trigger}
              type="button"
              className={`${actionClass(main ? main.action : 'open')} split-toggle${main ? ' is-main' : ' split-more'}`}
              aria-label={main ? t('rowActions.more', {name}) : undefined}
              aria-haspopup="menu"
              aria-expanded={open}
              aria-controls={open ? menuId : undefined}
              onClick={() => setOpen((value) => !value)}
            >
              {!main && <span>{t('rowActions.moreLabel')}</span>}
              <Icon name="chevronDown" size={16} />
            </button>
          )}
        </span>
      )}
      {open && hasMenu && (
        <RowMenu
          id={menuId}
          name={name}
          groups={groups}
          anchor={trigger}
          onClose={() => setOpen(false)}
        />
      )}
    </>
  );
}

/** The icon a main button carries when it names none: the icon helps, the words say it. Setup names its own. */
const MAIN_ICONS: Partial<Record<Action, IconName>> = {
  confirm: 'check',
  danger: 'ban',
  edit: 'pencil',
  open: 'eye',
  file: 'file',
  revert: 'undo',
  contact: 'chat',
};

/** Links leave the app only to the app's own files and web pages; a link built from data never runs a script. */
function safeHref(href: string): boolean {
  const value = href.trim();
  // "//host" and "/\\host" are other sites to a browser, not paths of this one.
  return /^(https?:|\/)/i.test(value) && !/^\/[/\\]/.test(value);
}

const NEW_TAB = {target: '_blank', rel: 'noopener noreferrer'};

function MainButton({action}: {action: RowAction}) {
  const className = actionClass(action.action, 'is-main');
  const icon = action.icon ?? MAIN_ICONS[action.action];
  const content = (
    <>
      {icon && <Icon name={icon} size={16} />}
      {action.busy ? t('common.working') : action.label}
    </>
  );

  if (action.to) {
    return (
      <Link className={className} to={action.to}>
        {content}
      </Link>
    );
  }
  if (action.href && safeHref(action.href)) {
    return (
      <a className={className} href={action.href} {...NEW_TAB}>
        {content}
      </a>
    );
  }
  return (
    <button
      type="button"
      className={className}
      disabled={action.busy || action.disabled}
      title={action.title}
      aria-busy={action.busy || undefined}
      onClick={() => action.onClick?.()}
    >
      {content}
    </button>
  );
}

interface RowMenuProps {
  id: string;
  name: string;
  groups: ReadonlyArray<{title?: string; items: ReadonlyArray<RowAction>}>;
  anchor: RefObject<HTMLButtonElement | null>;
  onClose: () => void;
}

/**
 * The row's other actions: a menu under the chevron (above it near the bottom of the window), or a sheet from the
 * bottom of the screen on a phone. Drawn on <body>, so a table's scroll box never clips it.
 */
function RowMenu({id, name, groups, anchor, onClose}: RowMenuProps) {
  const ref = useRef<HTMLDivElement>(null);
  const [phone] = useState(isPhone);
  const [place, setPlace] = useState<{
    top?: number;
    bottom?: number;
    right: number;
  } | null>(null);

  // Closing hands the focus back to the chevron, so the keyboard stays on the row.
  const close = useCallback(() => {
    anchor.current?.focus();
    onClose();
  }, [anchor, onClose]);

  // Under the chevron, or above it when the window has no room below; recomputed when the page scrolls, so the
  // menu stays with its row. A row scrolled out of the window takes its menu with it.
  const reposition = useCallback(() => {
    if (phone || !anchor.current || !ref.current) {
      return true;
    }
    const box = anchor.current.getBoundingClientRect();
    if (box.bottom < 0 || box.top > window.innerHeight) {
      return false;
    }
    const height = ref.current.offsetHeight;
    const right = Math.max(8, window.innerWidth - box.right);
    setPlace(
      box.bottom + 6 + height > window.innerHeight - 8 &&
        box.top - 6 - height > 8
        ? {bottom: window.innerHeight - box.top + 6, right}
        : {top: box.bottom + 6, right},
    );
    return true;
  }, [anchor, phone]);

  useLayoutEffect(() => {
    reposition();
    ref.current?.querySelector<HTMLElement>('[role="menuitem"]')?.focus();
  }, [reposition]);

  // A click outside or a resize closes it, Esc closes it back on the chevron, and a scroll moves it with its row.
  useEffect(() => {
    const away = (event: Event) => {
      const target = event.target as Node;
      if (ref.current?.contains(target) || anchor.current?.contains(target)) {
        return;
      }
      onClose();
    };
    const escape = (event: globalThis.KeyboardEvent) => {
      if (event.key === 'Escape') {
        close();
      }
    };
    const scrolled = (event: Event) => {
      if (
        phone ||
        (event.target instanceof Node && ref.current?.contains(event.target))
      ) {
        return;
      }
      if (!reposition()) {
        onClose();
      }
    };
    document.addEventListener('mousedown', away);
    document.addEventListener('keydown', escape);
    window.addEventListener('scroll', scrolled, true);
    window.addEventListener('resize', onClose);
    return () => {
      document.removeEventListener('mousedown', away);
      document.removeEventListener('keydown', escape);
      window.removeEventListener('scroll', scrolled, true);
      window.removeEventListener('resize', onClose);
    };
  }, [anchor, close, onClose, phone, reposition]);

  const move = (event: KeyboardEvent) => {
    if (event.key !== 'ArrowDown' && event.key !== 'ArrowUp') {
      return;
    }
    event.preventDefault();
    const items = [
      ...(ref.current?.querySelectorAll<HTMLElement>('[role="menuitem"]') ??
        []),
    ];
    const at = items.indexOf(document.activeElement as HTMLElement);
    const next =
      event.key === 'ArrowDown'
        ? (at + 1) % items.length
        : (at - 1 + items.length) % items.length;
    items[next]?.focus();
  };

  const menu = (
    <div
      ref={ref}
      id={id}
      role="menu"
      aria-label={name}
      className={phone ? 'row-menu is-tinted is-sheet' : 'row-menu is-tinted'}
      style={
        phone
          ? undefined
          : {
              ...(place ?? {top: -9999, right: 0}),
              visibility: place ? 'visible' : 'hidden',
            }
      }
      onKeyDown={move}
    >
      {phone && <div className="row-menu-title">{name}</div>}
      {groups.map((group, gi) => (
        <div
          key={group.title ?? `group-${gi}`}
          className="row-menu-group"
          role="group"
          aria-label={group.title}
        >
          {gi > 0 && <hr />}
          {group.title && <div className="row-menu-heading">{group.title}</div>}
          {group.items.map((item) => (
            <MenuItem key={`${gi}:${item.label}`} item={item} onDone={close} />
          ))}
        </div>
      ))}
    </div>
  );

  return createPortal(
    phone ? (
      <div
        className="row-menu-scrim"
        onMouseDown={(event) =>
          event.target === event.currentTarget && onClose()
        }
      >
        {menu}
      </div>
    ) : (
      menu
    ),
    document.body,
  );
}

function MenuItem({item, onDone}: {item: RowAction; onDone: () => void}) {
  const body = (
    <>
      {item.icon && <Icon name={item.icon} size={16} />}
      <span className="row-menu-text">
        <span className="row-menu-label">{item.label}</span>
        {item.description && (
          <span className="row-menu-desc">{item.description}</span>
        )}
      </span>
    </>
  );
  const className = `row-menu-item tone-${item.action}`;

  if (item.to) {
    return (
      <Link role="menuitem" className={className} to={item.to} onClick={onDone}>
        {body}
      </Link>
    );
  }
  if (item.href && safeHref(item.href)) {
    return (
      <a
        role="menuitem"
        className={className}
        href={item.href}
        {...NEW_TAB}
        onClick={onDone}
      >
        {body}
      </a>
    );
  }
  return (
    <button
      type="button"
      role="menuitem"
      className={className}
      disabled={item.disabled || item.busy}
      onClick={() => {
        onDone();
        // After the menu has gone: a modal the item opens then takes the focus, and gives it back to the chevron.
        const run = item.onClick;
        if (run) {
          window.setTimeout(run, 0);
        }
      }}
    >
      {body}
    </button>
  );
}
