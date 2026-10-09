/**
 * Intégration — écran de connexion (`app/(auth)/login.tsx`) avec la vraie session.
 *
 * Écran → `SessionProvider` → `auth.ts` → `client.ts` → `fetch` simulé. Garde :
 * l'identifiant marchand part rogné sur `signin`, sans Bearer ; un succès range le
 * jeton et ouvre la session ; un refus affiche le message du serveur (ou l'erreur
 * du champ en 422) sans rien ranger ; des champs vides ne partent pas.
 *
 * ⚠️ `@testing-library/react-native` 14 rend en **asynchrone** (`await render`).
 */
import { Text } from 'react-native';
import { fireEvent, render, screen } from '@testing-library/react-native';
import * as SecureStore from 'expo-secure-store';

import LoginScreen from '../../app/(auth)/login';
import { clearToken } from '../api/session';
import { t } from '../i18n';
import { SessionProvider, useSession } from '../session/SessionProvider';
import { champ } from '../testing/fields';
import { installFetch, jsonResponse, ok, singleCall } from '../testing/fetchMock';

jest.mock('../api/config', () => ({
  API_BASE_URL: 'https://example.test/api/v10',
  API_KEY: 'cle-api-test',
  REQUEST_TIMEOUT_MS: 20_000,
}));
jest.mock('expo-router', () => ({ Link: 'Text' }));
jest.mock('expo-secure-store', () => ({
  getItemAsync: jest.fn(),
  setItemAsync: jest.fn(),
  deleteItemAsync: jest.fn(),
}));
jest.mock('../push', () => ({ desabonnerAppareil: jest.fn(async () => undefined) }));

let fetchMock: jest.Mock;

beforeEach(async () => {
  jest.resetAllMocks();
  jest.mocked(SecureStore.getItemAsync).mockResolvedValue(null);
  jest.mocked(SecureStore.setItemAsync).mockResolvedValue(undefined);
  jest.mocked(SecureStore.deleteItemAsync).mockResolvedValue(undefined);
  await clearToken();
  fetchMock = installFetch();
});

function Compte() {
  const { loading, user } = useSession();
  if (loading) return <Text>session:chargement</Text>;
  return <Text>{user ? `session:${user.name}` : 'session:aucune'}</Text>;
}

async function ouvrir() {
  await render(
    <SessionProvider>
      <LoginScreen />
      <Compte />
    </SessionProvider>,
  );
  await screen.findByText('session:aucune');
}

async function saisir(identifiant: string, motDePasse: string) {
  await fireEvent.changeText(champ(t('auth.merchantId')), identifiant);
  await fireEvent.changeText(champ(t('auth.password')), motDePasse);
  await fireEvent.press(screen.getByRole('button', { name: t('auth.signIn') }));
}

it('se connecte : signin part sans Bearer, le jeton est rangé et la session ouverte', async () => {
  fetchMock.mockResolvedValueOnce(
    ok({ token: 'jeton-neuf', user: { id: 3, name: 'Boutique Awa', email: null, phone: null, user_type: 2 } }),
  );
  await ouvrir();

  await saisir(' 2024 ', 'motdepasse');

  expect(await screen.findByText('session:Boutique Awa')).toBeTruthy();
  const call = singleCall(fetchMock);
  expect(call.method).toBe('POST');
  expect(call.path).toBe('signin');
  expect(call.body).toEqual({ merchant_id: '2024', password: 'motdepasse' });
  expect(call.headers.apiKey).toBe('cle-api-test');
  expect(call.headers).not.toHaveProperty('Authorization');
  expect(SecureStore.setItemAsync).toHaveBeenCalledWith('beninlink.merchant.token', 'jeton-neuf');
});

it('affiche le refus du serveur et ne range aucun jeton', async () => {
  fetchMock.mockResolvedValueOnce(
    jsonResponse(401, { success: false, message: 'Identifiant ou mot de passe incorrect.', data: [] }),
  );
  await ouvrir();

  await saisir('2024', 'faux');

  expect(await screen.findByText('Identifiant ou mot de passe incorrect.')).toBeTruthy();
  expect(screen.getByText('session:aucune')).toBeTruthy();
  expect(SecureStore.setItemAsync).not.toHaveBeenCalled();
});

it('affiche sous le champ les erreurs de validation du serveur (422)', async () => {
  fetchMock.mockResolvedValueOnce(
    jsonResponse(422, {
      success: false,
      message: 'Données invalides.',
      data: { message: { merchant_id: ["L'identifiant marchand est introuvable."] } },
    }),
  );
  await ouvrir();

  await saisir('9999', 'motdepasse');

  expect(await screen.findByText("L'identifiant marchand est introuvable.")).toBeTruthy();
  expect(screen.getByText('Données invalides.')).toBeTruthy();
});

it("dit que le serveur est injoignable quand le réseau tombe", async () => {
  fetchMock.mockRejectedValueOnce(new TypeError('Network request failed'));
  await ouvrir();

  await saisir('2024', 'motdepasse');

  expect(await screen.findByText(t('errors.network'))).toBeTruthy();
});

it("n'envoie rien tant qu'un champ est vide", async () => {
  await ouvrir();

  await saisir('   ', 'motdepasse');

  expect(screen.getByText(t('errors.requiredField'))).toBeTruthy();
  expect(fetchMock).not.toHaveBeenCalled();
});
