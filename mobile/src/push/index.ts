/**
 * Notifications poussées de l'app marchand.
 *
 * Le serveur écrit déjà le fil (`notifications/*`) ; ce module ajoute la
 * **poussée** : l'appareil s'abonne pour le compte connecté, et le toucher
 * d'une notification ouvre l'écran concerné.
 *
 * Trois choses ne sont volontairement pas faites ici :
 *   - on ne demande la permission qu'une fois connecté (un écran de connexion
 *     qui réclame les notifications se fait refuser une fois sur deux) ;
 *   - on ne bloque jamais l'app sur un échec : sans permission, sans réseau ou
 *     sur un simulateur, l'app fonctionne, seul le push manque ;
 *   - on n'invente aucun texte : la notification affichée est celle rédigée
 *     par le serveur, en français, comme le fil.
 */
import Constants from 'expo-constants';
import * as Device from 'expo-device';
import * as Notifications from 'expo-notifications';
import { router } from 'expo-router';
import { useEffect, useRef } from 'react';
import { Platform } from 'react-native';

import { forgetDevice, registerDevice, type DevicePlatform } from '../api/push';
import { t } from '../i18n';

/** Notification reçue app au premier plan : on l'affiche quand même. */
Notifications.setNotificationHandler({
  handleNotification: async () => ({
    shouldShowBanner: true,
    shouldShowList: true,
    shouldPlaySound: true,
    shouldSetBadge: false,
  }),
});

/**
 * Jeton de cet appareil, mémorisé pour pouvoir le désabonner à la déconnexion.
 * Le service de push le rend identique d'un démarrage à l'autre ; le garder en
 * mémoire évite d'avoir à le redemander au moment de partir.
 */
let jetonCourant: string | null = null;

/** Canal Android : sans lui, certains constructeurs rangent tout en silencieux. */
async function preparerCanalAndroid(): Promise<void> {
  if (Platform.OS !== 'android') return;
  await Notifications.setNotificationChannelAsync('default', {
    name: t('notifications.channelName'),
    importance: Notifications.AndroidImportance.HIGH,
    lightColor: '#12503A',
  });
}

/**
 * Identifiant de projet EAS, requis par le service de push.
 * Renseigné par `eas build` ; absent en développement local sans EAS.
 */
function projectId(): string | undefined {
  const extra = Constants.expoConfig?.extra as { eas?: { projectId?: string } } | undefined;
  return extra?.eas?.projectId ?? (Constants as { easConfig?: { projectId?: string } }).easConfig?.projectId;
}

/**
 * Abonne l'appareil. Renvoie le jeton, ou `null` si le push n'est pas
 * disponible ici (émulateur, permission refusée, projet EAS absent).
 */
export async function abonnerAppareil(): Promise<string | null> {
  try {
    if (!Device.isDevice) return null; // un émulateur ne reçoit rien

    const existant = await Notifications.getPermissionsAsync();
    const accord = existant.granted
      ? existant
      : await Notifications.requestPermissionsAsync();
    if (!accord.granted) return null;

    await preparerCanalAndroid();

    const id = projectId();
    const { data } = await Notifications.getExpoPushTokenAsync(id ? { projectId: id } : undefined);

    await registerDevice(data, Platform.OS as DevicePlatform);
    jetonCourant = data;
    return data;
  } catch {
    // Réseau, permission, service de push : rien de tout cela ne doit
    // empêcher d'utiliser l'app. Le fil reste consultable.
    return null;
  }
}

/** Désabonne l'appareil — à appeler AVANT d'effacer le jeton de session. */
export async function desabonnerAppareil(): Promise<void> {
  if (!jetonCourant) return;
  try {
    await forgetDevice(jetonCourant);
  } catch {
    // Sans réseau, le serveur gardera l'appareil : il l'oubliera au premier
    // envoi refusé par le service de push.
  } finally {
    jetonCourant = null;
  }
}

/** Écran à ouvrir pour une notification donnée. */
function destination(data: Record<string, unknown>): void {
  const kind = typeof data.kind === 'string' ? data.kind : '';
  const parcelId = data.parcel_id;

  switch (kind) {
    case 'parcel_status':
      if (parcelId) {
        router.push({ pathname: '/(app)/parcel/[id]', params: { id: String(parcelId) } });
        return;
      }
      router.push('/(app)/parcels');
      return;
    case 'wallet_credit':
    case 'payout':
      router.push('/(app)/wallet');
      return;
    case 'invoice':
      router.push('/(app)/invoices');
      return;
    case 'customs':
      router.push('/(app)/customs');
      return;
    default:
      router.push('/(app)/notifications');
  }
}

/**
 * Branche le push pour la durée de la session.
 *
 * `enabled` suit l'état de session : on s'abonne à l'ouverture, et le toucher
 * d'une notification n'est écouté que connecté — router vers un écran de
 * l'espace marchand alors qu'on est déconnecté rebondirait sur la connexion.
 */
export function usePushNotifications(enabled: boolean): void {
  const abonne = useRef(false);

  useEffect(() => {
    if (!enabled) {
      abonne.current = false;
      return;
    }
    if (abonne.current) return;
    abonne.current = true;
    void abonnerAppareil();
  }, [enabled]);

  useEffect(() => {
    if (!enabled) return;

    // L'app était fermée : la notification qui l'a ouverte attend ici.
    void Notifications.getLastNotificationResponseAsync().then((reponse) => {
      if (reponse) destination(reponse.notification.request.content.data ?? {});
    });

    const ecoute = Notifications.addNotificationResponseReceivedListener((reponse) => {
      destination(reponse.notification.request.content.data ?? {});
    });

    return () => ecoute.remove();
  }, [enabled]);
}
