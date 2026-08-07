import { useCallback, useEffect, useState, type FormEvent } from 'react';
import {
  Button,
  Card,
  Center,
  Group,
  Loader,
  Paper,
  Select,
  SimpleGrid,
  Stack,
  Text,
  TextInput,
  Title,
} from '@mantine/core';
import { notifications } from '@mantine/notifications';
import { useTranslation } from 'react-i18next';
import { api, ApiError } from '../api/client';
import { getErrorMessage } from '../api/errors';
import { useAuth } from '../auth/context';
import { NewExamModal } from '../components/NewExamModal';
import i18n from '../i18n';
import type { Exam } from '../types';

export default function ExamsPage() {
  const [exams, setExams] = useState<Exam[]>([]);
  const [loading, setLoading] = useState(true);
  const [title, setTitle] = useState('');
  const [date, setDate] = useState('');
  const [sort, setSort] = useState<string | null>('asc');
  const [enrolledIds, setEnrolledIds] = useState<Set<number>>(new Set());
  const [enrolledLoading, setEnrolledLoading] = useState(false);
  const [newExamOpen, setNewExamOpen] = useState(false);
  const { user } = useAuth();
  const { t } = useTranslation();

  const loadExams = useCallback((params: URLSearchParams) => {
    setLoading(true);
    api
      .get<{ data: Exam[] }>(`/exams?${params.toString()}`)
      .then((res) => setExams(res.data))
      .catch((e) =>
        notifications.show({
          message: getErrorMessage(e, i18n.t('exams.loadError')),
          color: 'red',
        }),
      )
      .finally(() => setLoading(false));
  }, []);

  useEffect(() => {
    loadExams(new URLSearchParams());
  }, [loadExams]);

  useEffect(() => {
    if (!user || user.role !== 'user') {
      setEnrolledIds(new Set());
      return;
    }

    setEnrolledLoading(true);
    api
      .get<{ data: Exam[] }>('/my/exams')
      .then((res) => setEnrolledIds(new Set(res.data.map((e) => e.id))))
      .catch((e) =>
        notifications.show({
          message: getErrorMessage(e, i18n.t('exams.enrolledError')),
          color: 'red',
        }),
      )
      .finally(() => setEnrolledLoading(false));
  }, [user]);

  function buildParams() {
    const params = new URLSearchParams();
    if (title) params.set('title', title);
    if (date) params.set('date', date);
    if (sort) params.set('sort', sort);
    return params;
  }

  function applyFilters(e: FormEvent) {
    e.preventDefault();
    loadExams(buildParams());
  }

  async function enroll(exam: Exam) {
    try {
      await api.post(`/exams/${exam.id}/enroll`);
      setEnrolledIds((prev) => new Set(prev).add(exam.id));
      notifications.show({
        message: t('exams.enrollSuccess', { title: exam.title }),
        color: 'green',
      });
    } catch (err) {
      if (err instanceof ApiError && err.status === 409) {
        notifications.show({
          message: t('exams.alreadyEnrolled', { title: exam.title }),
          color: 'blue',
        });
        return;
      }
      notifications.show({
        message: getErrorMessage(err, i18n.t('exams.enrollError')),
        color: 'red',
      });
    }
  }

  return (
    <Stack gap="lg">
      <Title order={2}>{t('exams.title')}</Title>

      <Paper withBorder p="md">
        <form onSubmit={applyFilters}>
          <Group align="flex-end">
            <TextInput
              label={t('exams.titleFilter')}
              placeholder={t('exams.titlePlaceholder')}
              value={title}
              onChange={(e) => setTitle(e.currentTarget.value)}
            />
            <TextInput label={t('exams.date')} type="date" value={date} onChange={(e) => setDate(e.currentTarget.value)} />
            <Select label={t('exams.sort')} data={['asc', 'desc']} value={sort} onChange={setSort} w={110} />
            <Button type="submit">{t('exams.filter')}</Button>
          </Group>
        </form>
      </Paper>

      {loading ? (
        <Center mt="xl">
          <Loader />
        </Center>
      ) : (
        <SimpleGrid cols={{ base: 1, sm: 2, lg: 3 }}>
          {user?.role === 'admin' && (
            <Card
              withBorder
              component="button"
              type="button"
              h="140px"
              className="exam-action-card"
              style={{
                borderStyle: 'dashed',
                cursor: 'pointer',
                display: 'flex',
                alignItems: 'center',
                justifyContent: 'center',
              }}
              onClick={() => setNewExamOpen(true)}
            >
              <Stack gap={2} align="center">
                <Text fw={700} fz={26} c="dimmed">
                  +
                </Text>
                <Text size="sm" c="dimmed">
                  {t('exams.newExam')}
                </Text>
              </Stack>
            </Card>
          )}
          {exams.map((exam) => (
            <Card key={exam.id} withBorder h="140px">
              <Stack gap="xs">
                <Text fw={600}>{exam.title}</Text>
                <Text size="sm" c="dimmed">
                  {exam.date}
                </Text>
                {user && user.role === 'user' && (
                  <Button
                    variant="light"
                    size="xs"
                    loading={enrolledLoading}
                    disabled={enrolledIds.has(exam.id)}
                    onClick={() => enroll(exam)}
                  >
                    {enrolledIds.has(exam.id) ? t('exams.enrolled') : t('exams.enroll')}
                  </Button>
                )}
              </Stack>
            </Card>
          ))}
        </SimpleGrid>
      )}

      <NewExamModal
        opened={newExamOpen}
        onClose={() => setNewExamOpen(false)}
        onCreated={() => loadExams(buildParams())}
      />
    </Stack>
  );
}
