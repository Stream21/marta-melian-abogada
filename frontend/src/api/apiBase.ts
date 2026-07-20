/** Base URL de la API. Vacío en dev → rutas relativas `/api/...` vía proxy Vite. */
export function getApiBase(): string {
  return import.meta.env.VITE_API_BASE_URL || '';
}

export function apiAbsoluteUrl(path: string): string {
  if (path.startsWith('http://') || path.startsWith('https://')) {
    return path;
  }
  return `${getApiBase()}${path}`;
}
