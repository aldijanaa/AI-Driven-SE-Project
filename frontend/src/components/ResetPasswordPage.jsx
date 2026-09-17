import { useState } from 'react'
import { resetPassword } from '../api/authApi'
import { isPasswordAcceptable } from '../lib/passwordStrength'
import PasswordStrengthMeter from './PasswordStrengthMeter'

function ResetPasswordPage({ token, onDone }) {
  const [form, setForm] = useState({ newPassword: '', confirmPassword: '' })
  const [status, setStatus] = useState('idle') // idle | submitting | error | done
  const [errorMessage, setErrorMessage] = useState('')

  const handleSubmit = async (e) => {
    e.preventDefault()

    if (form.newPassword !== form.confirmPassword) {
      setStatus('error')
      setErrorMessage('Passwords do not match')
      return
    }

    if (!isPasswordAcceptable(form.newPassword)) {
      setStatus('error')
      setErrorMessage('Password must be at least 8 characters and include a letter and a number')
      return
    }

    setStatus('submitting')
    setErrorMessage('')

    try {
      await resetPassword({ token, newPassword: form.newPassword })
      setStatus('done')
    } catch (err) {
      setStatus('error')
      setErrorMessage(err.message)
    }
  }

  return (
    <section className="auth-section">
      <p className="eyebrow">✈️ TravelMatch</p>
      <h1>Set a new password</h1>

      {status === 'done' ? (
        <>
          <p className="auth-success">Your password has been updated.</p>
          <button type="button" className="primary-button auth-submit" onClick={onDone}>
            Continue to log in
          </button>
        </>
      ) : (
        <form className="auth-form" onSubmit={handleSubmit}>
          <div className="auth-field">
            <label htmlFor="new-password">New password</label>
            <input
              id="new-password"
              type="password"
              required
              minLength={8}
              autoComplete="new-password"
              value={form.newPassword}
              onChange={(e) => setForm((f) => ({ ...f, newPassword: e.target.value }))}
            />
            <PasswordStrengthMeter password={form.newPassword} />
          </div>

          <div className="auth-field">
            <label htmlFor="confirm-new-password">Confirm new password</label>
            <input
              id="confirm-new-password"
              type="password"
              required
              autoComplete="new-password"
              value={form.confirmPassword}
              onChange={(e) => setForm((f) => ({ ...f, confirmPassword: e.target.value }))}
            />
          </div>

          {status === 'error' && <p className="auth-error">{errorMessage}</p>}

          <button type="submit" className="primary-button auth-submit" disabled={status === 'submitting'}>
            {status === 'submitting' ? 'Saving…' : 'Set new password'}
          </button>
        </form>
      )}
    </section>
  )
}

export default ResetPasswordPage
