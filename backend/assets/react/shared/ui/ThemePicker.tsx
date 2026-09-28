import {t} from '@/shared/i18n';
import {isThemeChoice, THEME_CHOICES, useTheme} from '@/shared/lib/theme';

/** "Tema": light, dark or the device's, in the sidebar footer. It applies at once and stays in this browser. */
export function ThemePicker() {
  const {choice, choose} = useTheme();

  return (
    <div className="theme-picker">
      <label className="nav-section-title" htmlFor="theme-select">
        {t('theme.label')}
      </label>
      <select
        id="theme-select"
        value={choice}
        onChange={(event) => {
          if (isThemeChoice(event.target.value)) {
            choose(event.target.value);
          }
        }}
      >
        {THEME_CHOICES.map((option) => (
          <option key={option} value={option}>
            {t(`theme.${option}`)}
          </option>
        ))}
      </select>
    </div>
  );
}
