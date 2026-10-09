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
 * Depuis S5 (2026-09-04), le jeton émis par `/signin` porte l'ability `merchant`
 * et les routes livreur répondent 403 : un jeton marchand n'ouvre que l'espace
 * marchand.
 */
import { Platform } from 'react-native';
import * as SecureStore from 'expo-secure-store';

const TOKEN_KEY = 'beninlink.merchant.token';

const isWeb = Platform.OS === 'web';

export const storage = {
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

/**
 * S144 — qui doit savoir que le jeton est tombé. Le client d'API efface le jeton sur un 401 ; depuis S135 et S136
 * le serveur en révoque en cours de session (mot de passe changé ailleurs, réinitialisé, rafraîchi). Sans écouteur,
 * la session gardait son compte en mémoire : l'app restait sur des écrans connectés dont chaque appel échouait,
 * jusqu'au redémarrage. `SessionProvider` s'abonne et revient à l'écran de connexion.
 */
type Ecouteur = () => void;
const ecouteurs = new Set<Ecouteur>();

export function onTokenCleared(ecouteur: Ecouteur): () => void {
  ecouteurs.add(ecouteur);
  return () => {
    ecouteurs.delete(ecouteur);
  };
}

export async function clearToken(): Promise<void> {
  cachedToken = null;
  await storage.remove(TOKEN_KEY);
  ecouteurs.forEach((ecouteur) => ecouteur());
}
