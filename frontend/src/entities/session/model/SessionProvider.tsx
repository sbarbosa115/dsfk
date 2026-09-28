import {useMutation, useQuery, useQueryClient} from '@tanstack/react-query';
import {createContext, useContext, type ReactNode} from 'react';
import {
  fetchCurrentUser,
  login,
  logout,
  switchUser,
  type CurrentUser,
} from '../api/sessionApi';

interface Session {
  user: CurrentUser | null;
  loading: boolean;
  login: (email: string, password: string) => Promise<CurrentUser>;
  logout: () => Promise<void>;
  /** "Ver como": an email to view the app as that user, or '_exit' to go back. */
  switchUser: (identifier: string) => Promise<CurrentUser>;
}

const SessionContext = createContext<Session | null>(null);

export const SESSION_KEY = ['me'];
/** Kept across user switches, so a super admin can jump between users while viewing as someone. */
export const SWITCHABLE_USERS_KEY = ['switchable-users'];

export function SessionProvider({children}: {children: ReactNode}) {
  const queryClient = useQueryClient();
  const me = useQuery({
    queryKey: SESSION_KEY,
    queryFn: fetchCurrentUser,
    staleTime: Infinity,
  });

  const loginMutation = useMutation({
    mutationFn: (c: {email: string; password: string}) =>
      login(c.email, c.password),
    onSuccess: (user) => queryClient.setQueryData(SESSION_KEY, user),
  });
  const logoutMutation = useMutation({
    mutationFn: logout,
    onSettled: () => {
      queryClient.clear();
      queryClient.setQueryData(SESSION_KEY, null);
    },
  });
  const switchMutation = useMutation({
    mutationFn: switchUser,
    onSuccess: (user) => {
      // Everything cached belongs to the previous identity.
      queryClient.removeQueries({
        predicate: (q) => q.queryKey[0] !== SWITCHABLE_USERS_KEY[0],
      });
      queryClient.setQueryData(SESSION_KEY, user);
    },
  });

  const value: Session = {
    user: me.data ?? null,
    loading: me.isPending,
    login: (email, password) => loginMutation.mutateAsync({email, password}),
    logout: () => logoutMutation.mutateAsync(),
    switchUser: (identifier) => switchMutation.mutateAsync(identifier),
  };

  return (
    <SessionContext.Provider value={value}>{children}</SessionContext.Provider>
  );
}

export function useSession(): Session {
  const value = useContext(SessionContext);
  if (!value) {
    throw new Error('useSession must be used inside SessionProvider');
  }

  return value;
}
