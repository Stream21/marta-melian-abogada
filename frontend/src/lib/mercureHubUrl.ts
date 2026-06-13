/**
 * En desarrollo usa el proxy de Vite (same-origin) para evitar CORS y problemas con el JWT.
 * En producción usa VITE_MERCURE_PUBLIC_URL o la URL devuelta por la API.
 */
export function resolveMercureHubUrl(apiHubUrl: string): string {
  if (import.meta.env.DEV) {
    return `${window.location.origin}/.well-known/mercure`;
  }

  const mercurePublicUrl = import.meta.env.VITE_MERCURE_PUBLIC_URL as string | undefined;
  if (mercurePublicUrl?.trim()) {
    return `${mercurePublicUrl.replace(/\/$/, '')}/.well-known/mercure`;
  }

  return apiHubUrl;
}

export function buildMercureEventSourceUrl(
  apiHubUrl: string,
  topics: string[],
  token: string,
): string {
  const url = new URL(resolveMercureHubUrl(apiHubUrl));
  topics.forEach((topic) => url.searchParams.append('topic', topic));
  url.searchParams.set('authorization', `Bearer ${token}`);
  return url.toString();
}
