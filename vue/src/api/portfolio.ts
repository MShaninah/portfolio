export type PortfolioResponse = {
    profile: { email: string, phone: string }
    projects: any[]
    publications: any[]
}

export async function getPortfolio(lang: "en" | "de"): Promise<PortfolioResponse> {
    const res = await fetch(`/api/portfolio?lang=${lang}`, {
        headers: { Accept: "application/json" },
    })
    if (!res.ok) throw new Error(`API error: ${res.status}`)
    return res.json()
}