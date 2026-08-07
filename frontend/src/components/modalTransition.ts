import type { MantineTransition } from '@mantine/core';

export const modalTransition: MantineTransition = {
  common: { willChange: 'transform, opacity' },
  in: { opacity: 1, transform: 'translateY(0)' },
  out: { opacity: 0, transform: 'translateY(12px)' },
  transitionProperty: 'transform, opacity',
};
