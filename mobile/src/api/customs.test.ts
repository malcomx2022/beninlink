/**
 * Alertes douanières (`customs.ts`) — la liste paginée et « Marquer traitée ».
 *
 * Garde : le filtre `status` n'est envoyé que s'il est choisi ; `hasMore` vient du
 * bloc `page` (S78) et, à défaut, d'une page pleine de 20 (S68 : une constante
 * fausse perdait des alertes en silence) ; la résolution est un PUT sur l'alerte.
 */
import * as SecureStore from 'expo-secure-store';

import {
  CUSTOMS_ALERTS_PER_PAGE,
  fetchCustomsAlerts,
  fetchCustomsReference,
  resolveCustomsAlert,
} from './customs';
import { setToken } from './session';
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

it("n'envoie le filtre de statut que s'il est choisi", async () => {
  fetchMock.mockResolvedValue(ok({ alerts: [] }));

  await fetchCustomsAlerts();
  await fetchCustomsAlerts(1, 3);

  expect(fetchCalls(fetchMock).map((c) => c.path)).toEqual(['customs/alerts?page=1', 'customs/alerts?status=1&page=3']);
});

it('lit `page` pour savoir si des alertes restent (S78)', async () => {
  fetchMock
    .mockResolvedValueOnce(ok({ alerts: [{ id: 1 }] }, { page: { current: 1, per_page: 20, last: 2, total: 21 } }))
    .mockResolvedValueOnce(ok({ alerts: [{ id: 2 }] }, { page: { current: 2, per_page: 20, last: 2, total: 21 } }));

  await expect(fetchCustomsAlerts()).resolves.toEqual({ items: [{ id: 1 }], hasMore: true });
  await expect(fetchCustomsAlerts(undefined, 2)).resolves.toEqual({ items: [{ id: 2 }], hasMore: false });
});

it("sans `page`, déduit la suite d'une page pleine de 20", async () => {
  expect(CUSTOMS_ALERTS_PER_PAGE).toBe(20);
  const pleine = Array.from({ length: 20 }, (_, i) => ({ id: i }));
  fetchMock.mockResolvedValueOnce(ok({ alerts: pleine })).mockResolvedValueOnce(ok({}));

  expect((await fetchCustomsAlerts()).hasMore).toBe(true);
  await expect(fetchCustomsAlerts()).resolves.toEqual({ items: [], hasMore: false });
});

it("marque une alerte traitée par un PUT et rend l'alerte mise à jour", async () => {
  fetchMock.mockResolvedValueOnce(ok({ alert: { id: 5, status: 2 } })).mockResolvedValueOnce(ok([]));

  await expect(resolveCustomsAlert(5)).resolves.toEqual({ id: 5, status: 2 });
  await expect(resolveCustomsAlert(6)).resolves.toBeNull();

  const [premier] = fetchCalls(fetchMock);
  expect(premier!.method).toBe('PUT');
  expect(premier!.path).toBe('customs/alerts/5/resolve');
  expect(premier!.headers.Authorization).toBe('Bearer jeton-marchand');
});

it('lit le référentiel des pays et catégories tel que le serveur le sert', async () => {
  const ref = { countries: [{ code: 'TG', name: 'Togo' }], categories: [] };
  fetchMock.mockResolvedValueOnce(ok(ref));
  await expect(fetchCustomsReference()).resolves.toEqual(ref);
  expect(singleCall(fetchMock).path).toBe('customs/reference');
});
