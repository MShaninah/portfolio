export type AdminPortfolio = {
  projects: any[]
  profile: { email: string; phone?: string }
  skills?: AdminSkill[]
  cv?: { available: boolean; filename: string | null; updated_at: string | null }
}

export type LoginResult = {
  token: string
  expires_in: number
  expires_at: string
}

import { apiUrl } from './http'

const TOKEN_KEY = 'admin_token'
const EXPIRY_KEY = 'admin_token_expires_at'

/**
 * Admin tokens are short-lived and signed server-side. The expiry is stored
 * alongside the token so an obviously-dead token is discarded before it is
 * sent, and any 401 clears it so the UI can prompt for a fresh login.
 */
export function getToken(): string | null {
  try {
    const token = localStorage.getItem(TOKEN_KEY)
    if (!token) return null

    const expiresAt = localStorage.getItem(EXPIRY_KEY)
    if (expiresAt && Date.parse(expiresAt) <= Date.now()) {
      setToken(null)
      return null
    }

    return token
  } catch {
    return null
  }
}

export function setToken(token: string | null, expiresAt?: string) {
  try {
    if (token) {
      localStorage.setItem(TOKEN_KEY, token)
      if (expiresAt) localStorage.setItem(EXPIRY_KEY, expiresAt)
      else localStorage.removeItem(EXPIRY_KEY)
    } else {
      localStorage.removeItem(TOKEN_KEY)
      localStorage.removeItem(EXPIRY_KEY)
    }
  } catch {}
  try { window.dispatchEvent(new CustomEvent('admin-token', { detail: token })) } catch {}
}

/** Called on any 401: the token is gone or no longer valid. */
function onUnauthorized(): Error {
  setToken(null)
  return new Error('Session expired. Please sign in again.')
}

/**
 * Pulls the server's own explanation out of an error response.
 *
 * A 422 from MapRequestPayload carries the failing field and reason; throwing a
 * generic "Failed to update project" threw that away and left no way to tell
 * what was actually wrong.
 */
async function errorMessage(res: Response, fallback: string): Promise<string> {
  try {
    const body: any = await res.clone().json()

    const violations = body?.violations
    if (Array.isArray(violations) && violations.length > 0) {
      return violations
        .slice(0, 3)
        .map((v: any) => (v?.propertyPath ? `${v.propertyPath}: ${v.message}` : v?.message))
        .filter(Boolean)
        .join(' · ')
    }

    return body?.detail || body?.message || fallback
  } catch {
    return fallback
  }
}

async function guard(res: Response, fallback: string): Promise<Response> {
  if (res.status === 401) throw onUnauthorized()
  if (res.status === 429) throw new Error('Too many requests. Please try again later.')
  if (!res.ok) throw new Error(await errorMessage(res, fallback))
  return res
}

function authHeader(): Record<string, string> {
  const t = getToken()
  return t ? { Authorization: `Bearer ${t}` } : ({} as Record<string, string>)
}

export async function adminLogin(password: string): Promise<string> {
  const res = await fetch(apiUrl('/api/admin/login'), {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
    body: JSON.stringify({ password })
  })

  if (res.status === 429) {
    throw new Error('Too many login attempts. Please try again later.')
  }
  if (!res.ok) throw new Error('Invalid credentials')

  const data = (await res.json()) as LoginResult
  setToken(data.token, data.expires_at)
  return data.token
}

export type CvInfo = { available: boolean; filename: string | null; updated_at: string | null }

export async function adminUploadCv(file: File): Promise<{ filename: string; updated_at: string | null }> {
  const body = new FormData()
  body.append('file', file)

  const res = await fetch(apiUrl('/api/admin/cv'), {
    method: 'POST',
    // Content-Type is deliberately NOT set: the browser must add it itself so
    // the multipart boundary is included.
    headers: { Accept: 'application/json', ...authHeader() },
    body,
  })
  await guard(res, 'Failed to upload the CV')
  return res.json()
}

export async function adminFetch(): Promise<AdminPortfolio> {
  const res = await fetch(apiUrl('/api/admin/portfolio'), { headers: { Accept: 'application/json', ...authHeader() } })
  await guard(res, `API ${res.status}`)
  return res.json()
}

export async function adminUpdateProfile(payload: { email: string; phone?: string }): Promise<AdminPortfolio['profile']> {
  const res = await fetch(apiUrl('/api/admin/profile'), {
    method: 'PUT',
    headers: { 'Content-Type': 'application/json', Accept: 'application/json', ...authHeader() },
    body: JSON.stringify(payload)
  })
  await guard(res, 'Failed to update profile')
  const data = await res.json()
  return data.profile
}

export type AdminSkill = { name: string; category: string; level: string }

export async function adminSetSkills(skills: AdminSkill[]): Promise<AdminSkill[]> {
  const res = await fetch(apiUrl('/api/admin/skills'), {
    method: 'PUT',
    headers: { 'Content-Type': 'application/json', Accept: 'application/json', ...authHeader() },
    body: JSON.stringify({ skills })
  })
  await guard(res, 'Failed to update skills')
  const data = await res.json()
  return data.skills as AdminSkill[]
}

export async function adminAddProject(project: any): Promise<any> {
  const res = await fetch(apiUrl('/api/admin/projects'), {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', Accept: 'application/json', ...authHeader() },
    body: JSON.stringify(project)
  })
  await guard(res, 'Failed to add project')
  return res.json()
}

export async function adminUpdateProject(id: string, patch: any): Promise<void> {
  const res = await fetch(apiUrl(`/api/admin/projects/${encodeURIComponent(id)}`), {
    method: 'PUT',
    headers: { 'Content-Type': 'application/json', Accept: 'application/json', ...authHeader() },
    body: JSON.stringify(patch)
  })
  await guard(res, 'Failed to update project')
}

export async function adminDeleteProject(id: string): Promise<void> {
  const res = await fetch(apiUrl(`/api/admin/projects/${encodeURIComponent(id)}`), {
    method: 'DELETE',
    headers: { Accept: 'application/json', ...authHeader() }
  })
  await guard(res, 'Failed to delete project')
}
