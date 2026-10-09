/**
 * Boutiques (`shops.ts`) — toutes les pages, et ce qu'une création ou une mise à jour envoie.
 *
 * Garde : la liste complète parcourt les pages jusqu'à ce que le serveur dise
 * « fini » (avant S78, le onzième magasin disparaissait) ; création et mise à
 * jour envoient `status: 1` exigé par le backend et le numéro tel que tapé (S133) ;
 * chaque opération vise la bonne route et la bonne méthode.
 */
import * as SecureStore from 'expo-secure-store';

import { setToken } from './session';
import { createShop, deleteShop, fetchShop, fetchShops, updateShop } from './shops';
import { fetchCalls, installFetch, ok, singleCall } from '../testing/fetchMock';

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

const boutiques = (debut: number, n: number) => Array.from({ length: n }, (_, i) => ({ id: debut + i }));

it('lit toutes les pages de boutiques, et s\'arrête à la dernière annoncée', async () => {
  fetchMock
    .mockResolvedValueOnce(ok({ shops: boutiques(1, 10) }, { page: { current: 1, per_page: 10, last: 2, total: 11 } }))
    .mockResolvedValueOnce(ok({ shops: boutiques(11, 1) }, { page: { current: 2, per_page: 10, last: 2, total: 11 } }));

  const toutes = await fetchShops();

  expect(toutes).toHaveLength(11);
  expect(toutes.at(-1)).toEqual({ id: 11 });
  expect(fetchCalls(fetchMock).map((c) => c.path)).toEqual(['shops/index?page=1', 'shops/index?page=2']);
});

it("sans `page`, continue tant qu'une page est pleine", async () => {
  fetchMock.mockResolvedValueOnce(ok({ shops: boutiques(1, 10) })).mockResolvedValueOnce(ok({ shops: boutiques(11, 3) }));

  await expect(fetchShops()).resolves.toHaveLength(13);
  expect(fetchMock).toHaveBeenCalledTimes(2);
});

it('crée une boutique active avec le numéro tel que tapé (S133)', async () => {
  fetchMock.mockResolvedValueOnce(ok([]));

  await createShop({ name: 'Boutique Awa', contact_no: '+229 01 97 00 00 00', address: 'Cotonou' });

  const call = singleCall(fetchMock);
  expect(call.method).toBe('POST');
  expect(call.path).toBe('shops/store');
  expect(call.body).toEqual({ name: 'Boutique Awa', contact_no: '+229 01 97 00 00 00', address: 'Cotonou', status: 1 });
});

it('met à jour par PUT sur la boutique, en la gardant active', async () => {
  fetchMock.mockResolvedValueOnce(ok([]));

  await updateShop(3, { name: 'Dépôt', contact_no: '0197000000', address: 'Porto-Novo' });

  const call = singleCall(fetchMock);
  expect(call.method).toBe('PUT');
  expect(call.path).toBe('shops/update/3');
  expect(call.body).toEqual({ name: 'Dépôt', contact_no: '0197000000', address: 'Porto-Novo', status: 1 });
});

it('lit une boutique sous `shop` et supprime par DELETE', async () => {
  fetchMock.mockResolvedValueOnce(ok({ shop: { id: 3, name: 'Dépôt' } })).mockResolvedValueOnce(ok([]));

  await expect(fetchShop(3)).resolves.toEqual({ id: 3, name: 'Dépôt' });
  await deleteShop(3);

  const [lecture, suppression] = fetchCalls(fetchMock);
  expect(lecture!.path).toBe('shops/edit/3');
  expect(suppression!.method).toBe('DELETE');
  expect(suppression!.path).toBe('shops/delete/3');
});
