import { beforeEach, describe, expect, it, vi } from 'vitest';
import { screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import ExamsPage from './ExamsPage';
import { renderWithProviders } from '../test/render';
import { api } from '../api/client';
import type { Exam, User } from '../types';

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

const exams: Exam[] = [
  { id: 1, title: 'Analisi Matematica', date: '2026-09-10' },
  { id: 2, title: 'Geometria', date: '2026-10-01' },
];

const studentUser: User = {
  id: 3,
  name: 'Studente',
  email: 'student@example.com',
  role: 'user',
};

function mockApi() {
  vi.mocked(api.get).mockImplementation(async (url: string) => {
    if (url.startsWith('/my/exams')) {
      return { data: [exams[0]] };
    }
    return { data: exams };
  });
}

describe('ExamsPage', () => {
  beforeEach(() => {
    useAuthMock.user = studentUser;
    vi.mocked(api.get).mockReset();
    vi.mocked(api.post).mockReset();
  });

  it('renders the list of exams', async () => {
    mockApi();
    renderWithProviders(<ExamsPage />);

    expect(await screen.findByText('Analisi Matematica')).toBeInTheDocument();
    expect(screen.getByText('Geometria')).toBeInTheDocument();
  });

  it('shows an already-enrolled exam as Iscritto (disabled)', async () => {
    mockApi();
    renderWithProviders(<ExamsPage />);

    expect(await screen.findByRole('button', { name: 'Iscritto' })).toBeDisabled();
    expect(screen.getByRole('button', { name: 'Iscriviti' })).toBeEnabled();
  });

  it('calls enroll on click for a not-enrolled exam', async () => {
    mockApi();
    vi.mocked(api.post).mockResolvedValue({ message: 'ok' });
    const user = userEvent.setup();
    renderWithProviders(<ExamsPage />);

    await screen.findByRole('button', { name: 'Iscriviti' });
    await user.click(screen.getByRole('button', { name: 'Iscriviti' }));

    await waitFor(() => expect(api.post).toHaveBeenCalledWith('/exams/2/enroll'));
  });

  it('filters by title on submit', async () => {
    mockApi();
    const user = userEvent.setup();
    renderWithProviders(<ExamsPage />);

    await screen.findByText('Analisi Matematica');
    await user.type(screen.getByLabelText(/titolo/i), 'Analisi');
    await user.click(screen.getByRole('button', { name: 'Filtra' }));

    await waitFor(() =>
      expect(api.get).toHaveBeenCalledWith(expect.stringContaining('title=Analisi')),
    );
  });
});
