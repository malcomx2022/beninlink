/**
 * Client HTTP unique (`client.ts`) — ce que l'app envoie au backend et comment
 * elle lit ce qu'il répond.
 *
 * Garde : l'en-tête `apiKey` sur toutes les routes, le Bearer seulement sur les
 * routes protégées et seulement s'il y a un jeton, la lecture de l'enveloppe
 * (`data`, `page` S78), l'`ApiError` (statut, erreurs 422, `data`), l'effacement
 * du jeton sur un 401 (S144), le délai et la panne réseau, la chaîne de requête.
 *
 * Seul `fetch` est simulé : la session (`session.ts`) est la vraie, sur un
 * expo-secure-store en mémoire.
 */
import * as SecureStore from 'expo-secure-store';

import { ApiError, api, request, requestPaged } from './client';
import { clearToken, onTokenCleared, setToken } from './session';
import {
  TEST_API_BASE_URL,
  TEST_API_KEY,
  fetchCalls,
  installFetch,
  jsonResponse,
  ok,
  singleCall,
} from '../testing/fetchMock';

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

let fetchMock: jest.Mock;

beforeEach(async () => {
  jest.resetAllMocks();
  jest.mocked(SecureStore.getItemAsync).mockResolvedValue(null);
  jest.mocked(SecureStore.setItemAsync).mockResolvedValue(undefined);
  jest.mocked(SecureStore.deleteItemAsync).mockResolvedValue(undefined);
  await clearToken(); // aucun jeton en cache d'un test à l'autre
  jest.mocked(SecureStore.deleteItemAsync).mockClear();
  fetchMock = installFetch();
});

afterEach(() => {
  jest.useRealTimers();
});

describe('en-têtes', () => {
  it('envoie apiKey et Accept sur une route publique, sans Bearer même si un jeton existe', async () => {
    await setToken('jeton-marchand');
    fetchMock.mockResolvedValueOnce(ok({ token: 't' }));

    await api.post('signin', { merchant_id: '2024' }, { authenticated: false });

    const call = singleCall(fetchMock);
    expect(call.headers.apiKey).toBe(TEST_API_KEY);
    expect(call.headers.Accept).toBe('application/json');
    expect(call.headers.Authorization).toBeUndefined();
  });

  it('ajoute le Bearer sur une route protégée quand un jeton est enregistré', async () => {
    await setToken('jeton-marchand');
    fetchMock.mockResolvedValueOnce(ok({}));

    await api.get('profile');

    const call = singleCall(fetchMock);
    expect(call.headers.apiKey).toBe(TEST_API_KEY);
    expect(call.headers.Authorization).toBe('Bearer jeton-marchand');
  });

  it("n'envoie pas de Bearer vide quand aucun jeton n'existe", async () => {
    fetchMock.mockResolvedValueOnce(ok({}));

    await api.get('profile');

    expect(singleCall(fetchMock).headers).not.toHaveProperty('Authorization');
  });

  it('pose Content-Type et sérialise le corps seulement quand il y en a un', async () => {
    fetchMock.mockResolvedValueOnce(ok({})).mockResolvedValueOnce(ok({}));

    await api.post('fedapay/initiate', { amount: 5000 });
    await api.delete('shops/delete/3');

    const [post, del] = fetchCalls(fetchMock);
    expect(post!.method).toBe('POST');
    expect(post!.headers['Content-Type']).toBe('application/json');
    expect(post!.body).toEqual({ amount: 5000 });
    expect(del!.method).toBe('DELETE');
    expect(del!.headers).not.toHaveProperty('Content-Type');
    expect(fetchMock.mock.calls[1]![1].body).toBeUndefined();
  });
});

describe('adresse et chaîne de requête', () => {
  it('colle le chemin à la base sans double barre', async () => {
    fetchMock.mockResolvedValueOnce(ok({}));
    await api.get('/parcel/index');
    expect(singleCall(fetchMock).url).toBe(`${TEST_API_BASE_URL}/parcel/index`);
  });

  it('encode la requête et omet les valeurs undefined', async () => {
    fetchMock.mockResolvedValueOnce(ok({}));
    await api.get('customs/alerts', { query: { status: undefined, page: 2, q: 'a b&c', actif: true } });
    expect(singleCall(fetchMock).url).toBe(`${TEST_API_BASE_URL}/customs/alerts?page=2&q=a+b%26c&actif=true`);
  });

  it("n'ajoute pas de « ? » quand toutes les valeurs sont undefined", async () => {
    fetchMock.mockResolvedValueOnce(ok({}));
    await api.get('customs/alerts', { query: { status: undefined } });
    expect(singleCall(fetchMock).url).toBe(`${TEST_API_BASE_URL}/customs/alerts`);
  });
});

describe('enveloppe de réponse', () => {
  it('rend `data` quand la réponse est enveloppée', async () => {
    fetchMock.mockResolvedValueOnce(ok({ parcels: [{ id: 1 }] }));
    await expect(request('parcel/index')).resolves.toEqual({ parcels: [{ id: 1 }] });
  });

  it("rend l'objet nu quand il n'y a pas de clé `data`", async () => {
    fetchMock.mockResolvedValueOnce(jsonResponse(200, { total_cod: 1500 }));
    await expect(request('dashboard/balance-details')).resolves.toEqual({ total_cod: 1500 });
  });

  it('rend le tableau nu tel quel', async () => {
    fetchMock.mockResolvedValueOnce(jsonResponse(200, [{ key: 1, value: 'En attente' }]));
    await expect(request('parcel/all/status')).resolves.toEqual([{ key: 1, value: 'En attente' }]);
  });

  it('rend null sur une réponse vide (204)', async () => {
    fetchMock.mockResolvedValueOnce(jsonResponse(204));
    await expect(request('notifications/read-all', { method: 'PUT' })).resolves.toBeNull();
  });

  it('garde le bloc `page` de la racine (S78)', async () => {
    const page = { current: 1, per_page: 10, last: 3, total: 25 };
    fetchMock.mockResolvedValueOnce(ok({ entries: [] }, { page }));
    await expect(requestPaged('wallet/history')).resolves.toEqual({ data: { entries: [] }, page });
  });

  it("rend `page: null` quand le bloc manque ou n'a pas la bonne forme", async () => {
    fetchMock
      .mockResolvedValueOnce(ok({ entries: [] }))
      .mockResolvedValueOnce(ok({ entries: [] }, { page: { current: '1', per_page: 10, last: 3, total: 25 } }))
      .mockResolvedValueOnce(jsonResponse(200, [1, 2]));

    await expect(api.getPaged('wallet/history')).resolves.toEqual({ data: { entries: [] }, page: null });
    await expect(api.getPaged('wallet/history')).resolves.toEqual({ data: { entries: [] }, page: null });
    await expect(api.getPaged('wallet/history')).resolves.toEqual({ data: [1, 2], page: null });
  });
});

describe('erreurs', () => {
  async function erreur(promise: Promise<unknown>): Promise<ApiError> {
    try {
      await promise;
    } catch (e) {
      expect(e).toBeInstanceOf(ApiError);
      return e as ApiError;
    }
    throw new Error("L'appel aurait dû échouer.");
  }

  it("porte le statut, le message du serveur et le `data` de l'enveloppe", async () => {
    fetchMock.mockResolvedValueOnce(
      jsonResponse(422, {
        success: false,
        message: 'Solde insuffisant.',
        data: { required: 1500, wallet_balance: 500, missing: 1000 },
      }),
    );

    const e = await erreur(api.post('parcel/store', {}));

    expect(e.status).toBe(422);
    expect(e.isValidation).toBe(true);
    expect(e.isUnauthenticated).toBe(false);
    expect(e.message).toBe('Solde insuffisant.');
    expect(e.data).toEqual({ required: 1500, wallet_balance: 500, missing: 1000 });
  });

  it('lit les erreurs par champ sous `errors` (Laravel) et sous `data.message` (backend)', async () => {
    fetchMock
      .mockResolvedValueOnce(jsonResponse(422, { message: 'Invalide.', errors: { email: ['E-mail requis.'] } }))
      .mockResolvedValueOnce(
        jsonResponse(422, {
          success: false,
          message: 'Mot de passe non modifié.',
          data: { message: { new_password: ['Trop court.', 'Trop simple.'], old_password: 'Requis.' } },
        }),
      );

    const laravel = await erreur(api.post('password/reset', {}, { authenticated: false }));
    const backend = await erreur(api.put('update-password', {}));

    expect(laravel.errors).toEqual({ email: ['E-mail requis.'] });
    expect(backend.errors).toEqual({ new_password: ['Trop court.', 'Trop simple.'], old_password: ['Requis.'] });
  });

  it('prend `data.message` quand le message de racine est vide, sinon un repli avec le statut', async () => {
    fetchMock
      .mockResolvedValueOnce(jsonResponse(400, { message: '', data: { message: 'Boutique introuvable.' } }))
      .mockResolvedValueOnce(jsonResponse(500, { success: false }));

    expect((await erreur(api.get('shops/edit/9'))).message).toBe('Boutique introuvable.');
    const repli = await erreur(api.get('dashboard'));
    expect(repli.message).toBe('Erreur serveur (HTTP 500).');
    expect(repli.errors).toEqual({});
    expect(repli.data).toBeNull();
  });

  it('refuse une réponse non JSON (page HTML) avec le statut HTTP', async () => {
    fetchMock.mockResolvedValueOnce(jsonResponse(502, '<html>Bad gateway</html>'));
    const e = await erreur(api.get('dashboard'));
    expect(e.status).toBe(502);
    expect(e.message).toBe('Réponse inattendue du serveur (HTTP 502).');
  });

  it('dit « connexion impossible » (statut 0) quand le réseau échoue', async () => {
    fetchMock.mockRejectedValueOnce(new TypeError('Network request failed'));
    const e = await erreur(api.get('dashboard'));
    expect(e.status).toBe(0);
    expect(e.message).toBe('Connexion au serveur impossible.');
  });

  it('abandonne la requête au-delà du délai et le dit', async () => {
    jest.useFakeTimers();
    fetchMock.mockImplementationOnce(
      (_url: string, init: RequestInit) =>
        new Promise((_resolve, reject) => {
          init.signal?.addEventListener('abort', () => reject(new Error('aborted')));
        }),
    );

    const pending = erreur(api.get('dashboard'));
    await jest.advanceTimersByTimeAsync(20_000);
    const e = await pending;

    expect(e.status).toBe(0);
    expect(e.message).toBe('La requête a expiré. Vérifiez votre connexion.');
  });

  it("relaie l'abandon demandé par l'appelant (devis obsolète)", async () => {
    const controller = new AbortController();
    fetchMock.mockImplementationOnce(
      (_url: string, init: RequestInit) =>
        new Promise((_resolve, reject) => {
          init.signal?.addEventListener('abort', () => reject(new Error('aborted')));
          controller.abort(); // l'écran abandonne pendant que la requête est en vol
        }),
    );

    const e = await erreur(api.post('parcel/quote', {}, { signal: controller.signal }));

    expect(e.status).toBe(0);
    expect(e.message).toBe('La requête a expiré. Vérifiez votre connexion.');
  });
});

describe('401 (S144)', () => {
  it("efface le jeton et prévient les écouteurs quand une route protégée répond 401", async () => {
    await setToken('jeton-revoque');
    const ecouteur = jest.fn();
    const retirer = onTokenCleared(ecouteur);
    fetchMock
      .mockResolvedValueOnce(jsonResponse(401, { message: 'Unauthenticated.' }))
      .mockResolvedValueOnce(ok({}));

    let e: unknown;
    try {
      await api.get('profile');
    } catch (err) {
      e = err;
    }
    retirer();

    expect((e as ApiError).isUnauthenticated).toBe(true);
    expect(ecouteur).toHaveBeenCalledTimes(1);
    expect(SecureStore.deleteItemAsync).toHaveBeenCalledWith('beninlink.merchant.token');

    // L'appel suivant part sans le jeton révoqué.
    await api.get('profile');
    expect(fetchCalls(fetchMock)[1]!.headers).not.toHaveProperty('Authorization');
  });

  it('garde le jeton sur un 403 ou un 500', async () => {
    await setToken('jeton-valide');
    const ecouteur = jest.fn();
    const retirer = onTokenCleared(ecouteur);
    fetchMock
      .mockResolvedValueOnce(jsonResponse(403, { message: 'Interdit.' }))
      .mockResolvedValueOnce(jsonResponse(500, { message: 'Panne.' }))
      .mockResolvedValueOnce(ok({}));

    await expect(api.get('dashboard')).rejects.toBeInstanceOf(ApiError);
    await expect(api.get('dashboard')).rejects.toBeInstanceOf(ApiError);
    await api.get('dashboard');
    retirer();

    expect(ecouteur).not.toHaveBeenCalled();
    expect(SecureStore.deleteItemAsync).not.toHaveBeenCalled();
    expect(fetchCalls(fetchMock)[2]!.headers.Authorization).toBe('Bearer jeton-valide');
  });
});
