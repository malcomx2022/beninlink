/**
 * Jeton de session (`session.ts`) — où il vit, quand il est relu, qui apprend sa chute.
 *
 * Garde : le jeton est rangé dans expo-secure-store (Keychain / Keystore) sous sa
 * clé, relu une seule fois puis gardé en mémoire ; `clearToken()` l'efface partout
 * et prévient les écouteurs `onTokenCleared` (S144), qu'on peut retirer. Sur le
 * web, le stockage retombe sur `localStorage` et ne plante pas s'il manque.
 */
import * as SecureStore from 'expo-secure-store';

import { clearToken, getToken, onTokenCleared, setToken, storage } from './session';

jest.mock('expo-secure-store', () => ({
  getItemAsync: jest.fn(),
  setItemAsync: jest.fn(),
  deleteItemAsync: jest.fn(),
}));

const TOKEN_KEY = 'beninlink.merchant.token';

beforeEach(async () => {
  jest.resetAllMocks();
  jest.mocked(SecureStore.getItemAsync).mockResolvedValue(null);
  jest.mocked(SecureStore.setItemAsync).mockResolvedValue(undefined);
  jest.mocked(SecureStore.deleteItemAsync).mockResolvedValue(undefined);
  await clearToken(); // vide le cache du module
  jest.mocked(SecureStore.deleteItemAsync).mockClear();
});

describe('jeton', () => {
  it('relit le jeton du stockage sécurisé une seule fois, puis le garde en mémoire', async () => {
    jest.mocked(SecureStore.getItemAsync).mockResolvedValue('jeton-stocke');

    await expect(getToken()).resolves.toBe('jeton-stocke');
    await expect(getToken()).resolves.toBe('jeton-stocke');

    expect(SecureStore.getItemAsync).toHaveBeenCalledTimes(1);
    expect(SecureStore.getItemAsync).toHaveBeenCalledWith(TOKEN_KEY);
  });

  it("relit le stockage tant qu'aucun jeton n'y est (un démarrage déconnecté n'est pas figé)", async () => {
    jest.mocked(SecureStore.getItemAsync).mockResolvedValueOnce(null).mockResolvedValueOnce('jeton-arrive');

    await expect(getToken()).resolves.toBeNull();
    await expect(getToken()).resolves.toBe('jeton-arrive');
  });

  it('setToken écrit dans le stockage sécurisé et sert le jeton sans relecture', async () => {
    await setToken('jeton-neuf');

    expect(SecureStore.setItemAsync).toHaveBeenCalledWith(TOKEN_KEY, 'jeton-neuf');
    await expect(getToken()).resolves.toBe('jeton-neuf');
    expect(SecureStore.getItemAsync).not.toHaveBeenCalled();
  });

  it('clearToken efface le stockage et le cache', async () => {
    await setToken('jeton-neuf');
    await clearToken();

    expect(SecureStore.deleteItemAsync).toHaveBeenCalledWith(TOKEN_KEY);
    await expect(getToken()).resolves.toBeNull();
    expect(SecureStore.getItemAsync).toHaveBeenCalledTimes(1); // relu, pas servi du cache
  });
});

describe('onTokenCleared (S144)', () => {
  it('prévient chaque écouteur, une fois le stockage effacé', async () => {
    const ordre: string[] = [];
    jest.mocked(SecureStore.deleteItemAsync).mockImplementation(async () => {
      ordre.push('effacé');
    });
    const a = jest.fn(() => ordre.push('a'));
    const b = jest.fn(() => ordre.push('b'));
    const retirerA = onTokenCleared(a);
    const retirerB = onTokenCleared(b);

    await clearToken();
    retirerA();
    retirerB();

    expect(ordre).toEqual(['effacé', 'a', 'b']);
  });

  it("un écouteur retiré n'est plus appelé, les autres le restent", async () => {
    const garde = jest.fn();
    const retire = jest.fn();
    const retirerGarde = onTokenCleared(garde);
    const retirer = onTokenCleared(retire);

    retirer();
    await clearToken();
    retirerGarde();
    await clearToken();

    expect(retire).not.toHaveBeenCalled();
    expect(garde).toHaveBeenCalledTimes(1);
  });

  it("retirer deux fois le même écouteur ne casse rien", async () => {
    const ecouteur = jest.fn();
    const retirer = onTokenCleared(ecouteur);
    retirer();
    expect(() => retirer()).not.toThrow();
    await clearToken();
    expect(ecouteur).not.toHaveBeenCalled();
  });
});

describe('storage', () => {
  it('passe par expo-secure-store sur mobile', async () => {
    jest.mocked(SecureStore.getItemAsync).mockResolvedValueOnce('valeur');

    await expect(storage.get('cle')).resolves.toBe('valeur');
    await storage.set('cle', 'v2');
    await storage.remove('cle');

    expect(SecureStore.getItemAsync).toHaveBeenCalledWith('cle');
    expect(SecureStore.setItemAsync).toHaveBeenCalledWith('cle', 'v2');
    expect(SecureStore.deleteItemAsync).toHaveBeenCalledWith('cle');
  });

  describe('sur le web', () => {
    const original = Object.getOwnPropertyDescriptor(globalThis, 'localStorage');
    let web: typeof import('./session');

    beforeEach(() => {
      // Registre isolé : son `react-native` est une copie, la modifier ne touche pas les autres tests.
      jest.isolateModules(() => {
        const { Platform } = jest.requireActual<typeof import('react-native')>('react-native');
        Object.defineProperty(Platform, 'OS', { configurable: true, get: () => 'web' });
        web = jest.requireActual<typeof import('./session')>('./session');
      });
    });

    afterEach(() => {
      if (original) Object.defineProperty(globalThis, 'localStorage', original);
      else delete (globalThis as { localStorage?: unknown }).localStorage;
    });

    it('retombe sur localStorage, sans toucher au stockage sécurisé', async () => {
      const memoire = new Map<string, string>();
      Object.defineProperty(globalThis, 'localStorage', {
        configurable: true,
        value: {
          getItem: (k: string) => memoire.get(k) ?? null,
          setItem: (k: string, v: string) => void memoire.set(k, v),
          removeItem: (k: string) => void memoire.delete(k),
        },
      });

      await web.storage.set('cle', 'valeur');
      await expect(web.storage.get('cle')).resolves.toBe('valeur');
      await web.storage.remove('cle');
      await expect(web.storage.get('cle')).resolves.toBeNull();
      expect(SecureStore.setItemAsync).not.toHaveBeenCalled();
    });

    it('ne plante pas quand localStorage refuse (navigation privée)', async () => {
      const refus = () => {
        throw new Error('SecurityError');
      };
      Object.defineProperty(globalThis, 'localStorage', {
        configurable: true,
        value: { getItem: refus, setItem: refus, removeItem: refus },
      });

      await expect(web.storage.get('cle')).resolves.toBeNull();
      await expect(web.storage.set('cle', 'v')).resolves.toBeUndefined();
      await expect(web.storage.remove('cle')).resolves.toBeUndefined();
    });
  });
});
