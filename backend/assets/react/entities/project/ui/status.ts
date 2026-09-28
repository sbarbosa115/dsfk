import {t} from '@/shared/i18n';
import type {ProjectStatus} from '../api/projectApi';

/** The row tone of a project status (shared/ui TONES: project_draft, project_active…). */
export function projectTone(status: ProjectStatus): string {
  return `project_${status.toLowerCase()}`;
}

export function projectStatusLabel(status: ProjectStatus): string {
  return t(`projects.status.${status}`);
}
