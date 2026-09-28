import {Suspense, useEffect, useState} from 'react';
import {NavLink, Outlet, useLocation, useNavigate} from 'react-router';
import {isManager, useSession, type CurrentUser} from '@/entities/session';
import {ChangePasswordButton} from '@/features/change-password';
import {ImpersonationBanner, ImpersonationPicker} from '@/features/impersonate';
import {t} from '@/shared/i18n';
import {Icon, Loading, ThemePicker, type IconName} from '@/shared/ui';

interface NavItem {
  to: string;
  label: string;
  icon: IconName;
}

/** The sidebar sections a person sees: Team Leads have no dashboard, only Admins the administration. */
function menuFor(
  user: CurrentUser | null,
): {title: string; items: NavItem[]}[] {
  const work: NavItem[] = [
    ...(isManager(user)
      ? [{to: '/dashboard', label: 'nav.dashboard', icon: 'dashboard' as const}]
      : []),
    {to: '/projects', label: 'nav.projects', icon: 'building'},
  ];
  const sections = [{title: 'nav.section.work', items: work}];
  if (user?.admin) {
    sections.push({
      title: 'nav.section.admin',
      items: [
        {to: '/users', label: 'nav.users', icon: 'users'},
        {to: '/settings', label: 'nav.settings', icon: 'settings'},
        {to: '/audit', label: 'nav.audit', icon: 'clock'},
      ],
    });
  }
  sections.push({
    title: 'nav.section.help',
    items: [{to: '/help', label: 'nav.help', icon: 'book'}],
  });

  return sections;
}

function roleLabel(user: CurrentUser | null): string {
  if (user?.superAdmin) {
    return t('roles.SUPER_ADMIN');
  }
  if (user?.admin) {
    return t('roles.ADMIN');
  }
  const roles = new Set((user?.memberships ?? []).map((m) => m.role));

  return roles.size === 0
    ? t('roles.USER')
    : [...roles].map((r) => t(`roles.${r}`)).join(' · ');
}

/**
 * The signed-in layout: a sidebar with the options the person's role may see, "Ver como", the theme, their
 * password and sign out; on narrow screens the sidebar becomes a drawer opened from the top bar.
 */
export function AppShell() {
  const {user, logout} = useSession();
  const location = useLocation();
  const navigate = useNavigate();
  const [open, setOpen] = useState(false);

  // Close the drawer after navigating (adjusted while rendering, not in an effect), and on Escape.
  const [openedOn, setOpenedOn] = useState(location.pathname);
  if (openedOn !== location.pathname) {
    setOpenedOn(location.pathname);
    setOpen(false);
  }
  useEffect(() => {
    if (!open) {
      return undefined;
    }
    const onKey = (event: KeyboardEvent) =>
      event.key === 'Escape' && setOpen(false);
    document.addEventListener('keydown', onKey);

    return () => document.removeEventListener('keydown', onKey);
  }, [open]);

  const signOut = async () => {
    await logout();
    navigate('/login', {replace: true});
  };

  return (
    <div className={`shell ${open ? 'sidebar-open' : ''}`}>
      <header className="mobile-bar">
        <button
          type="button"
          className="icon-btn menu-toggle"
          aria-label={t('common.menu')}
          aria-expanded={open}
          aria-controls="sidebar"
          onClick={() => setOpen(true)}
        >
          <Icon name="menu" size={22} />
        </button>
        <Brand />
      </header>

      <aside id="sidebar" className="sidebar">
        <div className="sidebar-header">
          <Brand />
          <button
            type="button"
            className="icon-btn sidebar-close"
            aria-label={t('common.closeMenu')}
            onClick={() => setOpen(false)}
          >
            <Icon name="close" />
          </button>
        </div>

        <ImpersonationPicker />

        <nav className="sidebar-nav" aria-label={t('nav.label')}>
          {menuFor(user).map((section) => (
            <div key={section.title} className="nav-section">
              <div className="nav-section-title">{t(section.title)}</div>
              {section.items.map((item) => (
                <NavLink
                  key={item.to}
                  to={item.to}
                  className={({isActive}) =>
                    `nav-link ${isActive ? 'active' : ''}`
                  }
                >
                  <Icon name={item.icon} />
                  <span>{t(item.label)}</span>
                </NavLink>
              ))}
            </div>
          ))}
        </nav>

        <div className="sidebar-footer">
          {/* Always the person really signed in, also while viewing as someone else. */}
          <div className="sidebar-user">
            <span className="sidebar-user-name">
              {user?.impersonator?.fullName ?? user?.fullName}
            </span>
            <span className="sidebar-user-role">
              {user?.impersonator ? t('roles.SUPER_ADMIN') : roleLabel(user)}
            </span>
          </div>
          <ThemePicker />
          <ChangePasswordButton />
          <button
            type="button"
            className="btn btn-ghost btn-sm btn-block"
            onClick={() => void signOut()}
          >
            <Icon name="logout" size={16} />
            {t('common.logout')}
          </button>
        </div>
      </aside>

      <div
        className="sidebar-backdrop"
        aria-hidden="true"
        onClick={() => setOpen(false)}
      />

      <main className="content">
        <ImpersonationBanner />
        {/* Pages are lazy chunks: the menu stays while one loads, only the page area waits. */}
        <Suspense fallback={<Loading />}>
          <Outlet />
        </Suspense>
      </main>
    </div>
  );
}

function Brand() {
  return (
    <div className="brand">
      <span className="brand-name">{t('app.name')}</span>
      <span className="brand-subtitle">{t('app.tagline')}</span>
    </div>
  );
}
