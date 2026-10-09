/**
 * Les appels de session du livreur, à travers le vrai client d'API (seul `fetch` est simulé) :
 * connexion par `driver_id` sans Bearer et jeton enregistré, déconnexion qui efface le jeton même
 * hors réseau, changement de mot de passe confirmé, mot de passe oublié (S98) sur les routes publiques.
 */
import * as SecureStore from 'expo-secure-store';

import { requestPasswordReset, resetPassword, signIn, signOut, updatePassword } from './auth';
import { endpoints } from './endpoints';
import { clearToken, getToken, setToken } from './session';

jest.mock('./config', () => ({
  API_BASE_URL: 'https://example.test/api/v10',
  API_KEY: 'test',
  REQUEST_TIMEOUT_MS: 20_000,
}));
jest.mock('expo-secure-store', () => ({
  getItemAsync: jest.fn(),
  setItemAsync: jest.fn(),
  deleteItemAsync: jest.fn(),
}));

type Appel = [string, RequestInit & { headers: Record<string, string> }];
const fetchMock = jest.fn<Promise<unknown>, Appel>();
const BASE = 'https://example.test/api/v10/';

function reponse(status: number, corps: unknown) {
  return { ok: status >= 200 && status < 300, status, text: async () => JSON.stringify(corps) };
}

function appel(i = 0): { url: string; method?: string; headers: Record<string, string>; body: unknown } {
  const trouve = fetchMock.mock.calls[i];
  if (!trouve) throw new Error(`pas d'appel n° ${i} à fetch`);
  const [url, init] = trouve;
  return {
    url,
    method: init.method,
    headers: init.headers,
    body: typeof init.body === 'string' ? JSON.parse(init.body) : init.body,
  };
}

const livreur = { id: 4, name: 'Koffi Agbo', email: null, phone: '0197000000', user_type: '3' };

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

describe('signIn', () => {
  it('envoie driver_id (rogné) et mot de passe, sans Bearer, puis garde le jeton', async () => {
    await setToken('ancien-jeton');
    fetchMock.mockResolvedValueOnce(
      reponse(200, { success: true, message: 'Connecté', data: { token: 'jeton-neuf', user: livreur } }),
    );

    const user = await signIn('  20241 ', ' secret ');

    const { url, method, headers, body } = appel();
    expect(url).toBe(BASE + endpoints.login);
    expect(url).toBe(BASE + 'deliveryman/login');
    expect(method).toBe('POST');
    expect(headers.Authorization).toBeUndefined();
    expect(headers.apiKey).toBe('test');
    // Identifiant rogné ; le mot de passe part tel que saisi.
    expect(body).toEqual({ driver_id: '20241', password: ' secret ' });
    expect(body).not.toHaveProperty('phone');

    expect(user).toEqual(livreur);
    expect(SecureStore.setItemAsync).toHaveBeenCalledWith('beninlink.livreur.token', 'jeton-neuf');
    await expect(getToken()).resolves.toBe('jeton-neuf');
  });

  it("ne garde aucun jeton quand la connexion est refusée", async () => {
    fetchMock.mockResolvedValueOnce(reponse(401, { success: false, message: 'Identifiant ou mot de passe incorrect.' }));

    await expect(signIn('20241', 'faux')).rejects.toMatchObject({
      status: 401,
      message: 'Identifiant ou mot de passe incorrect.',
    });
    expect(SecureStore.setItemAsync).not.toHaveBeenCalled();
    await expect(getToken()).resolves.toBeNull();
  });
});

describe('signOut', () => {
  it('appelle sign-out avec le Bearer puis efface le jeton', async () => {
    await setToken('jeton-actif');
    fetchMock.mockResolvedValueOnce(reponse(200, { success: true, data: [] }));

    await signOut();

    expect(appel().url).toBe(BASE + 'sign-out');
    expect(appel().method).toBe('POST');
    expect(appel().headers.Authorization).toBe('Bearer jeton-actif');
    expect(SecureStore.deleteItemAsync).toHaveBeenCalledWith('beninlink.livreur.token');
    await expect(getToken()).resolves.toBeNull();
  });

  it('efface le jeton même sans réseau, sans lever', async () => {
    await setToken('jeton-actif');
    fetchMock.mockRejectedValueOnce(new TypeError('Network request failed'));

    await expect(signOut()).resolves.toBeUndefined();

    expect(SecureStore.deleteItemAsync).toHaveBeenCalledWith('beninlink.livreur.token');
    await expect(getToken()).resolves.toBeNull();
  });
});

describe('updatePassword', () => {
  it('envoie PUT update-password avec la confirmation égale au nouveau mot de passe', async () => {
    await setToken('jeton-actif');
    fetchMock.mockResolvedValueOnce(reponse(200, { success: true, message: 'Mot de passe modifié.', data: [] }));

    await updatePassword('ancien-mdp', 'nouveau-mdp-8');

    const { url, method, headers, body } = appel();
    expect(url).toBe(BASE + 'update-password');
    expect(method).toBe('PUT');
    expect(headers.Authorization).toBe('Bearer jeton-actif');
    expect(body).toEqual({
      old_password: 'ancien-mdp',
      new_password: 'nouveau-mdp-8',
      confirm_password: 'nouveau-mdp-8',
    });
  });

  it('remonte les erreurs de champ du serveur', async () => {
    await setToken('jeton-actif');
    fetchMock.mockResolvedValueOnce(
      reponse(422, { message: 'Mot de passe actuel incorrect.', errors: { old_password: ['Incorrect.'] } }),
    );

    await expect(updatePassword('x', 'yyyyyyyy')).rejects.toMatchObject({
      status: 422,
      errors: { old_password: ['Incorrect.'] },
    });
  });
});

describe('mot de passe oublié (S98)', () => {
  it("demande le lien sans Bearer, adresse rognée, et rend le message de `data`", async () => {
    await setToken('jeton-oublie');
    fetchMock.mockResolvedValueOnce(
      reponse(200, { success: true, message: 'Lien envoyé', data: { message: 'Nous vous avons envoyé un lien.' } }),
    );

    const message = await requestPasswordReset('  awa@exemple.bj ');

    expect(appel().url).toBe(BASE + 'password/email');
    expect(appel().method).toBe('POST');
    expect(appel().headers.Authorization).toBeUndefined();
    expect(appel().body).toEqual({ email: 'awa@exemple.bj' });
    expect(message).toBe('Nous vous avons envoyé un lien.');
  });

  it("rend une chaîne vide quand le serveur n'envoie pas de message", async () => {
    fetchMock.mockResolvedValueOnce(reponse(200, { success: true, data: [] }));
    await expect(requestPasswordReset('awa@exemple.bj')).resolves.toBe('');
  });

  it('envoie le jeton, l’adresse rognée et le mot de passe confirmé', async () => {
    fetchMock.mockResolvedValueOnce(
      reponse(200, { success: true, data: { message: 'Votre mot de passe a été réinitialisé.' } }),
    );

    const message = await resetPassword({
      token: 'abc123',
      email: ' awa@exemple.bj ',
      password: 'motdepasse8',
      password_confirmation: 'motdepasse8',
    });

    expect(appel().url).toBe(BASE + 'password/reset');
    expect(appel().headers.Authorization).toBeUndefined();
    expect(appel().body).toEqual({
      token: 'abc123',
      email: 'awa@exemple.bj',
      password: 'motdepasse8',
      password_confirmation: 'motdepasse8',
    });
    expect(message).toBe('Votre mot de passe a été réinitialisé.');
  });
});
