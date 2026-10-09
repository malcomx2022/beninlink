import { useState } from 'react';
import { Pressable, Text, View } from 'react-native';
import { act, fireEvent, render, screen, userEvent } from '@testing-library/react-native';
import * as SecureStore from 'expo-secure-store';

import { clearToken, getToken, setToken } from '../api/session';
import { SessionProvider, useSession } from './SessionProvider';

jest.mock('../api/config', () => ({ API_BASE_URL: 'https://example.test/api/v10', API_KEY: 'test', REQUEST_TIMEOUT_MS: 20_000 }));
jest.mock('expo-secure-store', () => ({ getItemAsync: jest.fn(), setItemAsync: jest.fn(), deleteItemAsync: jest.fn() }));
jest.mock('../push', () => ({ desabonnerAppareil: jest.fn() }));

let fetchMock: jest.Mock;
function response(status: number, body: unknown): Response {
  return { ok: status >= 200 && status < 300, status, text: async () => JSON.stringify(body) } as Response;
}
const profile = { id: 7, name: 'Compte pilote' };
const profileResponse = () => response(200, { success: true, data: { user: profile } });

beforeEach(async () => {
  jest.resetAllMocks();
  jest.mocked(SecureStore.getItemAsync).mockResolvedValue(null);
  jest.mocked(SecureStore.setItemAsync).mockResolvedValue(undefined);
  jest.mocked(SecureStore.deleteItemAsync).mockResolvedValue(undefined);
  await clearToken();
  await setToken('jeton-pilote');
  fetchMock = jest.fn().mockResolvedValueOnce(profileResponse());
  globalThis.fetch = fetchMock;
});

function Etat() {
  const { loading, user, refresh } = useSession();
  const [error, setError] = useState(false);
  return <View>
    <Text>{loading ? 'chargement' : user ? user.name : 'déconnecté'}</Text>
    {error && <Text>actualisation refusée</Text>}
    <Pressable accessibilityRole="button" onPress={async () => {
      setError(false);
      try { await refresh(); } catch { setError(true); }
    }}><Text>Actualiser</Text></Pressable>
  </View>;
}
async function open() {
  await render(<SessionProvider><Etat /></SessionProvider>);
  expect(await screen.findByText(profile.name)).toBeTruthy();
}

it('actualise les données du compte lorsque la requête réussit', async () => {
  await open();
  fetchMock.mockResolvedValueOnce(response(200, { data: { user: { ...profile, name: 'Compte actualisé' } } }));
  await fireEvent.press(screen.getByText('Actualiser'));
  expect(screen.getByText('Compte actualisé')).toBeTruthy();
  expect(await getToken()).toBe('jeton-pilote');
});

it('ignore aussi une restauration initiale terminée après suppression du jeton', async () => {
  let resolve!: (value: Response) => void;
  fetchMock.mockReset().mockImplementationOnce(() => new Promise<Response>((done) => { resolve = done; }));
  await render(<SessionProvider><Etat /></SessionProvider>);
  expect(screen.getByText('chargement')).toBeTruthy();
  await act(async () => { await clearToken(); });
  await act(async () => { resolve(profileResponse()); });
  expect(screen.getByText('déconnecté')).toBeTruthy();
});


it('restaure le compte depuis data.user avec le jeton de cet appareil', async () => {
  await open();
  expect(fetchMock.mock.calls[0][1].headers.Authorization).toBe('Bearer jeton-pilote');
});

it.each([0, 503])('conserve la session et propage un incident %s pendant l’actualisation', async (status) => {
  await open();
  if (status === 0) fetchMock.mockRejectedValueOnce(new Error('Network'));
  else fetchMock.mockResolvedValueOnce(response(status, { message: 'Indisponible' }));
  await fireEvent.press(screen.getByText('Actualiser'));
  expect(await screen.findByText('actualisation refusée')).toBeTruthy();
  expect(screen.getByText(profile.name)).toBeTruthy();
  expect(await getToken()).toBe('jeton-pilote');
});

it('déconnecte toujours lorsque le serveur révoque le jeton avec un 401', async () => {
  await open();
  fetchMock.mockResolvedValueOnce(response(401, { message: 'Jeton révoqué' }));
  await fireEvent.press(screen.getByText('Actualiser'));
  expect(await screen.findByText('déconnecté')).toBeTruthy();
  expect(await getToken()).toBeNull();
});

it('ignore un profil arrivé après la suppression du jeton', async () => {
  await open();
  let resolve!: (value: Response) => void;
  fetchMock.mockImplementationOnce(() => new Promise<Response>((done) => { resolve = done; }));
  await userEvent.setup().press(screen.getByRole('button', { name: 'Actualiser' }));
  await act(async () => { await clearToken(); });
  await act(async () => { resolve(profileResponse()); });
  expect(screen.getByText('déconnecté')).toBeTruthy();
  expect(await getToken()).toBeNull();
});

