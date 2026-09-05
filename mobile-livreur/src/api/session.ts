/**
 * Jeton Sanctum du livreur connecté.
 *
 * Sur **iOS et Android** le jeton vit dans expo-secure-store (Keychain /
 * Keystore). Sur le **web** (vérification rapide en développement seulement),
 * repli sur `localStorage`, sans protection comparable — jamais une cible de
 * production.
 *
 * Depuis S5 (2026-09-04), le backend émet des jetons portant l'ability
 * `deliveryman` et refuse (403) les routes marchand : un jeton livreur n'ouvre
 * que l'espace livreur. Voir web/CARTOGRAPHIE.md.
 */
import { Platform } from 'react-native';
import * as SecureStore from 'expo-secure-store';

const TOKEN_KEY = 'beninlink.livreur.token';

const isWeb = Platform.OS === 'web';

const storage = {
  async get(key: string): Promise<string | null> {
    if (isWeb) {
      try {
        return globalThis.localStorage?.getItem(key) ?? null;
      } catch {
        return null;
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
