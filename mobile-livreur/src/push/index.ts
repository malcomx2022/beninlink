/**
 * Notifications poussées de l'app livreur.
 *
 * C'est ici qu'elles comptent le plus : un livreur qui ne sait pas qu'une
 * course vient de lui être affectée ne part pas. Contrairement au marchand, le
 * livreur n'a **pas de fil consultable** — la notification poussée est le seul
 * canal, et le toucher ouvre directement la course.
 *
 * Comme côté marchand : permission demandée une fois connecté seulement, et
 * aucun échec (permission, réseau, émulateur) n'empêche d'utiliser l'app.
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

/**
 * Écran à ouvrir pour une notification donnée.
 *
 * Un livreur n'a qu'une question en tête à ce moment-là : quelle course ? On
 * ouvre donc le détail dès qu'un colis est nommé, la liste sinon.
 */
function destination(data: Record<string, unknown>): void {
  const parcelId = data.parcel_id;

  if (parcelId) {
    router.push({ pathname: '/(app)/parcel/[id]', params: { id: String(parcelId) } });
    return;
  }

  router.push('/(app)/(tabs)');
}

/**
 * Branche le push pour la durée de la session.
 *
 * `enabled` suit l'état de session : on s'abonne à l'ouverture, et le toucher
 * n'est écouté que connecté — ouvrir une course alors qu'on est déconnecté
 * rebondirait sur l'écran de connexion.
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
