import { MantineProvider } from '@mantine/core';
import { Notifications } from '@mantine/notifications';
import { render } from '@testing-library/react';
import { MemoryRouter } from 'react-router';
import type { ReactNode } from 'react';

export function renderWithProviders(ui: ReactNode, initialEntries: string[] = ['/']) {
  return render(
    <MantineProvider defaultColorScheme="light">
      <Notifications />
      <MemoryRouter initialEntries={initialEntries}>{ui}</MemoryRouter>
    </MantineProvider>,
  );
}
