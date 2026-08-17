/**
 * Jeton Sanctum du marchand connecté.
 *
 * Sur **iOS et Android** — les cibles réellement livrées aux PME — le jeton vit
 * dans expo-secure-store (Keychain / Keystore), jamais dans AsyncStorage : c'est
 * un jeton d'accès à des données financières.
 *
 * Sur le **web**, expo-secure-store n'existe pas (pas d'implémentation : l'appel
 * échoue avec `getValueWithKeyAsync is not a function`). On retombe sur
 * `localStorage`, qui n'offre aucune protection comparable — acceptable parce que
 * la cible web ne sert qu'à la vérification rapide en développement, jamais à un
 * usage marchand. Si le web devenait une cible de production, il faudrait un
 * cookie httpOnly posé par le serveur, pas un jeton en JavaScript.
 *
 * ⚠️ Le backend délivre des jetons **sans `abilities`** et n'a aucune garde
 * `user_type` sur les routes (constat S5) : `/signin` refuse bien un non-marchand,
 * mais le jeton obtenu ouvre aussi les routes livreur. L'app ne s'appuie pas
 * là-dessus.
 */
import { Platform } from 'react-native';
import * as SecureStore from 'expo-secure-store';

const TOKEN_KEY = 'beninlink.merchant.token';

const isWeb = Platform.OS === 'web';

const storage = {
  async get(key: string): Promise<string | null> {
    if (isWeb) {
      try {
        return globalThis.localStorage?.getItem(key) ?? null;
      } catch {
        return null; // localStorage indisponible (navigation privée, iframe…)
      }
    }
    return SecureStore.getItemAsync(key);
  },
  async set(key: string, value: string): Promise<void> {
    if (isWeb) {
      try {
        globalThis.localStorage?.setItem(key, value);
      } catch {
        /* session non persistée : l'app reste utilisable jusqu'au rechargement */
      }
      return;
    }
    await SecureStore.setItemAsync(key, value);
  },
  async remove(key: string): Promise<void> {
    if (isWeb) {
      try {
        globalThis.localStorage?.removeItem(key);
      } catch {
        /* rien à faire */
      }
      return;
    }
    await SecureStore.deleteItemAsync(key);
  },
};

let cachedToken: string | null = null;

export async function getToken(): Promise<string | null> {
  if (cachedToken !== null) return cachedToken;
  cachedToken = await storage.get(TOKEN_KEY);
  return cachedToken;
}

export async function setToken(token: string): Promise<void> {
  cachedToken = token;
  await storage.set(TOKEN_KEY, token);
}

export async function clearToken(): Promise<void> {
  cachedToken = null;
  await storage.remove(TOKEN_KEY);
}
