import { beforeEach, describe, expect, it, vi } from 'vitest';
import { screen } from '@testing-library/react';
import MyExamsPage from './MyExamsPage';
import { renderWithProviders } from '../test/render';
import { api } from '../api/client';
import type { User } from '../types';

vi.mock('../api/client', () => ({
  api: { get: vi.fn(), post: vi.fn() },
  ApiError: class extends Error {
    status: number;

    constructor(status: number, message: string) {
      super(message);
      this.status = status;
    }
  },
}));

const useAuthMock = vi.hoisted(() => ({ user: null as User | null }));

vi.mock('../auth/context', () => ({
  useAuth: () => ({
    user: useAuthMock.user,
    ready: true,
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

describe('MyExamsPage', () => {
  beforeEach(() => {
    useAuthMock.user = studentUser;
    vi.mocked(api.get).mockReset();
  });

  it('renders the exams with their votes', async () => {
    vi.mocked(api.get).mockResolvedValue({
      data: [{ id: 1, title: 'Analisi Matematica', date: '2026-09-10', vote: 27 }],
    });
    renderWithProviders(<MyExamsPage />);

    expect(await screen.findByText('Analisi Matematica')).toBeInTheDocument();
    expect(screen.getByText('27')).toBeInTheDocument();
  });

  it('shows a placeholder when there are no exams', async () => {
    vi.mocked(api.get).mockResolvedValue({ data: [] });
    renderWithProviders(<MyExamsPage />);

    expect(await screen.findByText('Nessun esame.')).toBeInTheDocument();
  });
});
