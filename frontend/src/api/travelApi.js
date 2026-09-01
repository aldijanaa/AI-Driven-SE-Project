const API_BASE = import.meta.env.VITE_API_URL || 'http://localhost:8000/api'

export async function getMatches(answers) {
  const response = await fetch(`${API_BASE}/match`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(answers),
  })

  if (!response.ok) {
    const body = await response.json().catch(() => ({}))
    throw new Error(body.error || `Request failed with status ${response.status}`)
  }

  const data = await response.json()
  return data.results
}

export async function sendResultsByEmail(email, results) {
  const response = await fetch(`${API_BASE}/notify`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ email, results }),
  })

  const body = await response.json().catch(() => ({}))

  if (!response.ok) {
    throw new Error(body.error || `Request failed with status ${response.status}`)
  }

  return body
}
