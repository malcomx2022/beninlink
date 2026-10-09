/**
 * L'espace livreur à travers le vrai client d'API (seul `fetch` est simulé) : la bonne route, la bonne
 * méthode, le corps attendu par le socle — `status_action` du backend pour livré / partiel / retour,
 * seul montant transmis = l'encaissé d'un partiel (aucun calcul côté app), preuves photo et signature
 * en multipart — et des réponses incomplètes ramenées à des listes vides plutôt qu'à `undefined`.
 */
import * as SecureStore from 'expo-secure-store';

import {
  fetchDashboard,
  fetchIncomeExpense,
  fetchParcelDetails,
  fetchParcelPaymentLogs,
  fetchProfile,
  reportDelivered,
  reportOutcome,
  updateLocation,
} from './deliveryman';
import { BackendParcelStatus } from '../domain/parcelStatus';
import { clearToken, setToken } from './session';

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

// FormData de React Native (celui de l'appareil) : celui de Node refuse un descripteur { uri, name, type }.
type RNForm = FormData & { getAll: (cle: string) => unknown[]; getParts: () => { fieldName: string }[] };
const RNFormData = jest.requireActual('react-native/Libraries/Network/FormData').default as typeof FormData;
const NodeFormData = globalThis.FormData;

type Appel = [string, RequestInit & { headers: Record<string, string> }];
const fetchMock = jest.fn<Promise<unknown>, Appel>();
const BASE = 'https://example.test/api/v10/';

function reponse(status: number, corps: unknown) {
  return { ok: status >= 200 && status < 300, status, text: async () => JSON.stringify(corps) };
}

function ok(data: unknown) {
  return reponse(200, { success: true, message: '', data });
}

function appel(i = 0) {
  const trouve = fetchMock.mock.calls[i];
  if (!trouve) throw new Error(`pas d'appel n° ${i} à fetch`);
  const [url, init] = trouve;
  return {
    url,
    method: init.method,
    headers: init.headers,
    json: () => JSON.parse(init.body as string),
    form: () => init.body as RNForm,
  };
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
  await clearToken();
  await setToken('jeton-livreur');
  jest.clearAllMocks();
});

describe('issue de la course — reportOutcome()', () => {
  it.each([
    ['delivered', BackendParcelStatus.DELIVERED, 9],
    ['partial', BackendParcelStatus.PARTIAL_DELIVERED, 32],
    ['return', BackendParcelStatus.RETURN_TO_COURIER, 24],
  ] as const)('%s part sur parcel-status-update avec status_action %i', async (action, code, brut) => {
    fetchMock.mockResolvedValueOnce(ok([]));

    await reportOutcome(12, action);

    const a = appel();
    expect(a.url).toBe(BASE + 'deliveryman/parcel-status-update');
    expect(a.method).toBe('POST');
    expect(a.headers.Authorization).toBe('Bearer jeton-livreur');
    expect(a.headers['Content-Type']).toBe('application/json');
    expect(code).toBe(brut);
    expect(a.json()).toEqual({ parcel_id: 12, status_action: brut });
  });

  it("transmet le montant encaissé d'un partiel, tel quel, et la remarque rognée", async () => {
    fetchMock.mockResolvedValueOnce(ok([]));

    await reportOutcome(12, 'partial', { cashCollection: 7500, note: '  Le client a payé la moitié  ' });

    expect(appel().json()).toEqual({
      parcel_id: 12,
      status_action: 32,
      cash_collection: 7500,
      note: 'Le client a payé la moitié',
    });
  });

  it('transmet un encaissement nul explicite (lot 3)', async () => {
    fetchMock.mockResolvedValueOnce(ok([]));
    await reportOutcome(12, 'partial', { cashCollection: 0 });
    expect(appel().json()).toEqual({ parcel_id: 12, status_action: 32, cash_collection: 0 });
  });

  it("omet une remarque faite d'espaces", async () => {
    fetchMock.mockResolvedValueOnce(ok([]));
    await reportOutcome(12, 'return', { note: '   ' });
    expect(appel().json()).toEqual({ parcel_id: 12, status_action: 24 });
  });

  it('envoie un retour signé en multipart, signature sous signatureImage (S106)', async () => {
    fetchMock.mockResolvedValueOnce(ok([]));

    await reportOutcome(12, 'return', { note: ' Absent ', signatureUri: 'file:///sig.png', cashCollection: 999 });

    const a = appel();
    expect(a.url).toBe(BASE + 'deliveryman/parcel-status-update');
    expect(a.headers['Content-Type']).toBeUndefined();
    const form = a.form();
    expect(form).toBeInstanceOf(RNFormData);
    expect(form.getAll('parcel_id')).toEqual(['12']);
    expect(form.getAll('status_action')).toEqual(['24']);
    expect(form.getAll('note')).toEqual(['Absent']);
    expect(form.getAll('signatureImage')).toEqual([
      { uri: 'file:///sig.png', name: 'signature-retour-12.png', type: 'image/png' },
    ]);
    // Un retour ne transmet jamais de montant.
    expect(form.getAll('cash_collection')).toEqual([]);
  });

  it('ignore la signature hors retour : un partiel reste en JSON', async () => {
    fetchMock.mockResolvedValueOnce(ok([]));
    await reportOutcome(12, 'partial', { cashCollection: 100, signatureUri: 'file:///sig.png' });
    expect(appel().json()).toEqual({ parcel_id: 12, status_action: 32, cash_collection: 100 });
  });

  it('remonte le refus du serveur', async () => {
    fetchMock.mockResolvedValueOnce(reponse(422, { message: 'Montant invalide.', errors: { cash_collection: ['Entier requis.'] } }));
    await expect(reportOutcome(12, 'partial', { cashCollection: 5 })).rejects.toMatchObject({
      status: 422,
      message: 'Montant invalide.',
    });
  });
});

describe('livraison avec preuves — reportDelivered()', () => {
  it('envoie photo, signature et remarque en multipart sur parcel/delivered/{id}', async () => {
    fetchMock.mockResolvedValueOnce(ok([]));

    await reportDelivered(31, { note: ' Remis en main propre ', photoUri: 'file:///p.jpg', signatureUri: 'file:///s.png' });

    const a = appel();
    expect(a.url).toBe(BASE + 'deliveryman/parcel/delivered/31');
    expect(a.method).toBe('POST');
    expect(a.headers.Authorization).toBe('Bearer jeton-livreur');
    expect(a.headers['Content-Type']).toBeUndefined();
    const form = a.form();
    expect(form.getAll('note')).toEqual(['Remis en main propre']);
    expect(form.getAll('image')).toEqual([{ uri: 'file:///p.jpg', name: 'livraison-31.jpg', type: 'image/jpeg' }]);
    expect(form.getAll('signatureImage')).toEqual([{ uri: 'file:///s.png', name: 'signature-31.png', type: 'image/png' }]);
  });

  it('envoie un formulaire vide sans preuve ni remarque', async () => {
    fetchMock.mockResolvedValueOnce(ok([]));
    await reportDelivered(31, { note: '  ' });
    expect(appel().form().getParts()).toEqual([]);
  });
});

describe('lectures', () => {
  it('fetchDashboard ramène les listes absentes à des tableaux vides', async () => {
    const course = { id: 1, tracking_id: 'BL-1' };
    fetchMock.mockResolvedValueOnce(ok({ deliveryman_assign: [course], delivered: null }));

    await expect(fetchDashboard()).resolves.toEqual({
      deliveryman_assign: [course],
      deliveryman_re_schedule: [],
      return_to_courier: [],
      delivered: [],
    });
    expect(appel().url).toBe(BASE + 'deliveryman/dashboard');
    expect(appel().method).toBe('GET');
  });

  it("fetchParcelDetails renomme parcelEvents et customs_alerts, `[]` pour un serveur d'avant S95", async () => {
    const parcel = { id: 9, tracking_id: 'BL-9' };
    const event = { id: 1, parcel_id: 9, parcel_status: 7, note: null, created_at: null };
    fetchMock
      .mockResolvedValueOnce(ok({ parcel, parcelEvents: [event] }))
      .mockResolvedValueOnce(ok({ parcel, parcelEvents: [], customs_alerts: [{ id: 3 }] }))
      .mockResolvedValueOnce(ok(null));

    await expect(fetchParcelDetails(9)).resolves.toEqual({ parcel, events: [event], customsAlerts: [] });
    await expect(fetchParcelDetails(9)).resolves.toEqual({ parcel, events: [], customsAlerts: [{ id: 3 }] });
    await expect(fetchParcelDetails(9)).resolves.toEqual({ parcel: null, events: [], customsAlerts: [] });
    expect(appel().url).toBe(BASE + 'deliveryman/parcel/details/9');
  });

  it('fetchProfile rend le profil tel que servi', async () => {
    const profil = { user: { id: 4 }, current_balance: 15000, deliveryman_earn: '3500.00', total_cod: 0 };
    fetchMock.mockResolvedValueOnce(ok(profil));
    await expect(fetchProfile()).resolves.toEqual(profil);
    expect(appel().url).toBe(BASE + 'deliveryman/profile');
  });

  it('fetchIncomeExpense garde revenus et dépenses séparés, vides par défaut', async () => {
    const revenu = { id: 1, amount: 500 };
    fetchMock.mockResolvedValueOnce(ok({ income: [revenu], deliveryInfo: { total: 500 } })).mockResolvedValueOnce(ok(null));

    await expect(fetchIncomeExpense()).resolves.toEqual({ income: [revenu], expense: [], deliveryInfo: { total: 500 } });
    await expect(fetchIncomeExpense()).resolves.toEqual({ income: [], expense: [], deliveryInfo: undefined });
    expect(appel().url).toBe(BASE + 'deliveryman/income-expense');
  });

  it('fetchParcelPaymentLogs lit parcel_payment_logs', async () => {
    const log = { id: 2, type: 1, amount: 12500, date: '2026-10-01', note: null, created_at: null };
    fetchMock.mockResolvedValueOnce(ok({ parcel_payment_logs: [log] })).mockResolvedValueOnce(ok({}));

    await expect(fetchParcelPaymentLogs()).resolves.toEqual([log]);
    await expect(fetchParcelPaymentLogs()).resolves.toEqual([]);
    expect(appel().url).toBe(BASE + 'deliveryman/parcel-payment-logs');
  });
});

describe('position', () => {
  it('updateLocation envoie lat et long sur parcel-location-update', async () => {
    fetchMock.mockResolvedValueOnce(ok([]));
    await updateLocation(6.3654, 2.4183);
    expect(appel().url).toBe(BASE + 'deliveryman/parcel-location-update');
    expect(appel().method).toBe('POST');
    expect(appel().json()).toEqual({ lat: 6.3654, long: 2.4183 });
  });
});
