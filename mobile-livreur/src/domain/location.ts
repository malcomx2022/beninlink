/**
 * Partage de position du livreur.
 *
 * Une position à la demande (bouton) ou après une déclaration de livraison :
 * pas de suivi en arrière-plan, qui exigerait une autorisation « toujours »
 * et une batterie que les téléphones des livreurs n'ont pas. Le backend
 * l'écrit sur toutes les courses en cours du livreur authentifié (S7).
 */
import * as Location from 'expo-location';

import { updateLocation } from '../api/deliveryman';

export type ShareResult = 'sent' | 'denied' | 'unavailable';

export async function shareCurrentPosition(): Promise<ShareResult> {
  const { status } = await Location.requestForegroundPermissionsAsync();
  if (status !== 'granted') return 'denied';

  let position: Location.LocationObject;
  try {
    position = await Location.getCurrentPositionAsync({ accuracy: Location.Accuracy.Balanced });
  } catch {
    return 'unavailable';
  }

  await updateLocation(position.coords.latitude, position.coords.longitude);
  return 'sent';
}
