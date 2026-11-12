export type PortfolioResponse = {
    profile: { email: string; phone: string | null }
    skills: { name: string; category: string; level: string }[]
    projects: any[]
    cv: { available: boolean; filename: string | null; updated_at: string | null }
}

import { apiUrl } from './http'

// The API serves a single language: the old ?lang= parameter was accepted and
// then ignored, so it is no longer sent.
export async function getPortfolio(): Promise<PortfolioResponse> {
    const res = await fetch(apiUrl('/api/portfolio'), {
        headers: { Accept: "application/json" },
    })
    if (!res.ok) throw new Error(`API error: ${res.status}`)
    return res.json()
}

export function cvDownloadUrl(): string {
    return apiUrl('/api/cv')
}
