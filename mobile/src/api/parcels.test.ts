/**
 * Colis (`parcels.ts`) — lecture, suivi, devis et création, tels qu'ils partent au backend.
 *
 * Garde : la liste et le suivi tolèrent les clés absentes (serveur d'avant S82 :
 * pas de `customs_alerts`) ; le détail accepte l'objet enveloppé ou nu ; la
 * création n'envoie QUE les choix du marchand, aucun montant calculé (faille S2) ;
 * le devis transmet le signal d'abandon ; le refus pour solde insuffisant (422)
 * se reconnaît depuis une vraie `ApiError` et rend ce qui manque, en entiers XOF.
 */
import * as SecureStore from 'expo-secure-store';

import { ApiError } from './client';
import {
  createParcel,
  fetchParcelDetails,
  fetchParcelStatuses,
  fetchParcelTimeline,
  fetchParcels,
  fetchQuote,
  walletShortfall,
} from './parcels';
import { setToken } from './session';
import { installFetch, jsonResponse, ok, singleCall } from '../testing/fetchMock';

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
  jest.mocked(SecureStore.setItemAsync).mockResolvedValue(undefined);
  await setToken('jeton-marchand');
  fetchMock = installFetch();
});

describe('lecture', () => {
  it('lit la liste sous `parcels`, et une liste vide si la clé manque', async () => {
    fetchMock.mockResolvedValueOnce(ok({ parcels: [{ id: 1 }, { id: 2 }] })).mockResolvedValueOnce(ok({}));

    await expect(fetchParcels()).resolves.toEqual([{ id: 1 }, { id: 2 }]);
    await expect(fetchParcels()).resolves.toEqual([]);
    expect(fetchMock.mock.calls[0]![0]).toBe('https://example.test/api/v10/parcel/index');
  });

  it('lit les statuts traduits du backend, servis en tableau nu', async () => {
    fetchMock.mockResolvedValueOnce(jsonResponse(200, [{ key: 1, value: 'En attente' }]));
    await expect(fetchParcelStatuses()).resolves.toEqual([{ key: 1, value: 'En attente' }]);
    expect(singleCall(fetchMock).path).toBe('parcel/all/status');
  });

  it('accepte le détail enveloppé sous `parcel` comme nu', async () => {
    fetchMock.mockResolvedValueOnce(ok({ parcel: { id: 7, tracking_id: 'BL-7' } }))
      .mockResolvedValueOnce(ok({ id: 8, tracking_id: 'BL-8' }));

    await expect(fetchParcelDetails(7)).resolves.toEqual({ id: 7, tracking_id: 'BL-7' });
    await expect(fetchParcelDetails(8)).resolves.toEqual({ id: 8, tracking_id: 'BL-8' });
    expect(fetchMock.mock.calls[0]![0]).toBe('https://example.test/api/v10/parcel/details/7');
  });

  it('rend le suivi avec les alertes douanières DU colis (S82)', async () => {
    fetchMock.mockResolvedValueOnce(
      ok({ parcel: { id: 7 }, parcelEvents: [{ id: 1 }], customs_alerts: [{ id: 3, level: 'BLOQUANT' }] }),
    );

    await expect(fetchParcelTimeline(7)).resolves.toEqual({
      parcel: { id: 7 },
      events: [{ id: 1 }],
      customsAlerts: [{ id: 3, level: 'BLOQUANT' }],
    });
    expect(singleCall(fetchMock).path).toBe('parcel/logs/7');
  });

  it("tolère un serveur d'avant S82 : ni alertes ni événements ne font planter", async () => {
    fetchMock.mockResolvedValueOnce(ok({}));
    await expect(fetchParcelTimeline(7)).resolves.toEqual({ parcel: null, events: [], customsAlerts: [] });
  });
});

describe('création et devis', () => {
  const choix = {
    shop_id: 1,
    category_id: 2,
    delivery_type_id: 1,
    customer_name: 'Awa Zinsou',
    customer_phone: '0196000002',
    customer_address: 'Fidjrossè',
    cash_collection: 12000,
    zone_id: 3,
  };

  it("envoie les seuls choix du marchand, sans montant calculé (S2)", async () => {
    fetchMock.mockResolvedValueOnce(ok({ parcel: { id: 9 } }));

    await createParcel(choix);

    const call = singleCall(fetchMock);
    expect(call.method).toBe('POST');
    expect(call.path).toBe('parcel/store');
    expect(call.body).toEqual(choix);
    expect(call.body).not.toHaveProperty('chargeDetails');
    expect(call.headers.Authorization).toBe('Bearer jeton-marchand');
  });

  it('demande le devis en POST et transmet le signal pour abandonner un devis obsolète', async () => {
    const devis = { delivery_charge: 1000, total_payable_charges: 1322, current_payable: 10678 };
    fetchMock.mockResolvedValueOnce(ok(devis));
    const controller = new AbortController();

    await expect(fetchQuote({ category_id: 2, delivery_type_id: 1, cash_collection: 12000 }, controller.signal))
      .resolves.toEqual(devis);

    const call = singleCall(fetchMock);
    expect(call.method).toBe('POST');
    expect(call.path).toBe('parcel/quote');
    expect(call.body).toEqual({ category_id: 2, delivery_type_id: 1, cash_collection: 12000 });
    expect(fetchMock.mock.calls[0]![1].signal).toBeInstanceOf(AbortSignal);
  });
});

describe('walletShortfall', () => {
  async function refus(body: unknown, status = 422): Promise<unknown> {
    fetchMock.mockResolvedValueOnce(jsonResponse(status, body));
    try {
      await createParcel({ ...({} as Parameters<typeof createParcel>[0]) });
    } catch (e) {
      return e;
    }
    throw new Error('La création aurait dû échouer.');
  }

  it('rend ce qui manque au porte-monnaie depuis le 422 du serveur', async () => {
    const e = await refus({
      success: false,
      message: 'Solde insuffisant.',
      data: { required: 1500, wallet_balance: 500, missing: 1000 },
    });

    expect(e).toBeInstanceOf(ApiError);
    expect(walletShortfall(e)).toEqual({ required: 1500, available: 500, missing: 1000 });
  });

  it('rend null pour un 422 de validation, un autre statut, ou des montants non numériques', async () => {
    const validation = await refus({ success: false, message: 'Invalide.', data: { message: { shop_id: ['Requis.'] } } });
    const autreStatut = await refus({ success: false, data: { required: 1500, wallet_balance: 500, missing: 1000 } }, 400);
    const chaines = await refus({ success: false, data: { required: '1500', wallet_balance: 500, missing: 1000 } });

    expect(walletShortfall(validation)).toBeNull();
    expect(walletShortfall(autreStatut)).toBeNull();
    expect(walletShortfall(chaines)).toBeNull();
    expect(walletShortfall(new Error('autre'))).toBeNull();
  });
});
