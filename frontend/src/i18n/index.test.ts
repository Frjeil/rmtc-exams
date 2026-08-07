import { afterEach, describe, expect, it } from 'vitest';
import i18n, { changeLanguage } from './index';

describe('i18n', () => {
  afterEach(() => {
    changeLanguage('it');
    localStorage.clear();
  });

  it('defaults to Italian', () => {
    expect(i18n.language).toBe('it');
    expect(i18n.t('nav.exams')).toBe('Esami');
    expect(i18n.t('nav.roles.admin')).toBe('Admin');
  });

  it('switches to English and persists the choice', () => {
    changeLanguage('en');

    expect(i18n.language).toBe('en');
    expect(i18n.t('nav.exams')).toBe('Exams');
    expect(i18n.t('nav.roles.admin')).toBe('Admin');
    expect(localStorage.getItem('rmtc_lang')).toBe('en');
  });
});
