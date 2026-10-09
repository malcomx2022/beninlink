/**
 * S144 — un jeton révoqué en cours de session ramène à l'écran de connexion.
 *
 * Depuis S135 et S136, le serveur révoque des jetons en cours de session (mot de passe changé ailleurs, réinitialisé,
 * rafraîchi). Le client d'API effaçait le jeton sur le 401, mais la session gardait son compte : l'app restait sur
 * des écrans connectés dont chaque appel échouait. `clearToken()` prévient désormais `SessionProvider`.
 *
 * ⚠️ `@testing-library/react-native` 14 rend en **asynchrone** (`await render`).
 */
import { Text } from 'react-native';
import { act, render, screen } from '@testing-library/react-native';

import { clearToken, onTokenCleared } from '../api/session';
import { SessionProvider, useSession } from './SessionProvider';

jest.mock('expo-secure-store', () => ({
  getItemAsync: jest.fn(async () => 'jeton-de-test'),
  setItemAsync: jest.fn(async () => undefined),
  deleteItemAsync: jest.fn(async () => undefined),
}));
jest.mock('../api/merchant', () => ({ fetchProfile: jest.fn(async () => ({ name: 'Compte S144' })) }));
jest.mock('../api/auth', () => ({ signIn: jest.fn(), signOut: jest.fn(), verifyOtp: jest.fn() }));
jest.mock('../push', () => ({ desabonnerAppareil: jest.fn(async () => undefined) }));

function Etat() {
  const { loading, user } = useSession();
  if (loading) return <Text>chargement</Text>;
  return <Text>{user ? 'connecté' : 'déconnecté'}</Text>;
}

describe('SessionProvider', () => {
  it('revient à la connexion quand le jeton tombe en cours de session', async () => {
    await render(<SessionProvider><Etat /></SessionProvider>);
    expect(await screen.findByText('connecté')).toBeTruthy();

    await act(async () => {
      await clearToken(); // ce que fait le client d'API sur un 401
    });

    expect(screen.getByText('déconnecté')).toBeTruthy();
  });

  it('se désabonne : un écouteur retiré n\'est plus appelé', async () => {
    const ecouteur = jest.fn();
    const retirer = onTokenCleared(ecouteur);
    await clearToken();
    retirer();
    await clearToken();
    expect(ecouteur).toHaveBeenCalledTimes(1);
  });
});
