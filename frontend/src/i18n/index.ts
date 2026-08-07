import i18n from 'i18next';
import { initReactI18next } from 'react-i18next';
import en from './locales/en.json';
import it from './locales/it.json';

const STORAGE_KEY = 'rmtc_lang';
const SUPPORTED = ['it', 'en'];

function readSavedLanguage(): string | null {
  try {
    const saved = typeof localStorage !== 'undefined' ? localStorage.getItem(STORAGE_KEY) : null;

    return saved && SUPPORTED.includes(saved) ? saved : null;
  } catch {
    return null;
  }
}

i18n.use(initReactI18next).init({
  resources: {
    it: { translation: it },
    en: { translation: en },
  },
  lng: readSavedLanguage() ?? 'it',
  fallbackLng: 'it',
  interpolation: { escapeValue: false },
  initAsync: false,
});

export function changeLanguage(language: string): void {
  if (!SUPPORTED.includes(language)) {
    return;
  }

  i18n.changeLanguage(language);

  try {
    localStorage.setItem(STORAGE_KEY, language);
  } catch {
    // storage non disponibile: la lingua resta comunque attiva per la sessione
  }
}

export default i18n;
