/**
 * Preuve de livraison : une photo prise sur place, réduite pour le réseau
 * mobile (qualité 0,5, côté long ~1 280 px suffit à un contrôle visuel).
 * Renvoie l'URI locale, ou `null` si l'utilisateur annule ; lève si l'accès à
 * l'appareil photo est refusé.
 */
import * as ImagePicker from 'expo-image-picker';

export class CameraDeniedError extends Error {}

export async function takeDeliveryPhoto(): Promise<string | null> {
  const { status } = await ImagePicker.requestCameraPermissionsAsync();
  if (status !== 'granted') throw new CameraDeniedError('camera denied');

  const result = await ImagePicker.launchCameraAsync({
    mediaTypes: ['images'],
    quality: 0.5,
    allowsEditing: false,
    exif: false,
  });
  if (result.canceled) return null;
  return result.assets[0]?.uri ?? null;
}
