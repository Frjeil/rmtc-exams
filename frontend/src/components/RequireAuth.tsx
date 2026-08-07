import { Center, Loader } from '@mantine/core';
import { Navigate, Outlet, useLocation } from 'react-router';
import { useAuth } from '../auth/context';
import type { Role } from '../types';

export function RequireAuth({ roles }: { roles?: Role[] }) {
  const { user, ready } = useAuth();
  const location = useLocation();

  if (!ready) {
    return (
      <Center mt="xl">
        <Loader />
      </Center>
    );
  }

  if (!user) {
    return <Navigate to="/login" state={{ from: location }} replace />;
  }

  if (roles && !roles.includes(user.role)) {
    return <Navigate to="/" replace />;
  }

  return <Outlet />;
}
