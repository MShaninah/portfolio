export type ContactPayload = {
  name: string
  email: string
  subject: string
  message: string
  // Honeypot field
  company?: string
}

export type ContactResponse = {
  message: string
}
import { apiUrl } from './http'
export async function sendContact(payload: ContactPayload): Promise<ContactResponse> {
  const res = await fetch(apiUrl('/api/contact'), {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(payload),
  })
  // Normalize common failure cases
  if (!res.ok) {
    let msg = 'Failed to send message'
    try {
      const data = await res.json()
      if (data?.message) msg = data.message
    } catch {}

    // Specific friendly messages
    if (res.status === 429) msg = 'Too many requests. Please try again later.'
    if (res.status >= 500) msg = 'Service unavailable. Please try again later.'

    throw new Error(msg)
  }

  return (await res.json()) as ContactResponse
}
