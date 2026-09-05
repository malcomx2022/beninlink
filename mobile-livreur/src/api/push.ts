/**
 * Abonnement de l'appareil aux notifications poussées (`push/*`).
 *
 * Routes communes aux deux apps : le serveur rattache le jeton au compte
 * authentifié, jamais à un compte désigné par la requête.
 */
import { api } from './client';
import { endpoints } from './endpoints';

export type DevicePlatform = 'ios' | 'android';

export async function registerDevice(token: string, platform: DevicePlatform): Promise<void> {
  await api.post(endpoints.pushRegister, { token, platform, app: 'deliveryman' });
}

export async function forgetDevice(token: string): Promise<void> {
  await api.post(endpoints.pushForget, { token });
}
