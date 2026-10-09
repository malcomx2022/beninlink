/**
 * Compte marchand (`merchant.ts`) — relevés, barème, profil et mot de passe.
 *
 * Garde : le relevé en cours arrive en objet nu et passe tel quel ; les relevés
 * émis se lisent page par page (S78) et un `data` qui n'est pas un tableau ne
 * plante pas l'écran ; le barème rend `zones` et `delays` vides quand le serveur
 * ne les envoie pas (D4) ; le lien PDF est l'URL signée ; le changement de mot de
 * passe envoie l'ancien, le nouveau et sa confirmation (S134).
 */
import * as SecureStore from 'expo-secure-store';

import {
  INVOICES_PER_PAGE,
  fetchBalanceDetails,
  fetchCodCharges,
  fetchDeliveryGrid,
  fetchInvoicePdfLink,
  fetchInvoices,
  updatePassword,
  updateProfile,
} from './merchant';
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

describe('relevés', () => {
  it('passe le relevé en cours tel quel (objet nu, sans `data`)', async () => {
    const releve = { cod_collected: 50000, total_charges: 4000, vat: 720, net_payable: 45280 };
    fetchMock.mockResolvedValueOnce(jsonResponse(200, releve));
    await expect(fetchBalanceDetails()).resolves.toEqual(releve);
    expect(singleCall(fetchMock).path).toBe('dashboard/balance-details');
  });

  it('lit une page de relevés émis et `page` pour la suite', async () => {
    fetchMock.mockResolvedValueOnce(ok([{ id: 1 }, { id: 2 }], { page: { current: 1, per_page: 10, last: 4, total: 32 } }));

    await expect(fetchInvoices()).resolves.toEqual({ items: [{ id: 1 }, { id: 2 }], hasMore: true });
    expect(singleCall(fetchMock).path).toBe('invoice-list/index?page=1');
  });

  it("rend une liste vide quand `data` n'est pas un tableau ; repli sur la page pleine sans `page`", async () => {
    const pleine = Array.from({ length: INVOICES_PER_PAGE }, (_, i) => ({ id: i }));
    fetchMock.mockResolvedValueOnce(ok({ invoices: [] })).mockResolvedValueOnce(ok(pleine));

    await expect(fetchInvoices(2)).resolves.toEqual({ items: [], hasMore: false });
    expect((await fetchInvoices(3)).hasMore).toBe(true);
  });

  it("rend l'URL signée du PDF", async () => {
    fetchMock.mockResolvedValueOnce(ok({ url: 'https://example.test/pdf?signature=x', expires_at: '2026-10-09T10:15:00Z' }));
    await expect(fetchInvoicePdfLink(12)).resolves.toBe('https://example.test/pdf?signature=x');
    expect(singleCall(fetchMock).path).toBe('invoice-pdf-link/12');
  });
});

describe('barème (D4)', () => {
  it('rend tarifs, zones et délais servis par le serveur', async () => {
    fetchMock.mockResolvedValueOnce(
      ok({ deliveryCharges: [{ id: 1 }], zones: [{ id: 2, name: 'Cotonou' }], delays: [{ id: 3 }] }),
    );

    await expect(fetchDeliveryGrid()).resolves.toEqual({
      rates: [{ id: 1 }],
      zones: [{ id: 2, name: 'Cotonou' }],
      delays: [{ id: 3 }],
    });
    expect(singleCall(fetchMock).path).toBe('settings/delivery-charges');
  });

  it("rend des tableaux vides quand un serveur ancien n'envoie pas les clés", async () => {
    fetchMock.mockResolvedValueOnce(ok({})).mockResolvedValueOnce(ok({}));
    await expect(fetchDeliveryGrid()).resolves.toEqual({ rates: [], zones: [], delays: [] });
    await expect(fetchCodCharges()).resolves.toEqual([]);
  });
});

describe('profil', () => {
  it('envoie le profil en POST sur profile/update', async () => {
    fetchMock.mockResolvedValueOnce(ok([]));
    const profil = { name: 'Awa', email: 'awa@boutique.bj', mobile: '0196000001', address: 'Cotonou', business_name: 'Boutique Awa' };

    await updateProfile(profil as Parameters<typeof updateProfile>[0]);

    const call = singleCall(fetchMock);
    expect(call.method).toBe('POST');
    expect(call.path).toBe('profile/update');
    expect(call.body).toEqual(profil);
  });

  it('change le mot de passe par PUT, nouveau mot de passe recopié en confirmation', async () => {
    fetchMock.mockResolvedValueOnce(ok([]));

    await updatePassword('ancien-mdp', 'nouveau-mdp');

    const call = singleCall(fetchMock);
    expect(call.method).toBe('PUT');
    expect(call.path).toBe('update-password');
    expect(call.body).toEqual({ old_password: 'ancien-mdp', new_password: 'nouveau-mdp', confirm_password: 'nouveau-mdp' });
    expect(call.headers.Authorization).toBe('Bearer jeton-marchand');
  });
});
