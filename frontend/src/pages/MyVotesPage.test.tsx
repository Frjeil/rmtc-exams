import { beforeEach, describe, expect, it, vi } from 'vitest';
import { screen } from '@testing-library/react';
import MyVotesPage from './MyVotesPage';
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

const supervisorUser: User = {
  id: 2,
  name: 'Supervisor',
  email: 'supervisor@example.com',
  role: 'supervisor',
};

const votes = [
  {
    exam_id: 5,
    exam_title: 'Analisi Matematica',
    exam_date: '2026-09-10',
    student_name: 'Anna',
    student_email: 'anna@example.com',
    vote: 28,
    graded_at: '2026-08-05T10:00:00Z',
  },
];

describe('MyVotesPage', () => {
  beforeEach(() => {
    useAuthMock.user = supervisorUser;
    vi.mocked(api.get).mockReset();
  });

  it('renders the assigned votes', async () => {
    vi.mocked(api.get).mockResolvedValue({ data: votes });
    renderWithProviders(<MyVotesPage />);

    expect(await screen.findByText('Analisi Matematica')).toBeInTheDocument();
    expect(screen.getByText('Anna')).toBeInTheDocument();
    expect(screen.getByText('28')).toBeInTheDocument();
  });

  it('renders the Assegna voto action card', async () => {
    vi.mocked(api.get).mockResolvedValue({ data: votes });
    renderWithProviders(<MyVotesPage />);

    expect(await screen.findByRole('button', { name: /assegna voto/i })).toBeInTheDocument();
  });
});
