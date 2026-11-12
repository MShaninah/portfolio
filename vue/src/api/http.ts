// Production serves the API from this same origin, under /api, so an empty
// base yields relative URLs. That keeps the API on the main domain's
// certificate and means requests are never cross-origin.
export const apiBase = import.meta.env.PROD
  ? ''
  : (import.meta.env.VITE_API_BASE as string | undefined) ?? 'http://localhost:8080';

export function apiUrl(path: string): string {
  if (!path.startsWith('/')) path = '/' + path;
  return apiBase.replace(/\/$/, '') + path;
}
