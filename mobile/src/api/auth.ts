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

export type SignUpPayload = {
  business_name: string;
  full_name: string;
  address: string;
  mobile: string;
  password: string;
  /** Identifiants légaux béninois — IFU et RCCM exigés par le backend. */
  ifu: string;
  rccm: string;
  /** Ne concerne que les entreprises ayant des salariés. */
  cnss?: string;
  /** Agence de rattachement, si l'utilisateur en choisit une. */
  hub_id?: number;
};

/**
 * Inscription d'une PME.
 *
 * Le backend **ne connecte pas** à l'issue de l'inscription : il envoie un code
 * par SMS et renvoie le numéro. Il faut ensuite `verifyOtp()` pour obtenir un
 * jeton. Ne pas prendre un `success` pour une session ouverte.
 */
export async function signUp(payload: SignUpPayload): Promise<string> {
  const res = await api.post<{ mobile?: string }>(
    endpoints.register,
    payload,
    { authenticated: false },
  );
  return res?.mobile ?? payload.mobile;
}

/** Vérifie le code SMS et ouvre la session. */
export async function verifyOtp(mobile: string, otp: string): Promise<AuthUser> {
  const result = await api.post<SignInResult>(
    endpoints.otpVerification,
    { mobile: mobile.trim(), otp: otp.trim() },
    { authenticated: false },
  );
  await setToken(result.token);
  return result.user;
}

export async function resendOtp(mobile: string): Promise<void> {
  await api.post(endpoints.resendOtp, { mobile }, { authenticated: false });
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

export type ResetPasswordPayload = {
  /** Jeton porté par le lien reçu par e-mail (`password/reset/{token}`). */
  token: string;
  email: string;
  password: string;
  password_confirmation: string;
};

/**
 * Définit le nouveau mot de passe à partir du jeton reçu.
 *
 * Le lien envoyé par `password/email` mène à la page web du backend ; l'app
 * accepte le même jeton, qu'il arrive par lien profond
 * (`beninlink://reset-password?token=…&email=…`) ou par saisie. Le backend
 * exige au moins 8 caractères et la confirmation.
 */
export async function resetPassword(payload: ResetPasswordPayload): Promise<string> {
  const res = await api.post<{ message?: string }>(
    endpoints.passwordReset,
    { ...payload, email: payload.email.trim() },
    { authenticated: false },
  );
  return res?.message ?? '';
}
