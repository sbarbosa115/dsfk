export type Violations = Record<string, string[]>;

/** An API error: a stable snake_case code the UI translates, plus any extra data. */
export class ApiError extends Error {
  readonly status: number;
  readonly code: string;
  readonly violations: Violations;
  readonly data: Record<string, unknown>;

  constructor(status: number, data: Record<string, unknown>) {
    const code =
      typeof data['error'] === 'string' ? data['error'] : 'unknown_error';
    super(code);
    this.status = status;
    this.code = code;
    this.violations = (data['violations'] as Violations | undefined) ?? {};
    this.data = data;
  }

  fieldError(field: string): string | undefined {
    return this.violations[field]?.[0];
  }
}

const CSRF_HEADER = {'X-Requested-With': 'XMLHttpRequest'};

async function parse<T>(response: Response): Promise<T> {
  if (response.status === 204) {
    return undefined as T;
  }
  const data: unknown = await response.json().catch(() => ({}));
  if (!response.ok) {
    throw new ApiError(
      response.status,
      typeof data === 'object' && data !== null
        ? (data as Record<string, unknown>)
        : {},
    );
  }

  return data as T;
}

/**
 * JSON call against the Symfony API with the session cookie. Always sends the header the backend requires as
 * CSRF protection.
 */
export async function api<T>(
  path: string,
  options: {method?: string; body?: unknown} = {},
): Promise<T> {
  const response = await fetch(`/api${path}`, {
    method: options.method ?? 'GET',
    credentials: 'same-origin',
    headers: {
      'Accept': 'application/json',
      'Content-Type': 'application/json',
      ...CSRF_HEADER,
    },
    body: options.body === undefined ? undefined : JSON.stringify(options.body),
  });

  return parse<T>(response);
}

/** Multipart upload of one file (field "file"). */
export async function upload<T>(path: string, file: File): Promise<T> {
  const body = new FormData();
  body.append('file', file);
  const response = await fetch(`/api${path}`, {
    method: 'POST',
    credentials: 'same-origin',
    headers: {Accept: 'application/json', ...CSRF_HEADER},
    body,
  });

  return parse<T>(response);
}
