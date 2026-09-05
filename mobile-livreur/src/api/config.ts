/**
 * Configuration d'accès à l'API de web/.
 *
 * Rien n'est codé en dur : tout vient de l'environnement Expo (`EXPO_PUBLIC_*`,
 * injecté au build). L'app Flutter dépréciée codait l'URL de production et la clé
 * d'API dans `api-list.dart` — ne pas reproduire.
 *
 * ⚠️ `EXPO_PUBLIC_*` est **embarqué en clair dans le bundle** : ces valeurs ne sont
 * pas des secrets. C'est acceptable pour l'URL ; ça ne l'est pas vraiment pour la
 * clé d'API — mais le backend l'exige sur toutes les routes et elle est déjà
 * publique (constat S3 de la cartographie : clé identique pour toutes les
 * installations, codée en dur dans `config/rxcourier.php`). À reprendre côté web/
 * avant la diffusion de l'APK aux PME pilotes.
 */

function required(name: string, value: string | undefined): string {
  if (!value || value.trim() === '') {
    throw new Error(
      `Variable d'environnement manquante : ${name}. ` +
        'Copier .env.example vers .env et renseigner les valeurs.',
    );
  }
  return value.trim();
}

/** Base de l'API, préfixe de version inclus. Ex. https://beninlink.app/api/v10 */
export const API_BASE_URL = required(
  'EXPO_PUBLIC_API_URL',
  process.env.EXPO_PUBLIC_API_URL,
).replace(/\/+$/, '');

/** En-tête `apiKey` exigé par CheckApiKeyMiddleware sur **toutes** les routes. */
export const API_KEY = required(
  'EXPO_PUBLIC_API_KEY',
  process.env.EXPO_PUBLIC_API_KEY,
);

/** Délai au-delà duquel une requête est abandonnée (ms). */
export const REQUEST_TIMEOUT_MS = 20_000;
