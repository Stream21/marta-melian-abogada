/** Cookie JWT legible por Lexik (`token_extractors.cookie.name: BEARER`). */
const AUTH_COOKIE = 'BEARER';
const TOKEN_TTL_SECONDS = 3600;

export function setAuthCookie(token: string): void {
  if (typeof document === 'undefined') return;
  const secure = window.location.protocol === 'https:' ? '; Secure' : '';
  document.cookie =
    `${AUTH_COOKIE}=${encodeURIComponent(token)}; Path=/; SameSite=Lax; Max-Age=${TOKEN_TTL_SECONDS}${secure}`;
}

export function clearAuthCookie(): void {
  if (typeof document === 'undefined') return;
  const secure = window.location.protocol === 'https:' ? '; Secure' : '';
  document.cookie = `${AUTH_COOKIE}=; Path=/; SameSite=Lax; Max-Age=0${secure}`;
}
