import { Anchor, Button, Center, Loader, Paper, PasswordInput, Stack, Text, TextInput, Title } from '@mantine/core';
import { useForm } from '@mantine/form';
import { notifications } from '@mantine/notifications';
import { useTranslation } from 'react-i18next';
import { Link, Navigate, useNavigate } from 'react-router';
import { getErrorMessage } from '../api/errors';
import { useAuth } from '../auth/context';

export default function RegisterPage() {
  const { user, ready, register } = useAuth();
  const { t } = useTranslation();
  const navigate = useNavigate();

  const form = useForm({
    initialValues: { name: '', email: '', password: '', password_confirmation: '' },
    validate: {
      name: (value) => (value.trim() ? null : t('register.nameRequired')),
      email: (value) => (/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value) ? null : t('login.errors.email')),
      password: (value) => (value.length >= 8 ? null : t('register.minPassword')),
      password_confirmation: (value, values) =>
        value === values.password ? null : t('register.passwordMismatch'),
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

  async function handleSubmit(values: typeof form.values) {
    try {
      await register(values.name, values.email, values.password);
      notifications.show({ message: t('register.success'), color: 'green' });
      navigate('/', { replace: true });
    } catch (e) {
      notifications.show({
        message: getErrorMessage(e, t('register.error')),
        color: 'red',
      });
    }
  }

  return (
    <Paper withBorder p="lg" maw={420} mx="auto" mt="xl" shadow="sm">
      <Title order={3} mb="lg">
        {t('register.title')}
      </Title>
      <form onSubmit={form.onSubmit(handleSubmit)}>
        <Stack>
          <TextInput label={t('register.name')} placeholder={t('register.namePlaceholder')} {...form.getInputProps('name')} />
          <TextInput label={t('register.email')} placeholder={t('login.emailPlaceholder')} {...form.getInputProps('email')} />
          <PasswordInput label={t('register.password')} {...form.getInputProps('password')} />
          <PasswordInput label={t('register.confirm')} {...form.getInputProps('password_confirmation')} />
          <Button type="submit" fullWidth>
            {t('register.submit')}
          </Button>
        </Stack>
      </form>
      <Text size="sm" ta="center" mt="md">
        {t('register.hasAccount')}{' '}
        <Anchor component={Link} to="/login">
          {t('register.loginLink')}
        </Anchor>
      </Text>
    </Paper>
  );
}
