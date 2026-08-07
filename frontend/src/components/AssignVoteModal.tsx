import { useCallback, useEffect, useRef, useState } from 'react';
import { Button, Group, Loader, Modal, NumberInput, Select, Stack } from '@mantine/core';
import { useForm } from '@mantine/form';
import { notifications } from '@mantine/notifications';
import { useTranslation } from 'react-i18next';
import { api } from '../api/client';
import { getErrorMessage } from '../api/errors';
import { modalTransition } from './modalTransition';
import type { EnrolledUser, Exam } from '../types';

interface AssignVoteModalProps {
  opened: boolean;
  onClose: () => void;
  onAssigned?: () => void;
}

export function AssignVoteModal({ opened, onClose, onAssigned }: AssignVoteModalProps) {
  const [exams, setExams] = useState<Exam[]>([]);
  const [users, setUsers] = useState<EnrolledUser[]>([]);
  const [usersLoading, setUsersLoading] = useState(false);
  const wasOpened = useRef(false);
  const { t } = useTranslation();

  const form = useForm({
    initialValues: { exam_id: '', user_id: '', vote: 18 },
    validate: {
      exam_id: (value) => (value ? null : t('assignVoteModal.examRequired')),
      user_id: (value) => (value ? null : t('assignVoteModal.studentRequired')),
      vote: (value) => (value >= 18 && value <= 30 ? null : t('assignVoteModal.voteRange')),
    },
  });

  useEffect(() => {
    const prev = wasOpened.current;
    wasOpened.current = opened;

    if (!opened || prev) {
      return;
    }

    form.reset();
    setUsers([]);
    api
      .get<{ data: Exam[] }>('/exams')
      .then((res) => setExams(res.data))
      .catch((e) =>
        notifications.show({
          message: getErrorMessage(e, t('assignVoteModal.examsError')),
          color: 'red',
        }),
      );
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [opened]);

  const loadUsers = useCallback(
    (examId: string) => {
      setUsersLoading(true);
      api
        .get<{ data: EnrolledUser[] }>(`/exams/${examId}/users`)
        .then((res) => setUsers(res.data))
        .catch((e) =>
          notifications.show({
            message: getErrorMessage(e, t('assignVoteModal.studentsError')),
            color: 'red',
          }),
        )
        .finally(() => setUsersLoading(false));
    },
    [t],
  );

  const handleExamChange = (value: string | null) => {
    form.setFieldValue('exam_id', value ?? '');
    form.setFieldValue('user_id', '');
    setUsers([]);
    if (value) {
      loadUsers(value);
    }
  };

  async function handleSubmit(values: typeof form.values) {
    try {
      const res = await api.post<{ message: string }>(`/supervisor/exams/${values.exam_id}/assign`, {
        user_id: Number(values.user_id),
        vote: values.vote,
      });
      notifications.show({ message: res.message, color: 'green' });
      onAssigned?.();
      onClose();
    } catch (e) {
      notifications.show({
        message: getErrorMessage(e, t('assignVoteModal.assignError')),
        color: 'red',
      });
    }
  }

  return (
    <Modal
      opened={opened}
      onClose={onClose}
      title={t('assignVoteModal.title')}
      centered
      transitionProps={{
        transition: modalTransition,
        duration: 200,
        timingFunction: 'ease-out',
      }}
    >
      <form onSubmit={form.onSubmit(handleSubmit)}>
        <Stack>
          <Select
            label={t('assignVoteModal.exam')}
            placeholder={t('assignVoteModal.examPlaceholder')}
            data={exams.map((e) => ({ value: String(e.id), label: `${e.title} (${e.date})` }))}
            searchable
            {...form.getInputProps('exam_id')}
            onChange={handleExamChange}
          />
          <Select
            label={t('assignVoteModal.student')}
            placeholder={usersLoading ? t('assignVoteModal.studentLoading') : t('assignVoteModal.studentPlaceholder')}
            data={users.map((u) => ({ value: String(u.id), label: `${u.name} (${u.email})` }))}
            searchable
            disabled={!form.values.exam_id || usersLoading}
            rightSection={usersLoading ? <Loader size="xs" /> : undefined}
            {...form.getInputProps('user_id')}
          />
          <NumberInput label={t('assignVoteModal.vote')} min={18} max={30} {...form.getInputProps('vote')} />
          <Group justify="flex-end" mt="md">
            <Button variant="subtle" onClick={onClose}>
              {t('assignVoteModal.cancel')}
            </Button>
            <Button type="submit">{t('assignVoteModal.submit')}</Button>
          </Group>
        </Stack>
      </form>
    </Modal>
  );
}
