import { SegmentedControl } from '@mantine/core';
import { useTranslation } from 'react-i18next';
import { changeLanguage } from '../i18n';

export function LanguageSwitcher() {
  const { i18n, t } = useTranslation();
  const current = i18n.language === 'en' ? 'en' : 'it';

  return (
    <SegmentedControl
      value={current}
      onChange={(value) => changeLanguage(value)}
      data={[
        { value: 'it', label: 'IT' },
        { value: 'en', label: 'EN' },
      ]}
      size="xs"
      aria-label={t('nav.language')}
    />
  );
}
