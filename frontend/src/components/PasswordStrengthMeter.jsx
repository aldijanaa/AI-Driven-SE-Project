import { passwordStrength } from '../lib/passwordStrength'

const LABELS = { weak: 'Weak', fair: 'Fair', good: 'Good', strong: 'Strong' }

function PasswordStrengthMeter({ password }) {
  if (!password) return null

  const { score, label } = passwordStrength(password)

  return (
    <div className="password-strength" aria-live="polite">
      <div className="password-strength-bar">
        {[0, 1, 2, 3].map((i) => (
          <span key={i} className={`password-strength-segment ${i < score ? `filled-${label}` : ''}`} />
        ))}
      </div>
      <span className={`password-strength-label label-${label}`}>{LABELS[label]}</span>
    </div>
  )
}

export default PasswordStrengthMeter
