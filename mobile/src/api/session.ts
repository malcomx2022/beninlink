/**
 * Jeton Sanctum du marchand connecté.
 *
 * Conservé dans expo-secure-store (Keychain iOS / Keystore Android) et jamais
 * dans AsyncStorage : c'est un jeton d'accès à des données financières.
 *
 * ⚠️ Le backend délivre des jetons **sans `abilities`** et n'a aucune garde
 * `user_type` (constat S5) : le même jeton ouvre les routes marchand et livreur.
 * L'app ne doit pas s'appuyer là-dessus — chaque écran appelle l'endpoint marchand
 * qui lui correspond.
 */
import * as SecureStore from 'expo-secure-store';

const TOKEN_KEY = 'beninlink.merchant.token';

let cachedToken: string | null = null;

export async function getToken(): Promise<string | null> {
  if (cachedToken !== null) return cachedToken;
  cachedToken = await SecureStore.getItemAsync(TOKEN_KEY);
  return cachedToken;
}

export async function setToken(token: string): Promise<void> {
  cachedToken = token;
  await SecureStore.setItemAsync(TOKEN_KEY, token);
}

export async function clearToken(): Promise<void> {
  cachedToken = null;
  await SecureStore.deleteItemAsync(TOKEN_KEY);
}
