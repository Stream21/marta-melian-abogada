import { createContext, useCallback, useContext, useMemo, useState } from 'react';
import { clearAuthCookie, setAuthCookie } from '@/lib/auth-cookie';

const TOKEN_KEY = 'bufete_jwt_token';

interface JwtPayload {
  email?: string;
  username?: string;
  roles?: string[];
  exp?: number;
}

export interface AuthContextValue {
  token: string | null;
  isAuthenticated: boolean;
  userEmail: string | null;
  userRoles: string[];
  login: (token: string) => void;
  logout: () => void;
}

function decodePayload(token: string): JwtPayload | null {
  try {
    const payload = token.split('.')[1];
    return JSON.parse(atob(payload)) as JwtPayload;
  } catch {
    return null;
  }
}

function isTokenValid(token: string): boolean {
  const payload = decodePayload(token);
  if (!payload?.exp) return false;
  return payload.exp * 1000 > Date.now();
}

export function isSessionActive(): boolean {
  const token = localStorage.getItem(TOKEN_KEY);
  return !!token && isTokenValid(token);
}

const AuthContext = createContext<AuthContextValue | null>(null);

export function AuthProvider({ children }: { children: React.ReactNode }) {
  const [token, setToken] = useState<string | null>(() => {
    const stored = localStorage.getItem(TOKEN_KEY);
    if (stored && isTokenValid(stored)) {
      setAuthCookie(stored);
      return stored;
    }
    localStorage.removeItem(TOKEN_KEY);
    clearAuthCookie();
    return null;
  });

  const login = useCallback((newToken: string) => {
    localStorage.setItem(TOKEN_KEY, newToken);
    setAuthCookie(newToken);
    setToken(newToken);
  }, []);

  const logout = useCallback(() => {
    localStorage.removeItem(TOKEN_KEY);
    clearAuthCookie();
    setToken(null);
  }, []);

  const payload = token ? decodePayload(token) : null;

  const value = useMemo<AuthContextValue>(
    () => ({
      token,
      isAuthenticated: token !== null,
      userEmail: payload?.email ?? payload?.username ?? null,
      userRoles: payload?.roles ?? [],
      login,
      logout,
    }),
    [token, payload, login, logout],
  );

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
}

export function useAuth(): AuthContextValue {
  const ctx = useContext(AuthContext);
  if (!ctx) throw new Error('useAuth must be used inside <AuthProvider>');
  return ctx;
}
