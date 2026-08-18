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
import { getToken } from '../api/session';
import type { AuthUser } from '../api/types';

type SessionState = {
  /** `true` tant que la session initiale n'est pas résolue. */
  loading: boolean;
  user: AuthUser | null;
  signIn: (merchantId: string, password: string) => Promise<void>;
  /** Ouvre la session à partir du code SMS reçu après inscription. */
  verifyOtp: (otp: string) => Promise<void>;
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
      setUser(await fetchProfile());
    } catch {
      // Jeton invalide ou serveur injoignable : on repart déconnecté.
      setUser(null);
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    void restore();
  }, [restore]);

  const value = useMemo<SessionState>(
    () => ({
      loading,
      user,
      signIn: async (merchantId, password) => {
        setUser(await apiSignIn(merchantId, password));
      },
      verifyOtp: async (otp) => {
        setUser(await apiVerifyOtp(otp));
      },
      signOut: async () => {
        await apiSignOut();
        setUser(null);
      },
      refresh: restore,
    }),
    [loading, user, restore],
  );

  return <SessionContext.Provider value={value}>{children}</SessionContext.Provider>;
}

export function useSession(): SessionState {
  const ctx = useContext(SessionContext);
  if (!ctx) throw new Error('useSession doit être utilisé dans <SessionProvider>.');
  return ctx;
}
