/** S146 : écran, vraie session et vrai client ; seuls fetch et le natif sont simulés. */
import { Text } from 'react-native';
import { act, fireEvent, render, screen, userEvent, waitFor } from '@testing-library/react-native';
import * as SecureStore from 'expo-secure-store';

import ProfileEditScreen from '../../app/(app)/profile/edit';
import { clearToken, getToken, setToken } from '../api/session';
import { SessionProvider, useSession } from '../session/SessionProvider';
import { t } from '../i18n';
import { champ } from '../testing/fields';
import { fetchCalls, installFetch, jsonResponse, ok } from '../testing/fetchMock';

jest.mock('../api/config', () => ({ API_BASE_URL: 'https://example.test/api/v10', API_KEY: 'cle-api-test', REQUEST_TIMEOUT_MS: 20_000 }));
const mockBack = jest.fn();
jest.mock('expo-router', () => ({ useRouter: () => ({ back: mockBack }) }));
jest.mock('expo-secure-store', () => ({ getItemAsync: jest.fn(), setItemAsync: jest.fn(), deleteItemAsync: jest.fn() }));
jest.mock('../push', () => ({ desabonnerAppareil: jest.fn() }));

const profile = { id: 7, name: 'Compte pilote', email: 'pilote@example.test', phone: '2290197000000', address: 'Cotonou', user_type: 2, merchant: { business_name: 'Boutique pilote' } };
let fetchMock: jest.Mock;

function Formulaire() {
  const { loading, user } = useSession();
  if (loading) return <Text>chargement</Text>;
  if (!user) return <Text>session fermée</Text>;
  return <><ProfileEditScreen /><Text>{`compte:${user.name}`}</Text></>;
}

beforeEach(async () => {
  jest.resetAllMocks();
  jest.mocked(SecureStore.getItemAsync).mockResolvedValue(null);
  jest.mocked(SecureStore.setItemAsync).mockResolvedValue(undefined);
  jest.mocked(SecureStore.deleteItemAsync).mockResolvedValue(undefined);
  await clearToken();
  await setToken('jeton-pilote');
  fetchMock = installFetch();
  fetchMock.mockResolvedValueOnce(ok({ user: profile }));
});

async function ouvrir() {
  await render(<SessionProvider><Formulaire /></SessionProvider>);
  await screen.findByText('compte:Compte pilote');
  await fireEvent.changeText(champ(t('auth.managerName')), '  Nouveau gérant  ');
}

async function enregistrer() {
  await fireEvent.press(screen.getByRole('button', { name: t('common.save') }));
}

function ecritures() {
  return fetchCalls(fetchMock).filter((call) => call.method === 'POST');
}

it('enregistre les cinq champs, relit le compte serveur puis revient au profil', async () => {
  fetchMock.mockResolvedValueOnce(ok([])).mockResolvedValueOnce(ok({ user: { ...profile, name: 'Nouveau gérant' } }));
  await ouvrir();
  await enregistrer();
  expect(ecritures()).toHaveLength(1);
  expect(ecritures()[0]).toMatchObject({ path: 'profile/update', headers: { Authorization: 'Bearer jeton-pilote' }, body: { name: 'Nouveau gérant', business_name: 'Boutique pilote', email: 'pilote@example.test', mobile: profile.phone, address: 'Cotonou' } });
  expect(fetchCalls(fetchMock).map((c) => c.path)).toEqual(['profile', 'profile/update', 'profile']);
  expect(await screen.findByText('compte:Nouveau gérant')).toBeTruthy();
  expect(mockBack).toHaveBeenCalledTimes(1);
});

it('refuse un champ obligatoire vide sans écriture', async () => {
  await ouvrir();
  await fireEvent.changeText(champ(t('auth.companyName')), ' ');
  await enregistrer();
  expect(screen.getByText(t('errors.requiredField'))).toBeTruthy();
  expect(ecritures()).toEqual([]);
});

it('affiche le refus 422 par champ sans annoncer un enregistrement', async () => {
  fetchMock.mockResolvedValueOnce(jsonResponse(422, { success: false, message: 'Corrigez le formulaire.', data: { message: { mobile: ['Numéro invalide.'] } } }));
  await ouvrir();
  await enregistrer();
  expect(screen.getByText('Numéro invalide.')).toBeTruthy();
  expect(screen.queryByText(t('profile.saved'))).toBeNull();
  expect(champ(t('auth.phone'))).toHaveProp('editable', true);
  expect(mockBack).not.toHaveBeenCalled();
});

it('permet une nouvelle écriture après un refus serveur de la modification', async () => {
  fetchMock.mockResolvedValueOnce(jsonResponse(503, { success: false, message: 'Service indisponible.', data: [] }));
  await ouvrir();
  await enregistrer();
  expect(screen.queryByText(t('profile.saved'))).toBeNull();
  fetchMock.mockResolvedValueOnce(ok([])).mockResolvedValueOnce(ok({ user: profile }));
  await enregistrer();
  expect(ecritures()).toHaveLength(2);
  expect(mockBack).toHaveBeenCalledTimes(1);
});

it.each(['réseau', '503'])('conserve l’enregistrement après échec de relecture (%s), puis réessaie uniquement la lecture', async (incident) => {
  fetchMock.mockResolvedValueOnce(ok([]));
  if (incident === 'réseau') fetchMock.mockRejectedValueOnce(new TypeError('offline'));
  else fetchMock.mockResolvedValueOnce(jsonResponse(503, { success: false, message: 'Service indisponible.', data: [] }));
  await ouvrir();
  await enregistrer();
  expect(screen.getByText(t('profile.saved'))).toBeTruthy();
  expect(champ(t('auth.managerName'))).toHaveProp('editable', false);
  expect(await getToken()).toBe('jeton-pilote');
  expect(mockBack).not.toHaveBeenCalled();
  fetchMock.mockResolvedValueOnce(ok({ user: { ...profile, name: 'Nouveau gérant' } }));
  await fireEvent.press(screen.getByRole('button', { name: t('profile.refresh') }));
  expect(ecritures()).toHaveLength(1);
  expect(await screen.findByText('compte:Nouveau gérant')).toBeTruthy();
  expect(mockBack).toHaveBeenCalledTimes(1);
});

it('déconnecte toujours sur 401 lors de la relecture', async () => {
  fetchMock.mockResolvedValueOnce(ok([])).mockResolvedValueOnce(jsonResponse(401, { success: false, message: 'Session expirée.', data: [] }));
  await ouvrir();
  await enregistrer();
  expect(await screen.findByText('session fermée')).toBeTruthy();
  expect(await getToken()).toBeNull();
  expect(ecritures()).toHaveLength(1);
  expect(mockBack).not.toHaveBeenCalled();
});

it('confirme l’écriture sans attendre une relecture lente et bloque les champs', async () => {
  let resolve!: (response: Response) => void;
  fetchMock.mockResolvedValueOnce(ok([])).mockImplementationOnce(() => new Promise<Response>((done) => { resolve = done; }));
  await ouvrir();
  await userEvent.setup().press(screen.getByRole('button', { name: t('common.save') }));
  await waitFor(() => expect(fetchMock).toHaveBeenCalledTimes(3));
  expect(screen.getByText(t('profile.saved'))).toBeTruthy();
  expect(champ(t('auth.companyName'))).toHaveProp('editable', false);
  expect(mockBack).not.toHaveBeenCalled();
  await act(async () => resolve(ok({ user: profile })));
  expect(mockBack).toHaveBeenCalledTimes(1);
});
