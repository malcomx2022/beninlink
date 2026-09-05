/**
 * Abonnement de l'appareil aux notifications poussées (`push/*`).
 *
 * L'app n'envoie que le jeton rendu par le service de push : le serveur le
 * rattache au compte authentifié. Aucune clé, aucun identifiant de compte ne
 * transite ici — comme pour FedaPay, les secrets restent côté `web/`.
 */
import { api } from './client';
import { endpoints } from './endpoints';

export type DevicePlatform = 'ios' | 'android';

export async function registerDevice(token: string, platform: DevicePlatform): Promise<void> {
  await api.post(endpoints.pushRegister, { token, platform, app: 'merchant' });
}

export async function forgetDevice(token: string): Promise<void> {
  await api.post(endpoints.pushForget, { token });
}
