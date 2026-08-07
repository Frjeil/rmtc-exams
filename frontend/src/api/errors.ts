import i18n from '../i18n';
import { ApiError } from './client';

/**
 * Restituisce un messaggio di errore localizzato.
 * - Se l'API ha restituito un `error` code traducibile, usa la traduzione.
 * - In ogni altro caso usa il fallback dell'azione chiamante (tradotto),
 *   senza mai mostrare messaggi non localizzati dal backend.
 */
export function getErrorMessage(error: unknown, fallback: string): string {
  if (error instanceof ApiError) {
    if (error.code && i18n.exists(`errors.${error.code}`)) {
      return i18n.t(`errors.${error.code}`);
    }
  }

  return fallback;
}
