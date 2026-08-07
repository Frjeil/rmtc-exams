import { Anchor, Button, Center, Loader, Paper, PasswordInput, Stack, Text, TextInput, Title } from '@mantine/core';
import { useForm } from '@mantine/form';
import { notifications } from '@mantine/notifications';
import { useTranslation } from 'react-i18next';
import { Link, Navigate, useLocation, useNavigate } from 'react-router';
import { getErrorMessage } from '../api/errors';
import { useAuth } from '../auth/context';

export default function LoginPage() {
  const { user, ready, login } = useAuth();
  const { t } = useTranslation();
  const navigate = useNavigate();
  const location = useLocation();
  const from = (location.state as { from?: { pathname?: string } } | null)?.from?.pathname ?? '/';

  const form = useForm({
    initialValues: { email: '', password: '' },
    validate: {
      email: (value) => (/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value) ? null : t('login.errors.email')),
      password: (value) => (value ? null : t('login.errors.password')),
    },
  });

  if (!ready) {
    return (
      <Center mt="xl">
        <Loader />
      </Center>
    );
  }

  if (user) {
    return <Navigate to="/" replace />;
  }

  async function handleSubmit(values: { email: string; password: string }) {
    try {
      await login(values.email, values.password);
      notifications.show({ message: t('login.success'), color: 'green' });
      navigate(from, { replace: true });
    } catch (e) {
      notifications.show({
        message: getErrorMessage(e, t('login.error')),
        color: 'red',
      });
    }
  }

  return (
    <Paper withBorder p="lg" maw={420} mx="auto" mt="xl" shadow="sm">
      <Title order={3} mb="lg">
        {t('login.title')}
      </Title>
      <form onSubmit={form.onSubmit(handleSubmit)}>
        <Stack>
          <TextInput label={t('login.email')} placeholder={t('login.emailPlaceholder')} {...form.getInputProps('email')} />
          <PasswordInput label={t('login.password')} {...form.getInputProps('password')} />
          <Button type="submit" fullWidth>
            {t('login.submit')}
          </Button>
        </Stack>
      </form>
      <Text size="sm" ta="center" mt="md">
        {t('login.noAccount')}{' '}
        <Anchor component={Link} to="/register">
          {t('login.registerLink')}
        </Anchor>
      </Text>
    </Paper>
  );
}
