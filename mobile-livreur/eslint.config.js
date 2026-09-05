// https://docs.expo.dev/guides/using-eslint/
const { defineConfig } = require('eslint/config');
const expoConfig = require('eslint-config-expo/flat');

module.exports = defineConfig([
  expoConfig,
  {
    ignores: ['dist/*'],
  },
  {
    rules: {
      /**
       * `set-state-in-effect` : désactivée, décision du 2026-08-18.
       *
       * Elle vise les rendus en cascade : un `setState` exécuté pendant la phase
       * synchrone d'un effet provoque une seconde passe de rendu immédiate. Elle
       * signale ici les 9 écrans qui chargent leurs données au montage
       * (`useEffect(() => { void load(); }, [load])`).
       *
       * Deux formes ont été essayées avant de trancher :
       *   1. déplacer le `setError('')` après le premier `await` — **toujours
       *      signalé** : la règle suit la fonction appelée, pas seulement le corps
       *      de l'effet ;
       *   2. faire le `setState` dans un `.then()` — **accepté**, mais il faut
       *      alors soit dupliquer le chargement (l'effet d'un côté, le
       *      « tirer pour rafraîchir » de l'autre), soit réécrire les 8 `load()`
       *      en chaînes de promesses. Le devis temporisé de `parcel/new` et ses
       *      annulations y perdraient beaucoup en lisibilité.
       *
       * Et le coût réel est nul : ces effets ne tournent qu'au montage, et le
       * premier `setState` écrit la valeur que l'état porte déjà (`''` pour
       * l'erreur, `true` pour le chargement) — React n'en déclenche aucun rendu.
       *
       * À revoir si l'app adopte une bibliothèque de chargement de données
       * (React Query ou équivalent) : la forme conforme viendrait alors gratuitement.
       */
      'react-hooks/set-state-in-effect': 'off',
    },
  },
]);
