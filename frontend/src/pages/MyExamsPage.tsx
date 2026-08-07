import { useEffect, useState } from 'react';
import { Badge, Center, Loader, Paper, Stack, Table, Title } from '@mantine/core';
import { notifications } from '@mantine/notifications';
import { useTranslation } from 'react-i18next';
import { api } from '../api/client';
import { getErrorMessage } from '../api/errors';
import type { Exam } from '../types';

export default function MyExamsPage() {
  const [exams, setExams] = useState<Exam[] | null>(null);
  const { t } = useTranslation();

  useEffect(() => {
    api
      .get<{ data: Exam[] }>('/my/exams')
      .then((res) => setExams(res.data))
      .catch((e) =>
        notifications.show({
          message: getErrorMessage(e, t('exams.loadError')),
          color: 'red',
        }),
      );
  }, [t]);

  if (!exams) {
    return (
      <Center mt="xl">
        <Loader />
      </Center>
    );
  }

  return (
    <Stack gap="lg">
      <Title order={2}>{t('myExams.title')}</Title>

      <Paper withBorder p="md">
        <Table>
          <Table.Thead>
            <Table.Tr>
              <Table.Th>{t('myExams.exam')}</Table.Th>
              <Table.Th>{t('myExams.date')}</Table.Th>
              <Table.Th>{t('myExams.vote')}</Table.Th>
            </Table.Tr>
          </Table.Thead>
          <Table.Tbody>
            {exams.length === 0 && (
              <Table.Tr>
                <Table.Td colSpan={3}>{t('myExams.empty')}</Table.Td>
              </Table.Tr>
            )}
            {exams.map((exam) => (
              <Table.Tr key={exam.id}>
                <Table.Td>{exam.title}</Table.Td>
                <Table.Td>{exam.date}</Table.Td>
                <Table.Td>
                  {exam.vote == null ? (
                    <Badge color="gray">{t('myExams.pending')}</Badge>
                  ) : (
                    <Badge color="green">{exam.vote}</Badge>
                  )}
                </Table.Td>
              </Table.Tr>
            ))}
          </Table.Tbody>
        </Table>
      </Paper>
    </Stack>
  );
}
