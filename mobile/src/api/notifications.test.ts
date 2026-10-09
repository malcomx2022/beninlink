/**
 * Fil de notifications (`notifications.ts`) et abonnement de l'appareil (`push.ts`, D11).
 *
 * Garde : le fil se lit page par page avec son compteur de non-lues et dit s'il
 * en reste (S78, sinon page pleine de 20) ; « lu » et « tout lu » sont des PUT ;
 * l'appareil s'abonne en ne donnant que son jeton de push, sa plateforme et
 * l'app « merchant » — aucune clé ni identifiant de compte.
 */
import * as SecureStore from 'expo-secure-store';

import {
  NOTIFICATIONS_PER_PAGE,
  fetchNotifications,
  fetchUnreadCount,
  markAllNotificationsRead,
  markNotificationRead,
} from './notifications';
import { forgetDevice, registerDevice } from './push';
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

describe('fil', () => {
  it('lit une page, le compteur de non-lues, et `page` pour la suite', async () => {
    fetchMock.mockResolvedValueOnce(
      ok({ notifications: [{ id: 'a' }], unread_count: 4 }, { page: { current: 1, per_page: 20, last: 2, total: 21 } }),
    );

    await expect(fetchNotifications()).resolves.toEqual({
      notifications: [{ id: 'a' }],
      unread_count: 4,
      hasMore: true,
    });
    expect(singleCall(fetchMock).path).toBe('notifications/index?page=1');
  });

  it("sans `page`, une page pleine de 20 annonce une suite ; les clés absentes valent zéro", async () => {
    const pleine = Array.from({ length: NOTIFICATIONS_PER_PAGE }, (_, i) => ({ id: String(i) }));
    fetchMock.mockResolvedValueOnce(ok({ notifications: pleine, unread_count: 0 })).mockResolvedValueOnce(ok({}));

    expect((await fetchNotifications(2)).hasMore).toBe(true);
    await expect(fetchNotifications(3)).resolves.toEqual({ notifications: [], unread_count: 0, hasMore: false });
    expect(fetchCalls(fetchMock).map((c) => c.path)).toEqual(['notifications/index?page=2', 'notifications/index?page=3']);
  });

  it('lit le compteur seul, et zéro si le serveur ne le donne pas', async () => {
    fetchMock.mockResolvedValueOnce(ok({ unread_count: 7 })).mockResolvedValueOnce(ok([]));

    await expect(fetchUnreadCount()).resolves.toBe(7);
    await expect(fetchUnreadCount()).resolves.toBe(0);
    expect(fetchMock.mock.calls[0]![0]).toBe('https://example.test/api/v10/notifications/unread-count');
  });

  it('marque une notification puis toutes comme lues, par PUT', async () => {
    fetchMock.mockResolvedValue(ok([]));

    await markNotificationRead('9f1c');
    await markAllNotificationsRead();

    expect(fetchCalls(fetchMock).map((c) => `${c.method} ${c.path}`)).toEqual([
      'PUT notifications/9f1c/read',
      'PUT notifications/read-all',
    ]);
  });
});

describe('appareil (D11)', () => {
  it("s'abonne avec le seul jeton de push, la plateforme et l'app marchand", async () => {
    fetchMock.mockResolvedValueOnce(ok([]));

    await registerDevice('ExponentPushToken[abc]', 'android');

    const call = singleCall(fetchMock);
    expect(call.method).toBe('POST');
    expect(call.path).toBe('push/register');
    expect(call.body).toEqual({ token: 'ExponentPushToken[abc]', platform: 'android', app: 'merchant' });
    expect(call.headers.Authorization).toBe('Bearer jeton-marchand');
  });

  it('se désabonne par le jeton de push', async () => {
    fetchMock.mockResolvedValueOnce(ok([]));
    await forgetDevice('ExponentPushToken[abc]');
    const call = singleCall(fetchMock);
    expect(call.path).toBe('push/forget');
    expect(call.body).toEqual({ token: 'ExponentPushToken[abc]' });
  });
});
