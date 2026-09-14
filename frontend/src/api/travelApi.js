const API_BASE = import.meta.env.VITE_API_URL || 'http://localhost:8000/api'

export async function getMatches(answers, token) {
  const headers = { 'Content-Type': 'application/json' }
  if (token) headers.Authorization = `Bearer ${token}`

  const response = await fetch(`${API_BASE}/match.php`, {
    method: 'POST',
    headers,
    body: JSON.stringify(answers),
  })

  if (!response.ok) {
    const body = await response.json().catch(() => ({}))
    throw new Error(body.error || `Request failed with status ${response.status}`)
  }

  const data = await response.json()
  return data.results
}

export async function getExploreDestinations() {
  const response = await fetch(`${API_BASE}/destinations.php`)

  if (!response.ok) {
    const body = await response.json().catch(() => ({}))
    throw new Error(body.error || `Request failed with status ${response.status}`)
  }

  const data = await response.json()
  return data.destinations
}

export async function searchDestinations(query) {
  const response = await fetch(`${API_BASE}/search.php?q=${encodeURIComponent(query)}`)

  if (!response.ok) {
    const body = await response.json().catch(() => ({}))
    throw new Error(body.error || `Request failed with status ${response.status}`)
  }

  const data = await response.json()
  return data.destinations
}

export async function getHistory(token) {
  const response = await fetch(`${API_BASE}/history.php`, {
    headers: { Authorization: `Bearer ${token}` },
  })

  if (!response.ok) {
    const body = await response.json().catch(() => ({}))
    throw new Error(body.error || `Request failed with status ${response.status}`)
  }

  const data = await response.json()
  return data.submissions
}

export async function getFavorites(token) {
  const response = await fetch(`${API_BASE}/favorites.php`, {
    headers: { Authorization: `Bearer ${token}` },
  })

  if (!response.ok) {
    const body = await response.json().catch(() => ({}))
    throw new Error(body.error || `Request failed with status ${response.status}`)
  }

  const data = await response.json()
  return data.destinations
}

export async function toggleFavorite(token, destinationId) {
  const response = await fetch(`${API_BASE}/favorites.php`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', Authorization: `Bearer ${token}` },
    body: JSON.stringify({ destinationId }),
  })

  if (!response.ok) {
    const body = await response.json().catch(() => ({}))
    throw new Error(body.error || `Request failed with status ${response.status}`)
  }

  return response.json()
}

export async function sendResultsByEmail(email, results) {
  const response = await fetch(`${API_BASE}/notify.php`, {
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
