/**
 * Connexion livreur, de bout en bout dans l'app : écran → `SessionProvider` → `signIn()` → vrai client
 * d'API → `fetch` simulé. Le livreur s'identifie par `driver_id` (jamais le téléphone), sans Bearer ;
 * un succès ouvre la session, un refus affiche le message du serveur et les erreurs par champ.
 *
 * ⚠️ `@testing-library/react-native` 14 rend en **asynchrone** (`await render`, `await fireEvent`).
 */
import { Text } from 'react-native';
import { fireEvent, render, screen } from '@testing-library/react-native';
import * as SecureStore from 'expo-secure-store';

import LoginScreen from '../../app/(auth)/login';
import { clearToken } from '../api/session';
import { SessionProvider, useSession } from '../session/SessionProvider';
import { t } from '../i18n';

jest.mock('../api/config', () => ({ API_BASE_URL: 'https://example.test/api/v10', API_KEY: 'test', REQUEST_TIMEOUT_MS: 20_000 }));
jest.mock('expo-router', () => ({ Link: 'Text' }));
jest.mock('expo-secure-store', () => ({
  getItemAsync: jest.fn(),
  setItemAsync: jest.fn(),
  deleteItemAsync: jest.fn(),
}));
jest.mock('../push', () => ({ desabonnerAppareil: jest.fn(async () => undefined) }));

type Appel = [string, RequestInit & { headers: Record<string, string> }];
const fetchMock = jest.fn<Promise<unknown>, Appel>();
type Champ = ReturnType<typeof screen.getByText>;

/** Le n-ième appel à `fetch` ; échoue lisiblement s'il n'a pas eu lieu. */
function appel(i: number, appels: Appel[] = fetchMock.mock.calls): Appel {
  const trouve = appels[i];
  if (!trouve) throw new Error(`pas d'appel n° ${i} à fetch`);
  return trouve;
}

function reponse(status: number, corps: unknown) {
  return { ok: status >= 200 && status < 300, status, text: async () => JSON.stringify(corps) };
}

function Etat() {
  const { loading, user } = useSession();
  if (loading) return <Text>session:chargement</Text>;
  return <Text>{user ? `session:${user.name}` : 'session:aucune'}</Text>;
}

async function afficher() {
  const rendu = await render(
    <SessionProvider>
      <LoginScreen />
      <Etat />
    </SessionProvider>,
  );
  await screen.findByText('session:aucune');
  const champs = rendu.container.queryAll((n) => n.type === 'TextInput');
  if (champs.length !== 2) throw new Error(`2 champs attendus, ${champs.length} trouvés`);
  const [identifiant, motDePasse] = champs as [Champ, Champ];
  return { identifiant, motDePasse };
}

beforeAll(() => {
  globalThis.fetch = fetchMock as unknown as typeof fetch;
});

beforeEach(async () => {
  jest.resetAllMocks();
  jest.mocked(SecureStore.getItemAsync).mockResolvedValue(null);
  jest.mocked(SecureStore.setItemAsync).mockResolvedValue(undefined);
  jest.mocked(SecureStore.deleteItemAsync).mockResolvedValue(undefined);
  await clearToken();
  jest.clearAllMocks();
});

describe('écran de connexion', () => {
  it("demande l'identifiant livreur, pas le téléphone", async () => {
    await afficher();
    expect(screen.getByText(t('auth.driverId'))).toBeTruthy();
    expect(screen.getByText(t('auth.driverIdHint'))).toBeTruthy();
    expect(screen.queryByText(t('auth.phone'))).toBeNull();
  });

  it("refuse un formulaire incomplet sans appeler l'API", async () => {
    const { identifiant } = await afficher();
    await fireEvent.changeText(identifiant, '   ');
    await fireEvent.press(screen.getByText(t('auth.signIn')));

    expect(screen.getByText(t('errors.requiredField'))).toBeTruthy();
    expect(fetchMock).not.toHaveBeenCalled();
  });

  it('envoie driver_id et mot de passe à deliveryman/login, puis ouvre la session', async () => {
    fetchMock.mockResolvedValueOnce(
      reponse(200, {
        success: true,
        message: 'Connecté',
        data: { token: 'jeton-neuf', user: { id: 4, name: 'Koffi Agbo', email: null, phone: null } },
      }),
    );
    const { identifiant, motDePasse } = await afficher();

    await fireEvent.changeText(identifiant, ' 20241 ');
    await fireEvent.changeText(motDePasse, 'secret-123');
    await fireEvent.press(screen.getByText(t('auth.signIn')));

    expect(await screen.findByText('session:Koffi Agbo')).toBeTruthy();
    expect(fetchMock).toHaveBeenCalledTimes(1);
    const [url, init] = appel(0);
    expect(url).toBe('https://example.test/api/v10/deliveryman/login');
    expect(init.method).toBe('POST');
    expect(init.headers.apiKey).toBe('test');
    expect(init.headers.Authorization).toBeUndefined();
    expect(JSON.parse(init.body as string)).toEqual({ driver_id: '20241', password: 'secret-123' });
    expect(SecureStore.setItemAsync).toHaveBeenCalledWith('beninlink.livreur.token', 'jeton-neuf');
  });

  it('affiche le refus du serveur et reste déconnecté', async () => {
    fetchMock.mockResolvedValueOnce(reponse(401, { success: false, message: 'Identifiant ou mot de passe incorrect.' }));
    const { identifiant, motDePasse } = await afficher();

    await fireEvent.changeText(identifiant, '20241');
    await fireEvent.changeText(motDePasse, 'mauvais');
    await fireEvent.press(screen.getByText(t('auth.signIn')));

    expect(await screen.findByText('Identifiant ou mot de passe incorrect.')).toBeTruthy();
    expect(screen.getByText('session:aucune')).toBeTruthy();
    expect(SecureStore.setItemAsync).not.toHaveBeenCalled();
  });

  it('place les erreurs de validation sous leur champ', async () => {
    fetchMock.mockResolvedValueOnce(
      reponse(422, {
        message: 'Les données fournies sont invalides.',
        errors: { driver_id: ['Identifiant livreur inconnu.'], password: ['Mot de passe requis.'] },
      }),
    );
    const { identifiant, motDePasse } = await afficher();

    await fireEvent.changeText(identifiant, '99999');
    await fireEvent.changeText(motDePasse, 'x');
    await fireEvent.press(screen.getByText(t('auth.signIn')));

    expect(await screen.findByText('Identifiant livreur inconnu.')).toBeTruthy();
    expect(screen.getByText('Mot de passe requis.')).toBeTruthy();
    expect(screen.getByText('Les données fournies sont invalides.')).toBeTruthy();
  });

  it('dit en français que le serveur est injoignable', async () => {
    fetchMock.mockRejectedValueOnce(new TypeError('Network request failed'));
    const { identifiant, motDePasse } = await afficher();

    await fireEvent.changeText(identifiant, '20241');
    await fireEvent.changeText(motDePasse, 'secret');
    await fireEvent.press(screen.getByText(t('auth.signIn')));

    expect(await screen.findByText(t('errors.network'))).toBeTruthy();
  });
});
