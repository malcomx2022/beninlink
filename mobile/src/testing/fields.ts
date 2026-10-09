/**
 * Outil de test — retrouver le champ de saisie d'un `Field` (`src/components/ui.tsx`) par son libellé.
 *
 * `Field` pose un libellé au-dessus d'un `TextInput` sans les relier pour
 * l'accessibilité ; on remonte donc du libellé à son conteneur, puis on y prend
 * le champ. Importé seulement par des fichiers `*.test.tsx`.
 */
import { screen } from '@testing-library/react-native';

export function champ(libelle: string) {
  const input = screen
    .getByText(libelle)
    .parent?.queryAll((noeud) => noeud.type === 'TextInput')[0];
  if (!input) throw new Error(`Aucun champ sous le libellé « ${libelle} ».`);
  return input;
}
