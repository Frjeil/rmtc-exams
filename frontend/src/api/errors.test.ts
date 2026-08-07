import { afterEach, describe, expect, it } from 'vitest';
import { getErrorMessage } from './errors';
import { ApiError } from './client';
import { changeLanguage } from '../i18n';

describe('getErrorMessage', () => {
  afterEach(() => {
    changeLanguage('it');
  });

  it('returns the localized message for a known error code', () => {
    const err = new ApiError(422, 'messaggio backend', 'invalid_credentials');

    expect(getErrorMessage(err, 'fallback')).toBe('Le credenziali inserite non sono valide.');
  });

  it('uses the current language for error codes', () => {
    const err = new ApiError(422, 'messaggio backend', 'email_taken');

    changeLanguage('en');

    expect(getErrorMessage(err, 'fallback')).toBe('This email is already registered.');
  });

  it('uses the caller fallback when the API returns no error code', () => {
    const err = new ApiError(422, 'messaggio backend');

    expect(getErrorMessage(err, 'fallback')).toBe('fallback');
  });

  it('uses the caller fallback for an unknown error code', () => {
    const err = new ApiError(422, 'messaggio backend', 'unknown_code');

    expect(getErrorMessage(err, 'fallback')).toBe('fallback');
  });

  it('uses the caller fallback for non-API errors', () => {
    expect(getErrorMessage(new Error('x'), 'fallback')).toBe('fallback');
  });
});
