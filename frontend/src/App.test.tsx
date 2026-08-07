import { beforeEach, describe, expect, it, vi } from 'vitest';
import { screen } from '@testing-library/react';
import App from './App';
import { renderWithProviders } from './test/render';
import { api } from './api/client';
import type { User } from './types';

vi.mock('./api/client', () => ({
  api: { get: vi.fn(), post: vi.fn() },
  ApiError: class extends Error {
    status: number;

    constructor(status: number, message: string) {
      super(message);
      this.status = status;
    }
  },
}));

const useAuthMock = vi.hoisted(() => ({ user: null as User | null, ready: true }));

vi.mock('./auth/context', () => ({
  useAuth: () => ({
    user: useAuthMock.user,
    ready: useAuthMock.ready,
    login: vi.fn(),
    register: vi.fn(),
    logout: vi.fn(),
  }),
}));

const studentUser: User = {
  id: 3,
  name: 'Studente',
  email: 'student@example.com',
  role: 'user',
};

describe('App routing', () => {
  beforeEach(() => {
    useAuthMock.user = null;
    useAuthMock.ready = true;
    vi.mocked(api.get).mockReset();
    vi.mocked(api.get).mockImplementation(async () => ({ data: [] }));
  });

  it('redirects an unauthenticated user from a protected route to /login', async () => {
    renderWithProviders(<App />, ['/my-exams']);

    expect(await screen.findByText('Accedi')).toBeInTheDocument();
  });

  it('redirects a student away from a supervisor route to the home page', async () => {
    useAuthMock.user = studentUser;
    renderWithProviders(<App />, ['/supervisor/my-votes']);

    expect(await screen.findByText('Esami disponibili')).toBeInTheDocument();
  });

  it('lets a student access /my-exams', async () => {
    useAuthMock.user = studentUser;
    renderWithProviders(<App />, ['/my-exams']);

    expect(await screen.findByText('Nessun esame.')).toBeInTheDocument();
  });
});
