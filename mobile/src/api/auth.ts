/**
 * Authentification marchand.
 *
 * ⚠️ Le backend n'identifie pas par téléphone ni par e-mail, contrairement à ce
 * que suggère la maquette : `POST /signin` attend **`merchant_id`**, qui est le
 * `users.unique_id` (l'identifiant marchand, ex. « 2024 »). Le libellé de l'écran
 * doit le refléter, sans quoi les PME pilotes saisiront leur numéro de téléphone
 * et se verront refuser la connexion sans comprendre pourquoi.
 *
 * Le backend refuse déjà (401) un compte dont `user_type` n'est pas MERCHANT.
 */
import { api } from './client';
import { endpoints } from './endpoints';
import { clearToken, setToken } from './session';
import type { AuthUser, SignInResult } from './types';

export async function signIn(merchantId: string, password: string): Promise<AuthUser> {
  const result = await api.post<SignInResult>(
    endpoints.signin,
    { merchant_id: merchantId.trim(), password },
    { authenticated: false },
  );
  await setToken(result.token);
  return result.user;
}

/**
 * Déconnexion. Le jeton local est effacé **même si l'appel échoue** : sans
 * réseau, l'utilisateur doit malgré tout pouvoir quitter sa session.
 */
export async function signOut(): Promise<void> {
  try {
    await api.post(endpoints.signOut);
  } catch {
    // silencieux : la session locale prime
  } finally {
    await clearToken();
  }
}

/** Demande d'un lien / code de réinitialisation. */
export async function requestPasswordReset(email: string): Promise<string> {
  const res = await api.post<{ message?: string }>(
    endpoints.passwordEmail,
    { email: email.trim() },
    { authenticated: false },
  );
  return res?.message ?? '';
}
