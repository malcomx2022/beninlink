/**
 * i18n minimal, sans dépendance : le MVP est monolingue (français).
 *
 * Une seule fonction d'accès, pour que l'ajout ultérieur d'une langue n'oblige
 * pas à toucher les écrans. ⚠️ L'API n'a **aucune gestion de locale** (le
 * middleware LanguageManager n'est monté que sur le groupe `web`) : les messages
 * renvoyés par le serveur arrivent dans sa locale par défaut, désormais `fr`.
 */
import { fr, type Translations } from './fr';

const translations: Translations = fr;

type Path<T> = T extends object
  ? { [K in keyof T & string]: T[K] extends object ? `${K}.${Path<T[K]>}` : K }[keyof T & string]
  : never;

export type TranslationKey = Path<Translations>;

/**
 * `t('parcels.title')`. La clé est vérifiée à la compilation ; en cas de clé
 * absente à l'exécution, on renvoie la clé elle-même plutôt que de planter.
 */
export function t(key: TranslationKey): string {
  const value = key
    .split('.')
    .reduce<unknown>((acc, part) => (acc as Record<string, unknown>)?.[part], translations);
  return typeof value === 'string' ? value : key;
}

/**
 * Libellé d'une étape marchand. Passe par `t()` avec une clé correctement typée,
 * plutôt qu'un gabarit de chaîne que TypeScript ne saurait pas vérifier.
 */
export function stageLabel(stage: keyof Translations['parcelStage']): string {
  return t(`parcelStage.${stage}`);
}
