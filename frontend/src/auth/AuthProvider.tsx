import { useEffect, useState, type ReactNode } from 'react';
import { api, getToken, setToken } from '../api/client';
import type { LoginResponse, User } from '../types';
import { AuthContext } from './context';

export function AuthProvider({ children }: { children: ReactNode }) {
  const [user, setUser] = useState<User | null>(null);
  const [ready, setReady] = useState(false);

  useEffect(() => {
    if (!getToken()) {
      setReady(true);
      return;
    }

    api
      .get<{ data: User }>('/auth/me')
      .then((res) => setUser(res.data))
      .catch(() => setToken(null))
      .finally(() => setReady(true));
  }, []);

  useEffect(() => {
    function onUnauthorized() {
      setUser(null);
      setReady(true);
    }
    window.addEventListener('auth:unauthorized', onUnauthorized);
    return () => window.removeEventListener('auth:unauthorized', onUnauthorized);
  }, []);

  async function login(email: string, password: string): Promise<void> {
    const res = await api.post<LoginResponse>('/auth/login', { email, password });
    setToken(res.token);
    setUser(res.user);
  }

  async function register(name: string, email: string, password: string): Promise<void> {
    const res = await api.post<LoginResponse>('/auth/register', {
      name,
      email,
      password,
      password_confirmation: password,
    });
    setToken(res.token);
    setUser(res.user);
  }

  async function logout(): Promise<void> {
    try {
      await api.post('/auth/logout');
    } catch {
      // il token viene comunque revocato localmente
    }
    setToken(null);
    setUser(null);
  }

  return (
    <AuthContext.Provider value={{ user, ready, login, register, logout }}>
      {children}
    </AuthContext.Provider>
  );
}
