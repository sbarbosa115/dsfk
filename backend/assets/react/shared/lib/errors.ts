import {ApiError} from '@/shared/api';
import {t} from '@/shared/i18n';

/** Human (Spanish) message for an API error, by its code. */
export function errorMessage(error: unknown): string {
  if (error instanceof ApiError) {
    return t(`errors.${error.code}`, {defaultValue: t('errors.generic')});
  }

  return t('errors.generic');
}

/** Validation message for one field, when the error is a 422 with violations. */
export function fieldError(error: unknown, field: string): string | undefined {
  return error instanceof ApiError ? error.fieldError(field) : undefined;
}
