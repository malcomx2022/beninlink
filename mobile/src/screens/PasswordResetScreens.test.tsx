/**
 * Intégration — mot de passe oublié (`app/(auth)/forgot-password.tsx`, `reset-password.tsx`).
 *
 * Écran → `auth.ts` → `client.ts` → `fetch` simulé. Garde : la demande de lien
 * part sur une route publique avec l'e-mail rogné et affiche le message du serveur
 * (ou le repli neutre de S142) ; la réinitialisation refuse un mot de passe de
 * moins de 8 caractères (S134) ou mal confirmé AVANT tout appel, reprend jeton et
 * e-mail du lien profond, et affiche succès ou refus du serveur.
 *
 * ⚠️ `@testing-library/react-native` 14 rend en **asynchrone** (`await render`).
 */
import { fireEvent, render, screen } from '@testing-library/react-native';
import * as SecureStore from 'expo-secure-store';

import ForgotPasswordScreen from '../../app/(auth)/forgot-password';
import ResetPasswordScreen from '../../app/(auth)/reset-password';
import { clearToken } from '../api/session';
import { t } from '../i18n';
import { champ } from '../testing/fields';
import { installFetch, jsonResponse, ok, singleCall } from '../testing/fetchMock';

jest.mock('../api/config', () => ({
  API_BASE_URL: 'https://example.test/api/v10',
  API_KEY: 'cle-api-test',
  REQUEST_TIMEOUT_MS: 20_000,
}));
let mockParams: { token?: string; email?: string } = {};
jest.mock('expo-router', () => ({ Link: 'Text', useLocalSearchParams: () => mockParams }));
jest.mock('expo-secure-store', () => ({
  getItemAsync: jest.fn(),
  setItemAsync: jest.fn(),
  deleteItemAsync: jest.fn(),
}));

let fetchMock: jest.Mock;

beforeEach(async () => {
  jest.resetAllMocks();
  jest.mocked(SecureStore.getItemAsync).mockResolvedValue(null);
  jest.mocked(SecureStore.deleteItemAsync).mockResolvedValue(undefined);
  await clearToken();
  mockParams = {};
  fetchMock = installFetch();
});

describe('mot de passe oublié', () => {
  async function demander(email: string) {
    await render(<ForgotPasswordScreen />);
    await fireEvent.changeText(champ(t('auth.email')), email);
    await fireEvent.press(screen.getByRole('button', { name: t('auth.sendResetLink') }));
  }

  it("envoie l'e-mail rogné sur la route publique et affiche le message du serveur", async () => {
    fetchMock.mockResolvedValueOnce(ok({ message: 'Nous vous avons envoyé le lien par e-mail.' }));

    await demander('  awa@boutique.bj ');

    expect(await screen.findByText('Nous vous avons envoyé le lien par e-mail.')).toBeTruthy();
    expect(screen.getByText(t('auth.resetNextStep'))).toBeTruthy();
    const call = singleCall(fetchMock);
    expect(call.path).toBe('password/email');
    expect(call.body).toEqual({ email: 'awa@boutique.bj' });
    expect(call.headers).not.toHaveProperty('Authorization');
  });

  it("retombe sur le message neutre quand le serveur n'en donne pas", async () => {
    fetchMock.mockResolvedValueOnce(ok([]));
    await demander('awa@boutique.bj');
    expect(await screen.findByText(t('auth.resetSent'))).toBeTruthy();
  });

  it('affiche le refus du serveur (cadence limitée)', async () => {
    fetchMock.mockResolvedValueOnce(jsonResponse(429, { message: 'Trop de tentatives. Réessayez dans une minute.' }));
    await demander('awa@boutique.bj');
    expect(await screen.findByText('Trop de tentatives. Réessayez dans une minute.')).toBeTruthy();
    expect(screen.queryByText(t('auth.resetNextStep'))).toBeNull();
  });

  it("n'envoie rien sans e-mail", async () => {
    await demander('   ');
    expect(screen.getByText(t('errors.requiredField'))).toBeTruthy();
    expect(fetchMock).not.toHaveBeenCalled();
  });
});

describe('nouveau mot de passe', () => {
  async function remplir(motDePasse: string, confirmation: string) {
    await fireEvent.changeText(champ(t('auth.newPassword')), motDePasse);
    await fireEvent.changeText(champ(t('auth.confirmPassword')), confirmation);
    await fireEvent.press(screen.getByRole('button', { name: t('auth.resetPassword') }));
  }

  it('reprend le jeton et l\'e-mail du lien profond', async () => {
    mockParams = { token: 'jeton-du-lien', email: 'awa@boutique.bj' };
    await render(<ResetPasswordScreen />);
    expect(champ(t('auth.resetToken')).props.value).toBe('jeton-du-lien');
    expect(champ(t('auth.email')).props.value).toBe('awa@boutique.bj');
  });

  it('refuse moins de 8 caractères avant tout appel (S134)', async () => {
    mockParams = { token: 'jeton-du-lien', email: 'awa@boutique.bj' };
    await render(<ResetPasswordScreen />);

    await remplir('1234567', '1234567');

    expect(screen.getByText(t('errors.passwordTooShort').replace('{n}', '8'))).toBeTruthy();
    expect(fetchMock).not.toHaveBeenCalled();
  });

  it('refuse une confirmation différente avant tout appel', async () => {
    mockParams = { token: 'jeton-du-lien', email: 'awa@boutique.bj' };
    await render(<ResetPasswordScreen />);

    await remplir('12345678', '12345679');

    expect(screen.getByText(t('errors.passwordMismatch'))).toBeTruthy();
    expect(fetchMock).not.toHaveBeenCalled();
  });

  it('exige jeton et e-mail', async () => {
    await render(<ResetPasswordScreen />);
    await remplir('12345678', '12345678');
    expect(screen.getByText(t('errors.requiredField'))).toBeTruthy();
    expect(fetchMock).not.toHaveBeenCalled();
  });

  it('accepte 8 caractères, envoie le tout et affiche le succès du serveur', async () => {
    mockParams = { token: ' jeton-du-lien ', email: ' awa@boutique.bj ' };
    fetchMock.mockResolvedValueOnce(ok({ message: 'Votre mot de passe a été réinitialisé.' }));
    await render(<ResetPasswordScreen />);

    await remplir('12345678', '12345678');

    expect(await screen.findByText('Votre mot de passe a été réinitialisé.')).toBeTruthy();
    expect(screen.getByText(t('auth.backToSignIn'))).toBeTruthy();
    const call = singleCall(fetchMock);
    expect(call.method).toBe('POST');
    expect(call.path).toBe('password/reset');
    expect(call.body).toEqual({
      token: 'jeton-du-lien',
      email: 'awa@boutique.bj',
      password: '12345678',
      password_confirmation: '12345678',
    });
    expect(call.headers).not.toHaveProperty('Authorization');
  });

  it('affiche le refus du serveur et l\'erreur sous le champ du jeton', async () => {
    mockParams = { token: 'perime', email: 'awa@boutique.bj' };
    fetchMock.mockResolvedValueOnce(
      jsonResponse(422, { message: 'Réinitialisation impossible.', errors: { token: ['Ce jeton est invalide.'] } }),
    );
    await render(<ResetPasswordScreen />);

    await remplir('12345678', '12345678');

    expect(await screen.findByText('Réinitialisation impossible.')).toBeTruthy();
    expect(screen.getByText('Ce jeton est invalide.')).toBeTruthy();
    expect(screen.queryByText(t('auth.backToSignIn'))).toBeNull();
  });
});
