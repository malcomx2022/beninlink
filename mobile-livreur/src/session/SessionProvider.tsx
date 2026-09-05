/**
 * État d'authentification partagé par l'app.
 *
 * Au démarrage, on relit le jeton depuis SecureStore : s'il existe, on vérifie
 * qu'il est encore valide en appelant `deliveryman/profile`. Un jeton périmé
 * produit un 401 que le client d'API efface déjà.
 */
import { createContext, useCallback, useContext, useEffect, useMemo, useState } from 'react';
import type { ReactNode } from 'react';

import { signIn as apiSignIn, signOut as apiSignOut } from '../api/auth';
import { fetchProfile } from '../api/deliveryman';
import { getToken } from '../api/session';
import { desabonnerAppareil } from '../push';
import type { DeliverymanUser } from '../api/types';

type SessionState = {
  loading: boolean;
  user: DeliverymanUser | null;
  signIn: (driverId: string, password: string) => Promise<void>;
  signOut: () => Promise<void>;
  refresh: () => Promise<void>;
};

const SessionContext = createContext<SessionState | null>(null);

export function SessionProvider({ children }: { children: ReactNode }) {
  const [loading, setLoading] = useState(true);
  const [user, setUser] = useState<DeliverymanUser | null>(null);

  const restore = useCallback(async () => {
    const token = await getToken();
    if (!token) {
      setUser(null);
      setLoading(false);
      return;
    }
    try {
      setUser((await fetchProfile()).user);
    } catch {
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
      signIn: async (driverId, password) => {
        setUser(await apiSignIn(driverId, password));
      },
      signOut: async () => {
        // D'abord l'appareil, tant que le jeton de session est encore valide :
        // après `apiSignOut()` l'API refuserait le désabonnement.
        await desabonnerAppareil();
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
