/**
 * Intégration — changement de mot de passe, session ouverte (`app/(app)/profile/password.tsx`).
 *
 * Écran → `merchant.ts` → `client.ts` → `fetch` simulé. Garde : moins de 8
 * caractères (S134) ou une confirmation différente ne partent pas ; le PUT porte
 * le Bearer et les trois champs ; un ancien mot de passe faux affiche le message
 * du serveur, une erreur de champ s'affiche sous le champ ; un jeton révoqué
 * (401, S135/S144) est effacé de l'appareil.
 *
 * ⚠️ `@testing-library/react-native` 14 rend en **asynchrone** (`await render`).
 */
import { fireEvent, render, screen } from '@testing-library/react-native';
import * as SecureStore from 'expo-secure-store';

import ChangePasswordScreen from '../../app/(app)/profile/password';
import { clearToken, getToken, onTokenCleared } from '../api/session';
import { t } from '../i18n';
import { champ } from '../testing/fields';
import { installFetch, jsonResponse, ok, singleCall } from '../testing/fetchMock';

jest.mock('../api/config', () => ({
  API_BASE_URL: 'https://example.test/api/v10',
  API_KEY: 'cle-api-test',
  REQUEST_TIMEOUT_MS: 20_000,
}));
const mockBack = jest.fn();
jest.mock('expo-router', () => ({ useRouter: () => ({ back: mockBack }) }));
jest.mock('expo-secure-store', () => ({
  getItemAsync: jest.fn(),
  setItemAsync: jest.fn(),
  deleteItemAsync: jest.fn(),
}));

let fetchMock: jest.Mock;

beforeEach(async () => {
  jest.resetAllMocks();
  jest.mocked(SecureStore.deleteItemAsync).mockResolvedValue(undefined);
  await clearToken();
  jest.mocked(SecureStore.deleteItemAsync).mockClear();
  jest.mocked(SecureStore.getItemAsync).mockResolvedValue('jeton-marchand');
  fetchMock = installFetch();
});

async function changer(actuel: string, nouveau: string, confirmation = nouveau) {
  await render(<ChangePasswordScreen />);
  await fireEvent.changeText(champ(t('profile.currentPassword')), actuel);
  await fireEvent.changeText(champ(t('auth.newPassword')), nouveau);
  await fireEvent.changeText(champ(t('auth.confirmPassword')), confirmation);
  await fireEvent.press(screen.getByRole('button', { name: t('profile.changePassword') }));
}

it('refuse un nouveau mot de passe de 7 caractères sans appeler le serveur (S134)', async () => {
  await changer('ancien-mdp', 'abcdefg');
  expect(screen.getByText(t('errors.passwordTooShort').replace('{n}', '8'))).toBeTruthy();
  expect(fetchMock).not.toHaveBeenCalled();
});

it('refuse une confirmation différente sans appeler le serveur', async () => {
  await changer('ancien-mdp', 'abcdefgh', 'abcdefgX');
  expect(screen.getByText(t('errors.passwordMismatch'))).toBeTruthy();
  expect(fetchMock).not.toHaveBeenCalled();
});

it('change le mot de passe : PUT avec Bearer, succès affiché, champs vidés, retour possible', async () => {
  fetchMock.mockResolvedValueOnce(ok([], { message: 'Mot de passe mis à jour.' }));

  await changer('ancien-mdp', 'abcdefgh');

  expect(await screen.findByText(t('profile.passwordChanged'))).toBeTruthy();
  const call = singleCall(fetchMock);
  expect(call.method).toBe('PUT');
  expect(call.path).toBe('update-password');
  expect(call.headers.Authorization).toBe('Bearer jeton-marchand');
  expect(call.body).toEqual({ old_password: 'ancien-mdp', new_password: 'abcdefgh', confirm_password: 'abcdefgh' });
  expect(champ(t('auth.newPassword')).props.value).toBe('');

  await fireEvent.press(screen.getByRole('button', { name: t('common.back') }));
  expect(mockBack).toHaveBeenCalledTimes(1);
});

it("affiche le refus du serveur quand l'ancien mot de passe est faux", async () => {
  fetchMock.mockResolvedValueOnce(
    jsonResponse(422, { success: false, message: "L'ancien mot de passe ne correspond pas.", data: [] }),
  );

  await changer('faux', 'abcdefgh');

  expect(await screen.findByText("L'ancien mot de passe ne correspond pas.")).toBeTruthy();
  expect(screen.queryByText(t('profile.passwordChanged'))).toBeNull();
});

it('affiche sous le champ les erreurs de validation du serveur', async () => {
  fetchMock.mockResolvedValueOnce(
    jsonResponse(422, {
      success: false,
      message: 'Mot de passe non modifié.',
      data: { message: { new_password: ['Le mot de passe doit contenir une lettre et un chiffre.'] } },
    }),
  );

  await changer('ancien-mdp', 'abcdefgh');

  expect(await screen.findByText('Le mot de passe doit contenir une lettre et un chiffre.')).toBeTruthy();
});

it('efface le jeton révoqué (401) et prévient la session (S144)', async () => {
  const ecouteur = jest.fn();
  const retirer = onTokenCleared(ecouteur);
  fetchMock.mockResolvedValueOnce(jsonResponse(401, { message: 'Non authentifié.' }));

  await changer('ancien-mdp', 'abcdefgh');

  expect(await screen.findByText('Non authentifié.')).toBeTruthy();
  retirer();
  expect(ecouteur).toHaveBeenCalledTimes(1);
  expect(SecureStore.deleteItemAsync).toHaveBeenCalledWith('beninlink.merchant.token');
  jest.mocked(SecureStore.getItemAsync).mockResolvedValue(null);
  await expect(getToken()).resolves.toBeNull();
});
