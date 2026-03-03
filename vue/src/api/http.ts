export const apiBase = import.meta.env.PROD ? 'https://api.mshaninah.com' : '';

export function apiUrl(path: string): string {
  if (!path.startsWith('/')) path = '/' + path;
  if (!apiBase) return path;
  return apiBase.replace(/\/$/, '') + path;
}
