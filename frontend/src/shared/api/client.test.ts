import {afterEach, describe, expect, it, vi} from 'vitest';
import {api, ApiError, upload} from './client';

function mockFetch(status: number, body?: unknown) {
  const fetchMock = vi.fn().mockResolvedValue(
    new Response(body === undefined ? null : JSON.stringify(body), {
      status,
      headers: {'Content-Type': 'application/json'},
    }),
  );
  vi.stubGlobal('fetch', fetchMock);

  return fetchMock;
}

afterEach(() => vi.unstubAllGlobals());

describe('api', () => {
  it('sends JSON with the CSRF header the backend requires on writes', async () => {
    const fetchMock = mockFetch(200, {id: 1});

    await expect(
      api('/projects', {method: 'POST', body: {name: 'X'}}),
    ).resolves.toEqual({id: 1});

    const [url, init] = fetchMock.mock.calls[0]!;
    expect(url).toBe('/api/projects');
    expect(init.headers['X-Requested-With']).toBe('XMLHttpRequest');
    expect(init.body).toBe('{"name":"X"}');
  });

  it('returns undefined for 204 No Content', async () => {
    mockFetch(204);

    await expect(api('/logout', {method: 'POST'})).resolves.toBeUndefined();
  });

  it('throws an ApiError carrying the code, violations and extra data', async () => {
    mockFetch(422, {
      error: 'insufficient_funds',
      available: '1000.00',
      violations: {amount: ['Fondos insuficientes.']},
    });

    const error = (await api('/x').catch((e: unknown) => e)) as ApiError;

    expect(error).toBeInstanceOf(ApiError);
    expect(error.status).toBe(422);
    expect(error.code).toBe('insufficient_funds');
    expect(error.fieldError('amount')).toBe('Fondos insuficientes.');
    expect(error.data['available']).toBe('1000.00');
  });

  it('falls back to unknown_error when the body is not JSON', async () => {
    vi.stubGlobal(
      'fetch',
      vi.fn().mockResolvedValue(new Response('<html>', {status: 500})),
    );

    await expect(api('/x')).rejects.toMatchObject({code: 'unknown_error'});
  });
});

describe('upload', () => {
  it('posts the file as multipart with the CSRF header', async () => {
    const fetchMock = mockFetch(201, {id: 7});
    const file = new File(['%PDF'], 'a.pdf', {type: 'application/pdf'});

    await expect(upload('/expenses/1/attachments', file)).resolves.toEqual({
      id: 7,
    });

    const [, init] = fetchMock.mock.calls[0]!;
    expect(init.headers['X-Requested-With']).toBe('XMLHttpRequest');
    expect((init.body as FormData).get('file')).toBeInstanceOf(File);
  });
});
