/** Evita ERR_NGROK_6024 (pantalla intermedia del plan gratuito) en peticiones fetch. */
export function getNgrokFetchHeaders(): Record<string, string> {
  if (typeof window === 'undefined') return {};

  const host = window.location.hostname;
  if (
    host.includes('ngrok-free.dev') ||
    host.includes('ngrok-free.app') ||
    host.endsWith('.ngrok.io')
  ) {
    return { 'ngrok-skip-browser-warning': 'true' };
  }

  return {};
}

export function mergeFetchHeaders(headers?: HeadersInit): Record<string, string> {
  const base = getNgrokFetchHeaders();
  if (!headers) return base;

  if (headers instanceof Headers) {
    const merged = { ...base };
    headers.forEach((value, key) => {
      merged[key] = value;
    });
    return merged;
  }

  if (Array.isArray(headers)) {
    return { ...base, ...Object.fromEntries(headers) };
  }

  return { ...base, ...headers };
}
