import { describe, expect, it, vi } from 'vitest';
import { screen } from '@testing-library/react';
import { AssignVoteModal } from './AssignVoteModal';
import { renderWithProviders } from '../test/render';
import { api } from '../api/client';

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

describe('AssignVoteModal', () => {
  it('renders on open and loads the exams list once', async () => {
    vi.mocked(api.get).mockImplementation(async (url: string) => {
      if (url.startsWith('/exams/1/users')) {
        return { data: [{ id: 3, name: 'Anna', email: 'anna@example.com' }] };
      }
      return { data: [{ id: 1, title: 'Analisi', date: '2026-09-10' }] };
    });

    renderWithProviders(<AssignVoteModal opened onClose={() => {}} />);

    expect(await screen.findByText('Assegna voto')).toBeInTheDocument();
    expect(await screen.findByText('Esame')).toBeInTheDocument();
    expect(api.get).toHaveBeenCalledTimes(1);
    expect(api.get).toHaveBeenCalledWith('/exams');
  });
});
