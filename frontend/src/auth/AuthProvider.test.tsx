import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { render, screen } from '@testing-library/react';
import { AuthProvider } from './AuthProvider';
import { useAuth } from './context';

function Probe() {
  const { user, ready } = useAuth();

  return <div>{ready ? (user ? user.email : 'anonimo') : 'loading'}</div>;
}

function renderAuth() {
  return render(
    <AuthProvider>
      <Probe />
    </AuthProvider>,
  );
}

describe('AuthProvider', () => {
  beforeEach(() => {
    localStorage.clear();
  });

  afterEach(() => {
    vi.unstubAllGlobals();
    localStorage.clear();
  });

  it('restores the user from /auth/me when a token is present', async () => {
    localStorage.setItem('rmtc_token', 'abc');
    vi.stubGlobal(
      'fetch',
      vi.fn().mockResolvedValue({
        ok: true,
        status: 200,
        json: async () => ({
          data: { id: 3, name: 'Anna', email: 'anna@example.com', role: 'user' },
        }),
      }),
    );

    renderAuth();

    expect(await screen.findByText('anna@example.com')).toBeInTheDocument();
  });

  it('falls back to anonymous when the token is rejected', async () => {
    localStorage.setItem('rmtc_token', 'abc');
    vi.stubGlobal(
      'fetch',
      vi.fn().mockResolvedValue({
        ok: false,
        status: 401,
        json: async () => ({ message: 'Non autorizzato' }),
      }),
    );

    renderAuth();

    expect(await screen.findByText('anonimo')).toBeInTheDocument();
    expect(localStorage.getItem('rmtc_token')).toBeNull();
  });

  it('is anonymous when there is no token', async () => {
    renderAuth();

    expect(await screen.findByText('anonimo')).toBeInTheDocument();
  });
});
