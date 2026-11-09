export const apiBase = import.meta.env.PROD
  ? 'https://api.mshaninah.com'
  : (import.meta.env.VITE_API_BASE as string | undefined) ?? 'http://localhost:8080';

export function apiUrl(path: string): string {
  if (!path.startsWith('/')) path = '/' + path;
  return apiBase.replace(/\/$/, '') + path;
}
