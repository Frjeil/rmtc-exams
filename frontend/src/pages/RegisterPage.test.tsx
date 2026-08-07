import { beforeEach, describe, expect, it, vi } from 'vitest';
import { screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import RegisterPage from './RegisterPage';
import { renderWithProviders } from '../test/render';

const registerMock = vi.fn();
const useAuthMock = vi.hoisted(() => ({ user: null, ready: true }));

vi.mock('../auth/context', () => ({
  useAuth: () => ({
    user: useAuthMock.user,
    ready: useAuthMock.ready,
    login: vi.fn(),
    register: registerMock,
    logout: vi.fn(),
  }),
}));

describe('RegisterPage', () => {
  beforeEach(() => {
    useAuthMock.user = null;
    registerMock.mockReset();
  });

  it('renders the registration form', () => {
    renderWithProviders(<RegisterPage />);

    expect(screen.getByLabelText(/nome/i)).toBeInTheDocument();
    expect(screen.getByLabelText(/email/i)).toBeInTheDocument();
    expect(screen.getByRole('button', { name: /registrati/i })).toBeInTheDocument();
  });

  it('shows validation errors on empty submit', async () => {
    const user = userEvent.setup();
    renderWithProviders(<RegisterPage />);

    await user.click(screen.getByRole('button', { name: /registrati/i }));

    expect(await screen.findByText('Nome obbligatorio')).toBeInTheDocument();
    expect(screen.getByText('Email non valida')).toBeInTheDocument();
    expect(screen.getByText('Minimo 8 caratteri')).toBeInTheDocument();
  });

  it('calls register with the data on valid submit', async () => {
    const user = userEvent.setup();
    renderWithProviders(<RegisterPage />);

    await user.type(screen.getByLabelText(/nome/i), 'Mario');
    await user.type(screen.getByLabelText(/email/i), 'mario@example.com');
    await user.type(screen.getByLabelText('Password'), 'password');
    await user.type(screen.getByLabelText(/conferma password/i), 'password');
    await user.click(screen.getByRole('button', { name: /registrati/i }));

    await waitFor(() =>
      expect(registerMock).toHaveBeenCalledWith('Mario', 'mario@example.com', 'password'),
    );
  });
});
