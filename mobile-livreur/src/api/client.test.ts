/**
 * Le client d'API unique de l'app livreur : ce que CHAQUE appel envoie et ce qu'il fait d'une réponse.
 *
 * - en-tête `apiKey` sur toutes les routes, `Authorization: Bearer` seulement quand l'appel est authentifié
 *   et qu'un jeton existe (CheckApiKeyMiddleware puis auth:sanctum) ;
 * - enveloppe `success` / `message` / `data` dépliée, objet nu rendu tel quel ;
 * - `ApiError` porte le statut et les erreurs de validation (422, sous `errors` ou `data.message`) ;
 * - un 401 efface le jeton et prévient la session (S144) ;
 * - réseau coupé, délai dépassé, réponse non JSON : un message français, jamais une exception brute ;
 * - un `FormData` (photo, signature) part tel quel, sans `Content-Type` posé à la main.
 */
import * as SecureStore from 'expo-secure-store';

import { api, ApiError, request } from './client';
import { REQUEST_TIMEOUT_MS } from './config';
import { clearToken, onTokenCleared, setToken } from './session';

jest.mock('./config', () => ({
  API_BASE_URL: 'https://example.test/api/v10',
  API_KEY: 'cle-de-test',
  REQUEST_TIMEOUT_MS: 20_000,
}));
jest.mock('expo-secure-store', () => ({
  getItemAsync: jest.fn(),
  setItemAsync: jest.fn(),
  deleteItemAsync: jest.fn(),
}));

// FormData de React Native (celui de l'appareil) : celui de Node refuse un descripteur { uri, name, type }.
const RNFormData = jest.requireActual('react-native/Libraries/Network/FormData').default as typeof FormData;
const NodeFormData = globalThis.FormData;

type Appel = [string, RequestInit & { headers: Record<string, string> }];
const fetchMock = jest.fn<Promise<unknown>, Appel>();

function reponse(status: number, corps: unknown) {
  return {
    ok: status >= 200 && status < 300,
    status,
    text: async () => (typeof corps === 'string' ? corps : JSON.stringify(corps)),
  };
}

function dernierAppel(): { url: string; init: Appel[1] } {
  const dernier = fetchMock.mock.calls.at(-1);
  if (!dernier) throw new Error('aucun appel à fetch');
  const [url, init] = dernier;
  return { url, init };
}

beforeAll(() => {
  globalThis.fetch = fetchMock as unknown as typeof fetch;
  globalThis.FormData = RNFormData;
});

afterAll(() => {
  globalThis.FormData = NodeFormData;
});

beforeEach(async () => {
  jest.resetAllMocks();
  jest.mocked(SecureStore.getItemAsync).mockResolvedValue(null);
  jest.mocked(SecureStore.setItemAsync).mockResolvedValue(undefined);
  jest.mocked(SecureStore.deleteItemAsync).mockResolvedValue(undefined);
  await clearToken(); // vide le cache du jeton entre deux scénarios
  jest.clearAllMocks(); // garde les implémentations, oublie l'appel de ce nettoyage
});

afterEach(() => {
  jest.useRealTimers();
});

describe('en-têtes', () => {
  it('pose apiKey et Accept sur une route publique, sans Bearer', async () => {
    await setToken('jeton-livreur');
    fetchMock.mockResolvedValueOnce(reponse(200, { success: true, data: {} }));

    await api.post('deliveryman/login', { driver_id: '20241' }, { authenticated: false });

    const { url, init } = dernierAppel();
    expect(url).toBe('https://example.test/api/v10/deliveryman/login');
    expect(init.method).toBe('POST');
    expect(init.headers.apiKey).toBe('cle-de-test');
    expect(init.headers.Accept).toBe('application/json');
    expect(init.headers.Authorization).toBeUndefined();
  });

  it('ajoute le Bearer sur une route authentifiée quand un jeton existe', async () => {
    await setToken('jeton-livreur');
    fetchMock.mockResolvedValueOnce(reponse(200, { data: { ok: 1 } }));

    await api.get('deliveryman/profile');

    const { init } = dernierAppel();
    expect(init.method).toBe('GET');
    expect(init.headers.apiKey).toBe('cle-de-test');
    expect(init.headers.Authorization).toBe('Bearer jeton-livreur');
    expect(init.body).toBeUndefined();
    expect(init.headers['Content-Type']).toBeUndefined();
  });

  it('relit le jeton dans le stockage sécurisé au premier appel', async () => {
    jest.mocked(SecureStore.getItemAsync).mockResolvedValue('jeton-relu');
    fetchMock.mockResolvedValueOnce(reponse(200, { data: [] }));

    await api.get('deliveryman/dashboard');

    expect(SecureStore.getItemAsync).toHaveBeenCalledWith('beninlink.livreur.token');
    expect(dernierAppel().init.headers.Authorization).toBe('Bearer jeton-relu');
  });

  it("n'envoie pas de Bearer sans jeton, même sur une route authentifiée", async () => {
    fetchMock.mockResolvedValueOnce(reponse(200, { data: [] }));

    await api.get('deliveryman/dashboard');

    const { init } = dernierAppel();
    expect(init.headers.apiKey).toBe('cle-de-test');
    expect(init.headers.Authorization).toBeUndefined();
  });

  it('sérialise un corps JSON avec son Content-Type', async () => {
    fetchMock.mockResolvedValueOnce(reponse(200, { data: null }));

    await api.put('update-password', { old_password: 'a', new_password: 'b' });

    const { init } = dernierAppel();
    expect(init.method).toBe('PUT');
    expect(init.headers['Content-Type']).toBe('application/json');
    expect(JSON.parse(init.body as string)).toEqual({ old_password: 'a', new_password: 'b' });
  });

  it('laisse partir un FormData tel quel, sans Content-Type posé à la main', async () => {
    fetchMock.mockResolvedValueOnce(reponse(200, { data: null }));
    const form = new FormData();
    form.append('image', { uri: 'file:///photo.jpg', name: 'livraison-1.jpg', type: 'image/jpeg' } as unknown as Blob);

    await api.post('deliveryman/parcel/delivered/1', form);

    const { init } = dernierAppel();
    expect(init.body).toBe(form);
    expect(init.headers['Content-Type']).toBeUndefined();
    expect(init.headers.apiKey).toBe('cle-de-test');
  });

  it('construit la chaîne de requête en ignorant les valeurs absentes', async () => {
    fetchMock.mockResolvedValueOnce(reponse(200, { data: [] }));

    await api.get('/deliveryman/parcel/index', { query: { page: 2, statut: undefined, actif: true } });

    expect(dernierAppel().url).toBe('https://example.test/api/v10/deliveryman/parcel/index?page=2&actif=true');
  });

  it('envoie DELETE sans corps', async () => {
    fetchMock.mockResolvedValueOnce(reponse(204, ''));

    await expect(api.delete('push/forget')).resolves.toBeNull();
    expect(dernierAppel().init.method).toBe('DELETE');
    expect(dernierAppel().init.body).toBeUndefined();
  });
});

describe('réponses', () => {
  it("déplie l'enveloppe et rend `data`", async () => {
    fetchMock.mockResolvedValueOnce(reponse(200, { success: true, message: 'ok', data: { id: 7 } }));
    await expect(api.get('deliveryman/profile')).resolves.toEqual({ id: 7 });
  });

  it("rend l'objet nu quand la réponse n'a pas de clé `data`", async () => {
    fetchMock.mockResolvedValueOnce(reponse(200, { token: 'x', user: { id: 1 } }));
    await expect(api.get('refresh')).resolves.toEqual({ token: 'x', user: { id: 1 } });
  });

  it('rend `null` pour une réponse vide', async () => {
    fetchMock.mockResolvedValueOnce(reponse(200, '   '));
    await expect(api.post('sign-out')).resolves.toBeNull();
  });

  it('refuse une réponse non JSON avec un message lisible et le statut HTTP', async () => {
    fetchMock.mockResolvedValueOnce(reponse(200, '<html>installeur</html>'));
    const erreur = await api.get('deliveryman/dashboard').catch((e: unknown) => e);
    expect(erreur).toBeInstanceOf(ApiError);
    expect(erreur).toMatchObject({ status: 200, message: 'Réponse inattendue du serveur (HTTP 200).' });
  });
});

describe('erreurs', () => {
  it('porte le statut et le message du serveur', async () => {
    fetchMock.mockResolvedValueOnce(reponse(404, { success: false, message: 'Colis introuvable.', data: [] }));
    const erreur = (await api.get('deliveryman/parcel/details/9').catch((e: unknown) => e)) as ApiError;
    expect(erreur).toBeInstanceOf(ApiError);
    expect(erreur.status).toBe(404);
    expect(erreur.message).toBe('Colis introuvable.');
    expect(erreur.isUnauthenticated).toBe(false);
    expect(erreur.isValidation).toBe(false);
    expect(erreur.errors).toEqual({});
  });

  it('lit les erreurs de validation 422 sous `errors`', async () => {
    fetchMock.mockResolvedValueOnce(
      reponse(422, { message: 'Données invalides.', errors: { driver_id: ['Identifiant requis.'], password: 'Trop court.' } }),
    );
    const erreur = (await api.post('deliveryman/login', {}, { authenticated: false }).catch((e: unknown) => e)) as ApiError;
    expect(erreur.isValidation).toBe(true);
    expect(erreur.message).toBe('Données invalides.');
    expect(erreur.errors).toEqual({ driver_id: ['Identifiant requis.'], password: ['Trop court.'] });
  });

  it('lit les erreurs de validation 422 sous `data.message` (forme du socle)', async () => {
    fetchMock.mockResolvedValueOnce(
      reponse(422, { success: false, message: '', data: { message: { cash_collection: ['Montant entier requis.'] } } }),
    );
    const erreur = (await api.post('deliveryman/parcel-status-update', {}).catch((e: unknown) => e)) as ApiError;
    expect(erreur.status).toBe(422);
    expect(erreur.errors).toEqual({ cash_collection: ['Montant entier requis.'] });
    // Ni `message` ni `data.message` texte : repli sur le statut.
    expect(erreur.message).toBe('Erreur serveur (HTTP 422).');
  });

  it('prend `data.message` texte quand `message` est vide', async () => {
    fetchMock.mockResolvedValueOnce(reponse(400, { success: false, message: ' ', data: { message: 'Course déjà livrée.' } }));
    await expect(api.post('deliveryman/parcel-status-update', {})).rejects.toMatchObject({
      status: 400,
      message: 'Course déjà livrée.',
    });
  });

  it('prend une chaîne JSON nue comme message', async () => {
    fetchMock.mockResolvedValueOnce(reponse(500, '"Panne du serveur."'));
    await expect(api.get('deliveryman/dashboard')).rejects.toMatchObject({ status: 500, message: 'Panne du serveur.' });
  });

  it('se replie sur le statut HTTP sans message exploitable', async () => {
    fetchMock.mockResolvedValueOnce(reponse(503, ''));
    await expect(api.get('deliveryman/dashboard')).rejects.toMatchObject({
      status: 503,
      message: 'Erreur serveur (HTTP 503).',
    });
  });
});

describe('401 et jeton révoqué (S144)', () => {
  it('efface le jeton et prévient les écouteurs sur un 401 authentifié', async () => {
    await setToken('jeton-revoque');
    const ecouteur = jest.fn();
    const retirer = onTokenCleared(ecouteur);
    fetchMock.mockResolvedValueOnce(reponse(401, { message: 'Unauthenticated.' }));

    const erreur = (await api.get('deliveryman/profile').catch((e: unknown) => e)) as ApiError;
    retirer();

    expect(erreur.isUnauthenticated).toBe(true);
    expect(erreur.message).toBe('Unauthenticated.');
    expect(SecureStore.deleteItemAsync).toHaveBeenCalledWith('beninlink.livreur.token');
    expect(ecouteur).toHaveBeenCalledTimes(1);

    // L'appel suivant part sans Bearer : le cache a été vidé.
    fetchMock.mockResolvedValueOnce(reponse(200, { data: [] }));
    await api.get('deliveryman/dashboard');
    expect(dernierAppel().init.headers.Authorization).toBeUndefined();
  });

  it("n'efface pas le jeton sur un 403 ou un 422", async () => {
    await setToken('jeton-valide');
    fetchMock
      .mockResolvedValueOnce(reponse(403, { message: 'Interdit.' }))
      .mockResolvedValueOnce(reponse(422, { message: 'Invalide.' }));

    await expect(api.get('parcel/index')).rejects.toMatchObject({ status: 403 });
    await expect(api.post('deliveryman/parcel-status-update', {})).rejects.toMatchObject({ status: 422 });

    expect(SecureStore.deleteItemAsync).not.toHaveBeenCalled();
    fetchMock.mockResolvedValueOnce(reponse(200, { data: [] }));
    await api.get('deliveryman/dashboard');
    expect(dernierAppel().init.headers.Authorization).toBe('Bearer jeton-valide');
  });

  it('efface aussi le jeton sur un 401 de route publique (connexion refusée) — comportement actuel', async () => {
    const ecouteur = jest.fn();
    const retirer = onTokenCleared(ecouteur);
    fetchMock.mockResolvedValueOnce(reponse(401, { message: 'Identifiants incorrects.' }));

    await expect(api.post('deliveryman/login', {}, { authenticated: false })).rejects.toMatchObject({ status: 401 });
    retirer();

    expect(ecouteur).toHaveBeenCalledTimes(1);
  });
});

describe('réseau', () => {
  it('traduit une coupure réseau en ApiError de statut 0', async () => {
    fetchMock.mockRejectedValueOnce(new TypeError('Network request failed'));
    await expect(api.get('deliveryman/dashboard')).rejects.toMatchObject({
      name: 'ApiError',
      status: 0,
      message: 'Connexion au serveur impossible.',
    });
  });

  it('abandonne la requête au-delà du délai et le dit en français', async () => {
    jest.useFakeTimers();
    fetchMock.mockImplementationOnce(
      (_url, init) =>
        new Promise((_resolve, reject) => {
          init.signal?.addEventListener('abort', () => reject(new Error('aborted')));
        }),
    );

    // Route publique : le minuteur est armé sans attendre le stockage du jeton.
    const promesse = request('password/email', { method: 'POST', body: {}, authenticated: false });
    const verdict = expect(promesse).rejects.toMatchObject({
      status: 0,
      message: 'La requête a expiré. Vérifiez votre connexion.',
    });
    jest.advanceTimersByTime(REQUEST_TIMEOUT_MS);
    await verdict;
  });

  it("ne coupe pas avant le délai", async () => {
    jest.useFakeTimers();
    let signal: AbortSignal | null | undefined;
    fetchMock.mockImplementationOnce(async (_url, init) => {
      signal = init.signal;
      return reponse(200, { data: 'ok' });
    });

    await expect(request('password/email', { method: 'POST', body: {}, authenticated: false })).resolves.toBe('ok');
    jest.advanceTimersByTime(REQUEST_TIMEOUT_MS * 2);
    expect(signal?.aborted).toBe(false); // le minuteur a été annulé après la réponse
  });

  it("répercute l'abandon demandé par l'appelant (rapporté comme un délai dépassé)", async () => {
    const controleur = new AbortController();
    fetchMock.mockImplementationOnce(
      (_url, init) =>
        new Promise((_resolve, reject) => {
          init.signal?.addEventListener('abort', () => reject(new Error('aborted')));
        }),
    );

    const promesse = request('deliveryman/dashboard', { authenticated: false, signal: controleur.signal });
    controleur.abort();

    await expect(promesse).rejects.toMatchObject({ status: 0, message: 'La requête a expiré. Vérifiez votre connexion.' });
  });
});
