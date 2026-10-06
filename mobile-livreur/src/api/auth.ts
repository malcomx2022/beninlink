/**
 * Session livreur.
 *
 * `POST deliveryman/login` attend **`driver_id`** — le `users.unique_id` du
 * livreur, attribué par le transporteur (ex. « 20241 ») — et non le téléphone.
 * Le backend refuse (401) un compte qui n'est pas de type livreur, et le jeton
 * émis porte l'ability `deliveryman` (S5).
 */
import { api } from './client';
import { endpoints } from './endpoints';
import { clearToken, setToken } from './session';
import type { DeliverymanUser, SignInResult } from './types';

export async function signIn(driverId: string, password: string): Promise<DeliverymanUser> {
  const result = await api.post<SignInResult>(
    endpoints.login,
    { driver_id: driverId.trim(), password },
    { authenticated: false },
  );
  await setToken(result.token);
  return result.user;
}

/**
 * Déconnexion. Le jeton local est effacé **même si l'appel échoue** : sans
 * réseau, le livreur doit pouvoir quitter sa session.
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

export async function updatePassword(oldPassword: string, newPassword: string): Promise<void> {
  await api.put(endpoints.updatePassword, {
    old_password: oldPassword,
    new_password: newPassword,
    confirm_password: newPassword,
  });
}

/**
 * S98 — mot de passe oublié, première étape : demander le lien.
 * Le backend envoie un e-mail au compte (`Password::sendResetLink`) ; la
 * réponse ne dit jamais si l'adresse existe (le serveur répond pareil).
 */
export async function requestPasswordReset(email: string): Promise<string> {
  const res = await api.post<{ message?: string }>(
    endpoints.passwordEmail,
    { email: email.trim() },
    { authenticated: false },
  );
  return res?.message ?? '';
}

export type ResetPasswordPayload = {
  /** Jeton porté par le lien reçu par e-mail (`password/reset/{token}`). */
  token: string;
  email: string;
  password: string;
  password_confirmation: string;
};

/** S98 — seconde étape : le nouveau mot de passe (8 caractères au moins, confirmé — règle du backend). */
export async function resetPassword(payload: ResetPasswordPayload): Promise<string> {
  const res = await api.post<{ message?: string }>(
    endpoints.passwordReset,
    { ...payload, email: payload.email.trim() },
    { authenticated: false },
  );
  return res?.message ?? '';
}
