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

  /**
   * The first message of every field, keyed by the field's name (an item of a list field, "items[2]", and a
   * sub-field, "a.b", count for the field).
   */
  fieldErrors(): Record<string, string> {
    const errors: Record<string, string> = {};
    for (const [key, messages] of Object.entries(this.violations)) {
      const field = key.split(/[[.]/)[0] ?? key;
      if (errors[field] === undefined && messages[0] !== undefined) {
        errors[field] = messages[0];
      }
    }

    return errors;
  }

  /** The first message for a field, including messages on its items ("items[2]") or sub-fields ("a.b"). */
  fieldError(field: string): string | undefined {
    const key = Object.keys(this.violations).find(
      (k) =>
        k === field || k.startsWith(`${field}[`) || k.startsWith(`${field}.`),
    );

    return key === undefined ? undefined : this.violations[key]?.[0];
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
