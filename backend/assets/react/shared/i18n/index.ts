import i18n from 'i18next';
import {initReactI18next} from 'react-i18next';
import {es} from './es';

void i18n.use(initReactI18next).init({
  resources: {es: {translation: es}},
  lng: 'es',
  fallbackLng: 'es',
  interpolation: {escapeValue: false},
  initAsync: false,
});

/** Every visible string goes through here: t('users.title'), t('impersonation.back', {admin}). */
export const t = i18n.t.bind(i18n);

/** The one locale the app writes numbers and dates in. */
export const LOCALE = 'es-CO';

export {i18n};
