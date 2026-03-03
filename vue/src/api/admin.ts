export type AdminPortfolio = {
  projects: any[]
  publications?: any[]
  profile: { email: string; phone?: string }
  skills?: string[]
}

const TOKEN_KEY = 'admin_token'

export function getToken(): string | null {
  try { return localStorage.getItem(TOKEN_KEY) } catch { return null }
}

export function setToken(token: string | null) {
  try {
    if (token) localStorage.setItem(TOKEN_KEY, token)
    else localStorage.removeItem(TOKEN_KEY)
  } catch {}
  try { window.dispatchEvent(new CustomEvent('admin-token', { detail: token })) } catch {}
}

function authHeader() {
  const t = getToken()
  return t ? { Authorization: `Bearer ${t}` } : {}
}

export async function adminLogin(password: string): Promise<string> {
  const res = await fetch('/api/admin/login', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
    body: JSON.stringify({ password })
  })
  if (!res.ok) throw new Error('Invalid credentials')
  const data = await res.json()
  const token = data.token as string
  setToken(token)
  return token
}

export async function adminFetch(): Promise<AdminPortfolio> {
  const res = await fetch('/api/admin/portfolio', { headers: { Accept: 'application/json', ...authHeader() } })
  if (res.status === 401) throw new Error('Unauthorized')
  if (!res.ok) throw new Error(`API ${res.status}`)
  return res.json()
}

export async function adminUpdateProfile(payload: { email: string; phone?: string }): Promise<AdminPortfolio['profile']> {
  const res = await fetch('/api/admin/profile', {
    method: 'PUT',
    headers: { 'Content-Type': 'application/json', Accept: 'application/json', ...authHeader() },
    body: JSON.stringify(payload)
  })
  if (!res.ok) throw new Error('Failed to update profile')
  const data = await res.json()
  return data.profile
}

export async function adminSetSkills(skills: string[]): Promise<string[]> {
  const res = await fetch('/api/admin/skills', {
    method: 'PUT',
    headers: { 'Content-Type': 'application/json', Accept: 'application/json', ...authHeader() },
    body: JSON.stringify({ skills })
  })
  if (!res.ok) throw new Error('Failed to update skills')
  const data = await res.json()
  return data.skills as string[]
}

export async function adminAddProject(project: any): Promise<any> {
  const res = await fetch('/api/admin/projects', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', Accept: 'application/json', ...authHeader() },
    body: JSON.stringify(project)
  })
  if (!res.ok) throw new Error('Failed to add project')
  return res.json()
}

export async function adminUpdateProject(id: string, patch: any): Promise<void> {
  const res = await fetch(`/api/admin/projects/${encodeURIComponent(id)}`, {
    method: 'PUT',
    headers: { 'Content-Type': 'application/json', Accept: 'application/json', ...authHeader() },
    body: JSON.stringify(patch)
  })
  if (!res.ok) throw new Error('Failed to update project')
}

export async function adminDeleteProject(id: string): Promise<void> {
  const res = await fetch(`/api/admin/projects/${encodeURIComponent(id)}`, {
    method: 'DELETE',
    headers: { Accept: 'application/json', ...authHeader() }
  })
  if (!res.ok) throw new Error('Failed to delete project')
}
