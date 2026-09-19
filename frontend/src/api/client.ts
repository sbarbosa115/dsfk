export type Violations = Record<string, string[]>

export class ApiError extends Error {
  readonly status: number
  readonly code: string
  readonly violations: Violations

  constructor(status: number, code: string, violations: Violations = {}) {
    super(code)
    this.status = status
    this.code = code
    this.violations = violations
  }

  fieldError(field: string): string | undefined {
    return this.violations[field]?.[0]
  }
}

/**
 * JSON fetch against the Symfony API. Uses the session cookie and always sends the
 * header the backend requires as CSRF protection.
 */
export async function api<T>(path: string, options: { method?: string; body?: unknown } = {}): Promise<T> {
  const response = await fetch(`/api${path}`, {
    method: options.method ?? 'GET',
    credentials: 'same-origin',
    headers: {
      Accept: 'application/json',
      'Content-Type': 'application/json',
      'X-Requested-With': 'XMLHttpRequest',
    },
    body: options.body === undefined ? undefined : JSON.stringify(options.body),
  })

  if (response.status === 204) {
    return undefined as T
  }

  const data = await response.json().catch(() => ({}))
  if (!response.ok) {
    throw new ApiError(response.status, data.error ?? 'unknown_error', data.violations ?? {})
  }

  return data as T
}

/** Multipart upload of one file (field "file"). */
export async function upload<T>(path: string, file: File): Promise<T> {
  const body = new FormData()
  body.append('file', file)
  const response = await fetch(`/api${path}`, {
    method: 'POST',
    credentials: 'same-origin',
    headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
    body,
  })
  const data = await response.json().catch(() => ({}))
  if (!response.ok) {
    throw new ApiError(response.status, data.error ?? 'unknown_error', data.violations ?? {})
  }

  return data as T
}
