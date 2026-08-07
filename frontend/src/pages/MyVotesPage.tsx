import { useCallback, useEffect, useState, type FormEvent } from 'react';
import {
  Badge,
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
import { api } from '../api/client';
import { getErrorMessage } from '../api/errors';
import { AssignVoteModal } from '../components/AssignVoteModal';
import i18n from '../i18n';
import type { MyVote } from '../types';

export default function MyVotesPage() {
  const [votes, setVotes] = useState<MyVote[] | null>(null);
  const [title, setTitle] = useState('');
  const [date, setDate] = useState('');
  const [sort, setSort] = useState<string | null>('asc');
  const [assignOpen, setAssignOpen] = useState(false);
  const { t } = useTranslation();

  const loadVotes = useCallback((params: URLSearchParams) => {
    api
      .get<{ data: MyVote[] }>(`/supervisor/my/votes?${params.toString()}`)
      .then((res) => setVotes(res.data))
      .catch((e) =>
        notifications.show({
          message: getErrorMessage(e, i18n.t('exams.loadError')),
          color: 'red',
        }),
      );
  }, []);

  useEffect(() => {
    loadVotes(new URLSearchParams());
  }, [loadVotes]);

  function buildParams() {
    const params = new URLSearchParams();
    if (title) params.set('title', title);
    if (date) params.set('date', date);
    if (sort) params.set('sort', sort);
    return params;
  }

  function applyFilters(e: FormEvent) {
    e.preventDefault();
    loadVotes(buildParams());
  }

  function formatDate(iso: string): string {
    return new Date(iso).toLocaleDateString(
      i18n.resolvedLanguage ?? 'it',
      {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
      },
    );
  }

  return (
    <Stack gap="lg">
      <Title order={2}>{t('votes.title')}</Title>

      <Paper withBorder p="md">
        <form onSubmit={applyFilters}>
          <Group align="flex-end">
            <TextInput
              label={t('votes.titleFilter')}
              placeholder={t('votes.titlePlaceholder')}
              value={title}
              onChange={(e) => setTitle(e.currentTarget.value)}
            />
            <TextInput label={t('votes.date')} type="date" value={date} onChange={(e) => setDate(e.currentTarget.value)} />
            <Select label={t('votes.sort')} data={['asc', 'desc']} value={sort} onChange={setSort} w={110} />
            <Button type="submit">{t('votes.filter')}</Button>
          </Group>
        </form>
      </Paper>

      {!votes ? (
        <Center mt="xl">
          <Loader />
        </Center>
      ) : (
        <SimpleGrid cols={{ base: 1, sm: 2, lg: 3 }}>
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
            onClick={() => setAssignOpen(true)}
          >
            <Stack gap={2} align="center">
              <Text fw={700} fz={26} c="dimmed">
                +
              </Text>
              <Text size="sm" c="dimmed">
                {t('votes.assign')}
              </Text>
            </Stack>
          </Card>
          {votes.map((vote) => (
            <Card key={`${vote.exam_id}-${vote.student_email}`} withBorder h="140px">
              <Stack gap="xs">
                <Text fw={600}>{vote.exam_title}</Text>
                <Text size="sm" c="dimmed">
                  {vote.exam_date}
                </Text>
                <Group gap="xs">
                  <Text size="sm">{vote.student_name}</Text>
                  <Badge color="green">{vote.vote}</Badge>
                </Group>
                <Text size="xs" c="dimmed">
                  {t('votes.gradedOn')} {formatDate(vote.graded_at)}
                </Text>
              </Stack>
            </Card>
          ))}
        </SimpleGrid>
      )}

      <AssignVoteModal
        opened={assignOpen}
        onClose={() => setAssignOpen(false)}
        onAssigned={() => loadVotes(buildParams())}
      />
    </Stack>
  );
}
