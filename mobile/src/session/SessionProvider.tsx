/**
 * État d'authentification partagé par l'app.
 *
 * Au démarrage, on relit le jeton depuis SecureStore : si un jeton existe, on
 * vérifie qu'il est encore valide en appelant `/profile`. Un jeton périmé produit
 * un 401 que le client d'API efface déjà — l'utilisateur retombe sur l'écran de
 * connexion sans état incohérent.
 */
import { createContext, useCallback, useContext, useEffect, useMemo, useState } from 'react';
import type { ReactNode } from 'react';

import { signIn as apiSignIn, signOut as apiSignOut, verifyOtp as apiVerifyOtp } from '../api/auth';
import { fetchProfile } from '../api/merchant';
import { getToken, onTokenCleared } from '../api/session';
import { desabonnerAppareil } from '../push';
import type { AuthUser } from '../api/types';

type SessionState = {
  /** `true` tant que la session initiale n'est pas résolue. */
  loading: boolean;
  user: AuthUser | null;
  signIn: (merchantId: string, password: string) => Promise<void>;
  /** Ouvre la session à partir du code SMS reçu après inscription. */
  verifyOtp: (mobile: string, otp: string) => Promise<void>;
  signOut: () => Promise<void>;
  refresh: () => Promise<void>;
};

const SessionContext = createContext<SessionState | null>(null);

export function SessionProvider({ children }: { children: ReactNode }) {
  const [loading, setLoading] = useState(true);
  const [user, setUser] = useState<AuthUser | null>(null);

  const restore = useCallback(async () => {
    const token = await getToken();
    if (!token) {
      setUser(null);
      setLoading(false);
      return;
    }
    try {
      const profile = await fetchProfile();
      if (await getToken() === token) setUser(profile);
    } catch {
      // Jeton invalide ou serveur injoignable : on repart déconnecté.
      if (await getToken() === token) setUser(null);
    } finally {
      setLoading(false);
    }
  }, []);

  // Une panne réseau n'invalide pas une session déjà ouverte. Le client d'API
  // efface le jeton sur 401 ; son écouteur conserve cette déconnexion.
  const refresh = useCallback(async () => {
    const token = await getToken();
    if (!token) {
      setUser(null);
      return;
    }
    const profile = await fetchProfile();
    // Ne pas restaurer le compte si la session a changé pendant la requête.
    if (await getToken() === token) setUser(profile);
  }, []);

  useEffect(() => {
    void restore();
  }, [restore]);

  // S144 — un jeton révoqué en cours de session (401) ramène à l'écran de connexion.
  useEffect(() => onTokenCleared(() => setUser(null)), []);

  const value = useMemo<SessionState>(
    () => ({
      loading,
      user,
      signIn: async (merchantId, password) => {
        setUser(await apiSignIn(merchantId, password));
      },
      verifyOtp: async (mobile, otp) => {
        setUser(await apiVerifyOtp(mobile, otp));
      },
      signOut: async () => {
        // D'abord l'appareil, tant que le jeton de session est encore valide :
        // après `apiSignOut()` l'API refuserait le désabonnement.
        await desabonnerAppareil();
        await apiSignOut();
        setUser(null);
      },
      refresh,
    }),
    [loading, user, refresh],
  );

  return <SessionContext.Provider value={value}>{children}</SessionContext.Provider>;
}

export function useSession(): SessionState {
  const ctx = useContext(SessionContext);
  if (!ctx) throw new Error('useSession doit être utilisé dans <SessionProvider>.');
  return ctx;
}
