/**
 * Le jeton Sanctum du livreur : stocké dans expo-secure-store (Keychain / Keystore), gardé en cache
 * après la première lecture, et effacé avec prévenance — `clearToken()` appelle chaque écouteur
 * `onTokenCleared` (S144, c'est ainsi que `SessionProvider` revient à la connexion), et un écouteur
 * retiré n'est plus appelé.
 */
import * as SecureStore from 'expo-secure-store';

import { clearToken, getToken, onTokenCleared, setToken } from './session';

jest.mock('expo-secure-store', () => ({
  getItemAsync: jest.fn(),
  setItemAsync: jest.fn(),
  deleteItemAsync: jest.fn(),
}));

const CLE = 'beninlink.livreur.token';

beforeEach(async () => {
  jest.resetAllMocks();
  jest.mocked(SecureStore.getItemAsync).mockResolvedValue(null);
  jest.mocked(SecureStore.setItemAsync).mockResolvedValue(undefined);
  jest.mocked(SecureStore.deleteItemAsync).mockResolvedValue(undefined);
  await clearToken(); // cache vide au départ de chaque scénario
  jest.clearAllMocks();
});

describe('session du livreur', () => {
  it('lit le jeton une seule fois puis le sert depuis le cache', async () => {
    jest.mocked(SecureStore.getItemAsync).mockResolvedValue('jeton-stocke');

    await expect(getToken()).resolves.toBe('jeton-stocke');
    await expect(getToken()).resolves.toBe('jeton-stocke');

    expect(SecureStore.getItemAsync).toHaveBeenCalledTimes(1);
    expect(SecureStore.getItemAsync).toHaveBeenCalledWith(CLE);
  });

  it('relit le stockage tant que rien n’y est enregistré', async () => {
    await expect(getToken()).resolves.toBeNull();
    await expect(getToken()).resolves.toBeNull();
    expect(SecureStore.getItemAsync).toHaveBeenCalledTimes(2);
  });

  it('enregistre le jeton et le sert sans relire le stockage', async () => {
    await setToken('jeton-neuf');

    expect(SecureStore.setItemAsync).toHaveBeenCalledWith(CLE, 'jeton-neuf');
    await expect(getToken()).resolves.toBe('jeton-neuf');
    expect(SecureStore.getItemAsync).not.toHaveBeenCalled();
  });

  it('efface le jeton du cache et du stockage', async () => {
    await setToken('jeton-a-effacer');

    await clearToken();

    expect(SecureStore.deleteItemAsync).toHaveBeenCalledWith(CLE);
    await expect(getToken()).resolves.toBeNull();
    expect(SecureStore.getItemAsync).toHaveBeenCalledTimes(1);
  });

  it('prévient chaque écouteur quand le jeton tombe', async () => {
    const premier = jest.fn();
    const second = jest.fn();
    const retirerPremier = onTokenCleared(premier);
    const retirerSecond = onTokenCleared(second);

    await clearToken();
    retirerPremier();
    retirerSecond();

    expect(premier).toHaveBeenCalledTimes(1);
    expect(second).toHaveBeenCalledTimes(1);
  });

  it('prévient les écouteurs après avoir vidé le cache', async () => {
    await setToken('jeton-revoque');
    let lecture: Promise<string | null> | undefined;
    const retirer = onTokenCleared(() => {
      lecture = getToken();
    });

    await clearToken();
    retirer();

    // L'écouteur ne revoit pas le jeton révoqué : il relit le stockage, vide.
    await expect(lecture).resolves.toBeNull();
    expect(SecureStore.getItemAsync).toHaveBeenCalledWith(CLE);
  });

  it("n'appelle plus un écouteur retiré, et garde les autres", async () => {
    const retire = jest.fn();
    const garde = jest.fn();
    const retirer = onTokenCleared(retire);
    const retirerGarde = onTokenCleared(garde);

    retirer();
    await clearToken();
    retirerGarde();

    expect(retire).not.toHaveBeenCalled();
    expect(garde).toHaveBeenCalledTimes(1);
  });

  it('enregistrer un jeton ne prévient personne', async () => {
    const ecouteur = jest.fn();
    const retirer = onTokenCleared(ecouteur);

    await setToken('jeton');
    retirer();

    expect(ecouteur).not.toHaveBeenCalled();
  });
});
