import { afterEach, describe, expect, it, vi } from 'vitest'
import { api, ApiError } from './client'

function mockFetch(status: number, body?: unknown) {
  const fetchMock = vi.fn().mockResolvedValue(
    new Response(body === undefined ? null : JSON.stringify(body), { status, headers: { 'Content-Type': 'application/json' } }),
  )
  vi.stubGlobal('fetch', fetchMock)

  return fetchMock
}

afterEach(() => vi.unstubAllGlobals())

describe('api', () => {
  it('sends JSON with the CSRF header', async () => {
    const fetchMock = mockFetch(200, { id: 1 })

    await expect(api('/projects', { method: 'POST', body: { name: 'X' } })).resolves.toEqual({ id: 1 })

    const [url, init] = fetchMock.mock.calls[0]
    expect(url).toBe('/api/projects')
    expect(init.headers['X-Requested-With']).toBe('XMLHttpRequest')
    expect(init.body).toBe('{"name":"X"}')
  })

  it('returns undefined for 204', async () => {
    mockFetch(204)

    await expect(api('/logout', { method: 'POST' })).resolves.toBeUndefined()
  })

  it('throws ApiError with code and violations', async () => {
    mockFetch(422, { error: 'validation_failed', violations: { name: ['Este valor no debería estar vacío.'] } })

    const error = (await api('/projects').catch((e: unknown) => e)) as ApiError

    expect(error).toBeInstanceOf(ApiError)
    expect(error.status).toBe(422)
    expect(error.code).toBe('validation_failed')
    expect(error.fieldError('name')).toBe('Este valor no debería estar vacío.')
  })
})
