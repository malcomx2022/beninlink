/**
 * Variables d'environnement lisibles depuis le code de l'app.
 *
 * Expo n'expose que le préfixe `EXPO_PUBLIC_*`, remplacé **à la compilation** dans
 * le bundle : ces valeurs ne sont donc pas des secrets. Les déclarer ici plutôt
 * que d'ajouter `@types/node` évite de faire croire qu'une API Node est disponible
 * dans React Native.
 *
 * Toute nouvelle variable doit être ajoutée ici ET dans `.env.example`.
 */
declare const process: {
  env: {
    /** Base de l'API de web/, préfixe de version inclus. */
    EXPO_PUBLIC_API_URL?: string;
    /** En-tête `apiKey` exigé par le backend sur toutes les routes. */
    EXPO_PUBLIC_API_KEY?: string;
    NODE_ENV?: 'development' | 'production' | 'test';
  };
};
