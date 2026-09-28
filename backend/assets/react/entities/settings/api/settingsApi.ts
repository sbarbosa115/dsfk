import {api, type Schema} from '@/shared/api';

export type Settings = Schema<'SettingsOutput'>;
export type SettingsBody = Schema<'SettingsInput'>;

export const SETTINGS_KEY = ['settings'];

export function fetchSettings(): Promise<Settings> {
  return api<Settings>('/settings');
}

export function saveSettings(body: SettingsBody): Promise<Settings> {
  return api<Settings>('/settings', {method: 'PUT', body});
}
