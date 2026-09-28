import {
  createContext,
  type ReactNode,
  useContext,
  useEffect,
  useState,
} from 'react';

/** What the person chose: light, dark, or whatever their device is set to. */
export type ThemeChoice = 'light' | 'dark' | 'system';
export type ResolvedTheme = 'light' | 'dark';

export const THEME_CHOICES: readonly ThemeChoice[] = [
  'light',
  'dark',
  'system',
];

/** The choice, kept in this browser. templates/spa.html.twig reads the same key before the page is drawn. */
const KEY = 'dsfk.theme';
const DARK_QUERY = '(prefers-color-scheme: dark)';

export function isThemeChoice(value: unknown): value is ThemeChoice {
  return THEME_CHOICES.includes(value as ThemeChoice);
}

export function resolveTheme(
  choice: ThemeChoice,
  deviceIsDark: boolean,
): ResolvedTheme {
  if (choice === 'system') {
    return deviceIsDark ? 'dark' : 'light';
  }

  return choice;
}

function deviceIsDark(): boolean {
  return (
    typeof window.matchMedia === 'function' &&
    window.matchMedia(DARK_QUERY).matches
  );
}

function stored(): ThemeChoice {
  try {
    const value = window.localStorage.getItem(KEY);

    return isThemeChoice(value) ? value : 'light';
  } catch {
    return 'light';
  }
}

interface ThemeState {
  choice: ThemeChoice;
  resolved: ResolvedTheme;
  choose: (choice: ThemeChoice) => void;
}

const ThemeContext = createContext<ThemeState | null>(null);

export function ThemeProvider({children}: {children: ReactNode}) {
  const [choice, setChoice] = useState<ThemeChoice>(stored);
  const [dark, setDark] = useState(deviceIsDark);

  // "Según el dispositivo" follows the device live, not only when the page loads.
  useEffect(() => {
    if (typeof window.matchMedia !== 'function') {
      return undefined;
    }
    const query = window.matchMedia(DARK_QUERY);
    const onChange = (event: MediaQueryListEvent) => setDark(event.matches);
    query.addEventListener('change', onChange);

    return () => query.removeEventListener('change', onChange);
  }, []);

  const resolved = resolveTheme(choice, dark);
  useEffect(() => {
    document.documentElement.dataset.theme = resolved;
  }, [resolved]);

  const choose = (next: ThemeChoice) => {
    setChoice(next);
    try {
      window.localStorage.setItem(KEY, next);
    } catch {
      // Private mode: the choice lasts until the page is reloaded.
    }
  };

  return (
    <ThemeContext.Provider value={{choice, resolved, choose}}>
      {children}
    </ThemeContext.Provider>
  );
}

export function useTheme(): ThemeState {
  const state = useContext(ThemeContext);
  if (!state) {
    throw new Error('The theme is read outside <ThemeProvider>.');
  }

  return state;
}
