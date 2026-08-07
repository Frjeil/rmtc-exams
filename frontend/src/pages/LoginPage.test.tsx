import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import LoginPage from './LoginPage';
import { renderWithProviders } from '../test/render';
import { changeLanguage } from '../i18n';

const loginMock = vi.fn();
const useAuthMock = vi.hoisted(() => ({ user: null, ready: true }));

vi.mock('../auth/context', () => ({
  useAuth: () => ({
    user: useAuthMock.user,
    ready: useAuthMock.ready,
    login: loginMock,
    register: vi.fn(),
    logout: vi.fn(),
  }),
}));

describe('LoginPage', () => {
  beforeEach(() => {
    useAuthMock.user = null;
    loginMock.mockReset();
  });

  afterEach(() => {
    changeLanguage('it');
  });

  it('renders the login form', () => {
    renderWithProviders(<LoginPage />);

    expect(screen.getByLabelText(/email/i)).toBeInTheDocument();
    expect(screen.getByLabelText('Password')).toBeInTheDocument();
    expect(screen.getByRole('button', { name: /login/i })).toBeInTheDocument();
  });

  it('shows validation errors on empty submit', async () => {
    const user = userEvent.setup();
    renderWithProviders(<LoginPage />);

    await user.click(screen.getByRole('button', { name: /login/i }));

    expect(await screen.findByText('Email non valida')).toBeInTheDocument();
    expect(screen.getByText('Password obbligatoria')).toBeInTheDocument();
  });

  it('calls login with the credentials on valid submit', async () => {
    const user = userEvent.setup();
    renderWithProviders(<LoginPage />);

    await user.type(screen.getByLabelText(/email/i), 'admin@example.com');
    await user.type(screen.getByLabelText('Password'), 'password');
    await user.click(screen.getByRole('button', { name: /login/i }));

    await waitFor(() =>
      expect(loginMock).toHaveBeenCalledWith('admin@example.com', 'password'),
    );
  });

  it('updates the labels when the language changes', async () => {
    renderWithProviders(<LoginPage />);

    expect(screen.getByRole('heading', { name: 'Accedi' })).toBeInTheDocument();

    changeLanguage('en');
    expect(await screen.findByRole('heading', { name: 'Sign in' })).toBeInTheDocument();
  });
});
