const API_BASE = import.meta.env.VITE_API_URL || 'http://localhost:8000/api'

async function parseJsonResponse(response) {
  const body = await response.json().catch(() => ({}))

  if (!response.ok) {
    const error = new Error(body.error || `Request failed with status ${response.status}`)
    if (body.needsVerification) {
      error.needsVerification = true
      error.email = body.email
    }
    throw error
  }

  return body
}

export async function registerAccount({ firstName, lastName, email, password }) {
  const response = await fetch(`${API_BASE}/register.php`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ firstName, lastName, email, password }),
  })

  return parseJsonResponse(response)
}

export async function login({ email, password }) {
  const response = await fetch(`${API_BASE}/login.php`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ email, password }),
  })

  return parseJsonResponse(response)
}

export async function fetchCurrentUser(token) {
  const response = await fetch(`${API_BASE}/me.php`, {
    headers: { Authorization: `Bearer ${token}` },
  })

  return parseJsonResponse(response)
}

export async function logout(token) {
  await fetch(`${API_BASE}/logout.php`, {
    method: 'POST',
    headers: { Authorization: `Bearer ${token}` },
  })
}

export async function updateProfile(token, { firstName, lastName, email }) {
  const response = await fetch(`${API_BASE}/profile.php`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', Authorization: `Bearer ${token}` },
    body: JSON.stringify({ firstName, lastName, email }),
  })

  return parseJsonResponse(response)
}

export async function changePassword(token, { currentPassword, newPassword }) {
  const response = await fetch(`${API_BASE}/change-password.php`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', Authorization: `Bearer ${token}` },
    body: JSON.stringify({ currentPassword, newPassword }),
  })

  return parseJsonResponse(response)
}

export async function forgotPassword(email) {
  const response = await fetch(`${API_BASE}/forgot-password.php`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ email }),
  })

  return parseJsonResponse(response)
}

export async function resetPassword({ token, newPassword }) {
  const response = await fetch(`${API_BASE}/reset-password.php`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ token, newPassword }),
  })

  return parseJsonResponse(response)
}

export async function verifyEmail({ email, code }) {
  const response = await fetch(`${API_BASE}/verify-email.php`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ email, code }),
  })

  return parseJsonResponse(response)
}

export async function resendVerificationCode(email) {
  const response = await fetch(`${API_BASE}/resend-verification-code.php`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ email }),
  })

  return parseJsonResponse(response)
}
