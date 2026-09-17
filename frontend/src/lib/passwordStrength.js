export const PASSWORD_MIN_LENGTH = 8

// Mirrors backend/src/PasswordPolicy.php so the live meter a user sees while
// typing matches what actually gets stored on their account.
export function passwordStrength(password) {
  const length = password.length
  const varietyCount = [/[a-z]/, /[A-Z]/, /[0-9]/, /[^a-zA-Z0-9]/].filter((re) => re.test(password)).length

  let score = 0
  if (length >= PASSWORD_MIN_LENGTH) score++
  if (length >= 12) score++
  if (varietyCount >= 3) score++
  if (length >= 16 && varietyCount >= 3) score++

  const label = score >= 4 ? 'strong' : score >= 3 ? 'good' : score >= 2 ? 'fair' : 'weak'

  return { score, label }
}

export function isPasswordAcceptable(password) {
  return password.length >= PASSWORD_MIN_LENGTH && /[a-zA-Z]/.test(password) && /[0-9]/.test(password)
}
