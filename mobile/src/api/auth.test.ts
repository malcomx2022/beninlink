/**
 * Authentification marchand (`auth.ts`) — ce qui part au backend et ce qui en reste dans la session.
 *
 * Garde : `signin` identifie par `merchant_id` (pas le téléphone), part sans Bearer,
 * et seul un succès range le jeton ; la déconnexion efface le jeton même sans
 * réseau ; l'inscription n'ouvre pas de session (le code SMS le fait) ; le mot de
 * passe oublié passe par des routes publiques et rend le message du serveur.
 *
 * Seul `fetch` est simulé : client et session sont les vrais.
 */
import * as SecureStore from 'expo-secure-store';

import {
  requestPasswordReset,
  resendOtp,
  resetPassword,
  signIn,
  signOut,
  signUp,
  verifyOtp,
} from './auth';
import { ApiError, api } from './client';
import { clearToken, getToken, setToken } from './session';
import { fetchCalls, installFetch, jsonResponse, ok, singleCall } from '../testing/fetchMock';

jest.mock('./config', () => ({
  API_BASE_URL: 'https://example.test/api/v10',
  API_KEY: 'cle-api-test',
  REQUEST_TIMEOUT_MS: 20_000,
}));
jest.mock('expo-secure-store', () => ({
  getItemAsync: jest.fn(),
  setItemAsync: jest.fn(),
  deleteItemAsync: jest.fn(),
}));

const TOKEN_KEY = 'beninlink.merchant.token';
const user = { id: 3, name: 'Boutique Awa', email: null, phone: '2290196000001', user_type: 2 };

let fetchMock: jest.Mock;

beforeEach(async () => {
  jest.resetAllMocks();
  jest.mocked(SecureStore.getItemAsync).mockResolvedValue(null);
  jest.mocked(SecureStore.setItemAsync).mockResolvedValue(undefined);
  jest.mocked(SecureStore.deleteItemAsync).mockResolvedValue(undefined);
  await clearToken();
  jest.mocked(SecureStore.deleteItemAsync).mockClear();
  fetchMock = installFetch();
});

describe('signIn', () => {
  it("envoie merchant_id (rogné) et le mot de passe tel quel, sans Bearer, puis range le jeton", async () => {
    await setToken('ancien-jeton');
    jest.mocked(SecureStore.setItemAsync).mockClear();
    fetchMock.mockResolvedValueOnce(ok({ token: 'jeton-neuf', user }));

    await expect(signIn('  2024 ', ' mot de passe ')).resolves.toEqual(user);

    const call = singleCall(fetchMock);
    expect(call.method).toBe('POST');
    expect(call.path).toBe('signin');
    expect(call.body).toEqual({ merchant_id: '2024', password: ' mot de passe ' });
    expect(call.headers).not.toHaveProperty('Authorization');
    expect(SecureStore.setItemAsync).toHaveBeenCalledWith(TOKEN_KEY, 'jeton-neuf');
    await expect(getToken()).resolves.toBe('jeton-neuf');
  });

  it('le jeton reçu sert aux appels suivants', async () => {
    fetchMock.mockResolvedValueOnce(ok({ token: 'jeton-neuf', user })).mockResolvedValueOnce(ok({}));

    await signIn('2024', 'motdepasse');
    await api.get('profile');

    expect(fetchCalls(fetchMock)[1]!.headers.Authorization).toBe('Bearer jeton-neuf');
  });

  it("ne range aucun jeton quand le serveur refuse l'identifiant", async () => {
    fetchMock.mockResolvedValueOnce(
      jsonResponse(401, { success: false, message: 'Identifiant ou mot de passe incorrect.', data: [] }),
    );

    const echec = signIn('2024', 'faux');

    await expect(echec).rejects.toBeInstanceOf(ApiError);
    await expect(echec).rejects.toMatchObject({ status: 401, message: 'Identifiant ou mot de passe incorrect.' });
    expect(SecureStore.setItemAsync).not.toHaveBeenCalled();
    await expect(getToken()).resolves.toBeNull();
  });
});

describe('signOut', () => {
  it('prévient le serveur avec le jeton, puis efface le jeton local', async () => {
    await setToken('jeton-actif');
    fetchMock.mockResolvedValueOnce(ok([]));

    await signOut();

    const call = singleCall(fetchMock);
    expect(call.method).toBe('POST');
    expect(call.path).toBe('sign-out');
    expect(call.headers.Authorization).toBe('Bearer jeton-actif');
    expect(SecureStore.deleteItemAsync).toHaveBeenCalledWith(TOKEN_KEY);
    await expect(getToken()).resolves.toBeNull();
  });

  it("efface le jeton local même sans réseau, et n'échoue pas", async () => {
    await setToken('jeton-actif');
    fetchMock.mockRejectedValueOnce(new TypeError('Network request failed'));

    await expect(signOut()).resolves.toBeUndefined();

    expect(SecureStore.deleteItemAsync).toHaveBeenCalledWith(TOKEN_KEY);
    await expect(getToken()).resolves.toBeNull();
  });
});

describe('inscription et code SMS', () => {
  it("inscrit sans ouvrir de session et rend le numéro renvoyé par le serveur", async () => {
    fetchMock.mockResolvedValueOnce(ok({ mobile: '2290196000001' }));
    const payload = {
      business_name: 'Boutique Awa',
      full_name: 'Awa Zinsou',
      address: 'Cotonou',
      mobile: '0196000001',
      password: 'motdepasse',
      ifu: '3202400000000',
      rccm: 'RB/COT/24 A 00001',
    };

    await expect(signUp(payload)).resolves.toBe('2290196000001');

    const call = singleCall(fetchMock);
    expect(call.path).toBe('register');
    expect(call.body).toEqual(payload);
    expect(call.headers).not.toHaveProperty('Authorization');
    expect(SecureStore.setItemAsync).not.toHaveBeenCalled();
  });

  it('retombe sur le numéro saisi quand le serveur ne le renvoie pas', async () => {
    fetchMock.mockResolvedValueOnce(ok([]));
    const mobile = await signUp({
      business_name: 'B', full_name: 'F', address: 'A', mobile: '0196000001', password: 'p', ifu: 'i', rccm: 'r',
    });
    expect(mobile).toBe('0196000001');
  });

  it('verifyOtp ouvre la session avec le jeton reçu', async () => {
    fetchMock.mockResolvedValueOnce(ok({ token: 'jeton-otp', user }));

    await expect(verifyOtp('123456')).resolves.toEqual(user);

    const call = singleCall(fetchMock);
    expect(call.path).toBe('otp-verification');
    expect(call.body).toEqual({ otp: '123456' });
    expect(call.headers).not.toHaveProperty('Authorization');
    await expect(getToken()).resolves.toBe('jeton-otp');
  });

  it('resendOtp renvoie le numéro sur la route publique', async () => {
    fetchMock.mockResolvedValueOnce(ok([]));
    await resendOtp('2290196000001');
    const call = singleCall(fetchMock);
    expect(call.path).toBe('resend-otp');
    expect(call.body).toEqual({ mobile: '2290196000001' });
    expect(call.headers).not.toHaveProperty('Authorization');
  });
});

describe('mot de passe oublié', () => {
  it("demande le lien avec l'e-mail rogné et rend `data.message`", async () => {
    fetchMock.mockResolvedValueOnce(ok({ message: 'Lien envoyé.' }));

    await expect(requestPasswordReset('  awa@boutique.bj ')).resolves.toBe('Lien envoyé.');

    const call = singleCall(fetchMock);
    expect(call.path).toBe('password/email');
    expect(call.body).toEqual({ email: 'awa@boutique.bj' });
    expect(call.headers).not.toHaveProperty('Authorization');
  });

  it("rend une chaîne vide quand le serveur ne dit rien (l'écran a son repli)", async () => {
    fetchMock.mockResolvedValueOnce(ok([]));
    await expect(requestPasswordReset('awa@boutique.bj')).resolves.toBe('');
  });

  it('envoie jeton, e-mail rogné, mot de passe et confirmation', async () => {
    fetchMock.mockResolvedValueOnce(ok({ message: 'Mot de passe réinitialisé.' }));

    const message = await resetPassword({
      token: 'abc123',
      email: ' awa@boutique.bj ',
      password: 'nouveau-mdp',
      password_confirmation: 'nouveau-mdp',
    });

    expect(message).toBe('Mot de passe réinitialisé.');
    const call = singleCall(fetchMock);
    expect(call.method).toBe('POST');
    expect(call.path).toBe('password/reset');
    expect(call.body).toEqual({
      token: 'abc123',
      email: 'awa@boutique.bj',
      password: 'nouveau-mdp',
      password_confirmation: 'nouveau-mdp',
    });
    expect(call.headers).not.toHaveProperty('Authorization');
  });
});
