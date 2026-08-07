import { useEffect, useRef } from 'react';
import { Button, Group, Modal, Stack, TextInput } from '@mantine/core';
import { useForm } from '@mantine/form';
import { notifications } from '@mantine/notifications';
import { useTranslation } from 'react-i18next';
import { api } from '../api/client';
import { getErrorMessage } from '../api/errors';
import { modalTransition } from './modalTransition';
import type { Exam } from '../types';

interface NewExamModalProps {
  opened: boolean;
  onClose: () => void;
  onCreated: () => void;
}

export function NewExamModal({ opened, onClose, onCreated }: NewExamModalProps) {
  const wasOpened = useRef(false);
  const { t } = useTranslation();

  const form = useForm({
    initialValues: { title: '', date: '' },
    validate: {
      title: (value) => (value.trim() ? null : t('newExamModal.titleRequired')),
      date: (value) => (/^\d{4}-\d{2}-\d{2}$/.test(value) ? null : t('newExamModal.dateInvalid')),
    },
  });

  useEffect(() => {
    const prev = wasOpened.current;
    wasOpened.current = opened;

    if (opened && !prev) {
      form.reset();
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [opened]);

  async function handleSubmit(values: { title: string; date: string }) {
    try {
      const res = await api.post<{ data: Exam }>('/admin/exams', values);
      notifications.show({
        message: t('newExamModal.created', { title: res.data.title }),
        color: 'green',
      });
      form.reset();
      onCreated();
      onClose();
    } catch (e) {
      notifications.show({
        message: getErrorMessage(e, t('newExamModal.error')),
        color: 'red',
      });
    }
  }

  return (
    <Modal
      opened={opened}
      onClose={onClose}
      title={t('newExamModal.title')}
      centered
      transitionProps={{
        transition: modalTransition,
        duration: 200,
        timingFunction: 'ease-out',
      }}
    >
      <form onSubmit={form.onSubmit(handleSubmit)}>
        <Stack>
          <TextInput
            label={t('newExamModal.titleField')}
            placeholder={t('newExamModal.titlePlaceholder')}
            {...form.getInputProps('title')}
          />
          <TextInput label={t('newExamModal.date')} type="date" {...form.getInputProps('date')} />
          <Group justify="flex-end" mt="md">
            <Button variant="subtle" onClick={onClose}>
              {t('newExamModal.cancel')}
            </Button>
            <Button type="submit">{t('newExamModal.submit')}</Button>
          </Group>
        </Stack>
      </form>
    </Modal>
  );
}
