import type { TFunction } from 'i18next'
import { ApiError } from '../api/client'

/** Human (Spanish) message for an API error code. */
export function errorMessage(t: TFunction, error: unknown): string {
  if (error instanceof ApiError) {
    return t(`errors.${error.code}`, { defaultValue: t('errors.generic') })
  }

  return t('errors.generic')
}

/** Validation message for one field, if the error is a 422 with violations. */
export function fieldError(error: unknown, field: string): string | undefined {
  return error instanceof ApiError ? error.fieldError(field) : undefined
}
