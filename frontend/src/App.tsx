import { Anchor, AppShell, Badge, Button, Group, Text, Title } from '@mantine/core';
import { Link, Navigate, Route, Routes } from 'react-router';
import { useTranslation } from 'react-i18next';
import { useAuth } from './auth/context';
import { LanguageSwitcher } from './components/LanguageSwitcher';
import { RequireAuth } from './components/RequireAuth';
import ExamsPage from './pages/ExamsPage';
import LoginPage from './pages/LoginPage';
import MyExamsPage from './pages/MyExamsPage';
import MyVotesPage from './pages/MyVotesPage';
import RegisterPage from './pages/RegisterPage';

export default function App() {
  const { user, logout } = useAuth();
  const { t } = useTranslation();

  return (
    <AppShell header={{ height: 60 }} padding="md">
      <AppShell.Header>
        <Group h="100%" px="md" justify="space-between">
          <Anchor component={Link} to="/" underline="never">
            <Title order={4}>rmtc-exams</Title>
          </Anchor>
          <Group gap="xs">
            {user?.role !== 'admin' && (
              <Button component={Link} to="/" variant="subtle" size="xs">
                {t('nav.exams')}
              </Button>
            )}
            {user?.role === 'user' && (
              <Button component={Link} to="/my-exams" variant="subtle" size="xs">
                {t('nav.myExams')}
              </Button>
            )}
            {user?.role === 'supervisor' && (
              <Button component={Link} to="/supervisor/my-votes" variant="subtle" size="xs">
                {t('nav.votes')}
              </Button>
            )}
            <LanguageSwitcher />
            {user ? (
              <Group gap="sm">
                <Badge variant="light">{t(`nav.roles.${user.role}`)}</Badge>
                <Text size="sm">{user.name}</Text>
                <Button variant="outline" size="xs" onClick={logout}>
                  {t('nav.logout')}
                </Button>
              </Group>
            ) : (
              <Button component={Link} to="/login" size="xs">
                {t('nav.login')}
              </Button>
            )}
          </Group>
        </Group>
      </AppShell.Header>
      <AppShell.Main>
        <Routes>
          <Route path="/login" element={<LoginPage />} />
          <Route path="/register" element={<RegisterPage />} />
          <Route path="/" element={<ExamsPage />} />
          <Route element={<RequireAuth roles={['user']} />}>
            <Route path="/my-exams" element={<MyExamsPage />} />
          </Route>
          <Route element={<RequireAuth roles={['supervisor']} />}>
            <Route path="/supervisor/my-votes" element={<MyVotesPage />} />
          </Route>
          <Route path="*" element={<Navigate to="/" replace />} />
        </Routes>
      </AppShell.Main>
    </AppShell>
  );
}
