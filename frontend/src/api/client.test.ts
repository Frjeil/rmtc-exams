import { afterEach, describe, expect, it, vi } from 'vitest';
import { ApiError, api, getToken, setToken } from './client';

describe('api client', () => {
  let unauthorizedHandler: (() => void) | null = null;

  afterEach(() => {
    vi.unstubAllGlobals();
    if (unauthorizedHandler) {
      window.removeEventListener('auth:unauthorized', unauthorizedHandler);
      unauthorizedHandler = null;
    }
    localStorage.clear();
  });

  it('sends the Bearer token when present', async () => {
    setToken('abc');
    const fetchMock = vi
      .fn()
      .mockResolvedValue({ ok: true, status: 200, json: async () => ({ data: [] }) });
    vi.stubGlobal('fetch', fetchMock);

    await api.get('/exams');

    const [url, init] = fetchMock.mock.calls[0];
    expect(url).toBe('/api/exams');
    expect((init.headers as Record<string, string>).Authorization).toBe('Bearer abc');
  });

  it('throws ApiError with the server message on failure', async () => {
    vi.stubGlobal(
      'fetch',
      vi.fn().mockResolvedValue({
        ok: false,
        status: 422,
        json: async () => ({ message: 'Errore di test' }),
      }),
    );

    const error = await api.get('/exams').catch((e) => e);
    expect(error).toBeInstanceOf(ApiError);
    if (!(error instanceof ApiError)) {
      throw new Error('expected ApiError');
    }
    expect(error.status).toBe(422);
    expect(error.message).toBe('Errore di test');
  });

  it('clears the token and dispatches unauthorized on 401', async () => {
    setToken('abc');
    unauthorizedHandler = vi.fn();
    window.addEventListener('auth:unauthorized', unauthorizedHandler);
    vi.stubGlobal(
      'fetch',
      vi.fn().mockResolvedValue({
        ok: false,
        status: 401,
        json: async () => ({ message: 'Non autorizzato' }),
      }),
    );

    await api.get('/exams').catch(() => {});

    expect(getToken()).toBeNull();
    expect(unauthorizedHandler).toHaveBeenCalled();
  });

  it('sends a JSON body on post', async () => {
    const fetchMock = vi
      .fn()
      .mockResolvedValue({ ok: true, status: 201, json: async () => ({}) });
    vi.stubGlobal('fetch', fetchMock);

    await api.post('/auth/login', { email: 'a@b.it' });

    const [, init] = fetchMock.mock.calls[0];
    expect(init.method).toBe('POST');
    expect(JSON.parse(init.body as string)).toEqual({ email: 'a@b.it' });
  });
});
