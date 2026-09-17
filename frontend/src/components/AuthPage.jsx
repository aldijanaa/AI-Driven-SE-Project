import { useState } from 'react'
import { forgotPassword, login, registerAccount, resendVerificationCode, verifyEmail } from '../api/authApi'
import { isPasswordAcceptable } from '../lib/passwordStrength'
import PasswordStrengthMeter from './PasswordStrengthMeter'

const EMPTY_FORM = { firstName: '', lastName: '', email: '', password: '', confirmPassword: '' }

function AuthPage({ onAuthenticated }) {
  const [mode, setMode] = useState('login') // login | register | forgot | verify
  const [form, setForm] = useState(EMPTY_FORM)
  const [status, setStatus] = useState('idle') // idle | submitting | error | sent
  const [errorMessage, setErrorMessage] = useState('')

  const [pendingEmail, setPendingEmail] = useState('')
  const [code, setCode] = useState('')
  const [resendStatus, setResendStatus] = useState('idle') // idle | sending | sent

  const isRegister = mode === 'register'
  const isForgot = mode === 'forgot'
  const isVerify = mode === 'verify'

  const updateField = (field) => (e) => setForm((f) => ({ ...f, [field]: e.target.value }))

  const switchMode = (nextMode) => {
    setMode(nextMode)
    setForm(EMPTY_FORM)
    setStatus('idle')
    setErrorMessage('')
  }

  const enterVerifyMode = (email) => {
    setMode('verify')
    setPendingEmail(email)
    setCode('')
    setResendStatus('idle')
    setStatus('idle')
    setErrorMessage('')
  }

  const handleForgotSubmit = async (e) => {
    e.preventDefault()
    setStatus('submitting')
    setErrorMessage('')

    try {
      await forgotPassword(form.email)
      setStatus('sent')
    } catch (err) {
      setStatus('error')
      setErrorMessage(err.message)
    }
  }

  const handleVerifySubmit = async (e) => {
    e.preventDefault()
    setStatus('submitting')
    setErrorMessage('')

    try {
      const { user, token } = await verifyEmail({ email: pendingEmail, code })
      onAuthenticated(user, token)
    } catch (err) {
      setStatus('error')
      setErrorMessage(err.message)
    }
  }

  const handleResendCode = async () => {
    setResendStatus('sending')
    try {
      await resendVerificationCode(pendingEmail)
      setResendStatus('sent')
    } catch {
      setResendStatus('idle')
    }
  }

  const handleSubmit = async (e) => {
    e.preventDefault()

    if (isRegister && form.password !== form.confirmPassword) {
      setStatus('error')
      setErrorMessage('Passwords do not match')
      return
    }

    if (isRegister && !isPasswordAcceptable(form.password)) {
      setStatus('error')
      setErrorMessage('Password must be at least 8 characters and include a letter and a number')
      return
    }

    setStatus('submitting')
    setErrorMessage('')

    try {
      if (isRegister) {
        const { user } = await registerAccount(form)
        enterVerifyMode(user.email)
        return
      }

      const { user, token } = await login(form)
      onAuthenticated(user, token)
    } catch (err) {
      if (err.needsVerification) {
        enterVerifyMode(err.email || form.email)
        return
      }
      setStatus('error')
      setErrorMessage(err.message)
    }
  }

  if (isVerify) {
    return (
      <section className="auth-section">
        <p className="eyebrow">✈️ TravelMatch</p>
        <h1>Check your email</h1>
        <p className="subtitle">Enter the 6-digit code we sent to {pendingEmail}.</p>

        <form className="auth-form" onSubmit={handleVerifySubmit}>
          <div className="auth-field">
            <label htmlFor="verify-code">Verification code</label>
            <input
              id="verify-code"
              type="text"
              inputMode="numeric"
              maxLength={6}
              required
              autoComplete="one-time-code"
              className="verification-code-input"
              value={code}
              onChange={(e) => setCode(e.target.value.replace(/\D/g, '').slice(0, 6))}
            />
          </div>

          {status === 'error' && <p className="auth-error">{errorMessage}</p>}

          <button
            type="submit"
            className="primary-button auth-submit"
            disabled={status === 'submitting' || code.length !== 6}
          >
            {status === 'submitting' ? 'Verifying…' : 'Verify email'}
          </button>
        </form>

        <p className="auth-toggle">
          {resendStatus === 'sent' ? (
            "New code sent — check your inbox."
          ) : (
            <>
              Didn&apos;t get it?{' '}
              <button
                type="button"
                className="link-button"
                onClick={handleResendCode}
                disabled={resendStatus === 'sending'}
              >
                {resendStatus === 'sending' ? 'Sending…' : 'Resend code'}
              </button>
            </>
          )}
        </p>

        <p className="auth-toggle">
          <button type="button" className="link-button" onClick={() => switchMode('login')}>
            Back to log in
          </button>
        </p>
      </section>
    )
  }

  if (isForgot) {
    return (
      <section className="auth-section">
        <p className="eyebrow">✈️ TravelMatch</p>
        <h1>Reset your password</h1>
        <p className="subtitle">Enter your account email and we&apos;ll send you a reset link.</p>

        {status === 'sent' ? (
          <p className="auth-success">
            If that email is registered, we&apos;ve sent a password reset link. Check your inbox.
          </p>
        ) : (
          <form className="auth-form" onSubmit={handleForgotSubmit}>
            <div className="auth-field">
              <label htmlFor="forgot-email">Email</label>
              <input
                id="forgot-email"
                type="email"
                required
                placeholder="you@example.com"
                value={form.email}
                onChange={updateField('email')}
              />
            </div>

            {status === 'error' && <p className="auth-error">{errorMessage}</p>}

            <button type="submit" className="primary-button auth-submit" disabled={status === 'submitting'}>
              {status === 'submitting' ? 'Sending…' : 'Send reset link'}
            </button>
          </form>
        )}

        <p className="auth-toggle">
          <button type="button" className="link-button" onClick={() => switchMode('login')}>
            Back to log in
          </button>
        </p>
      </section>
    )
  }

  return (
    <section className="auth-section">
      <p className="eyebrow">✈️ TravelMatch</p>
      <h1>{isRegister ? 'Create your account' : 'Welcome back'}</h1>
      <p className="subtitle">
        {isRegister
          ? 'Sign up to get personalized destination matches.'
          : 'Log in to continue to your travel matches.'}
      </p>

      <form className="auth-form" onSubmit={handleSubmit}>
        {isRegister && (
          <div className="auth-form-row">
            <div className="auth-field">
              <label htmlFor="firstName">First name</label>
              <input
                id="firstName"
                type="text"
                required
                maxLength={100}
                value={form.firstName}
                onChange={updateField('firstName')}
              />
            </div>
            <div className="auth-field">
              <label htmlFor="lastName">Last name</label>
              <input
                id="lastName"
                type="text"
                required
                maxLength={100}
                value={form.lastName}
                onChange={updateField('lastName')}
              />
            </div>
          </div>
        )}

        <div className="auth-field">
          <label htmlFor="email">Email</label>
          <input
            id="email"
            type="email"
            required
            placeholder="you@example.com"
            value={form.email}
            onChange={updateField('email')}
          />
        </div>

        <div className="auth-field">
          <label htmlFor="password">Password</label>
          <input
            id="password"
            type="password"
            required
            minLength={isRegister ? 8 : undefined}
            autoComplete={isRegister ? 'new-password' : 'current-password'}
            value={form.password}
            onChange={updateField('password')}
          />
          {isRegister && <PasswordStrengthMeter password={form.password} />}
          {!isRegister && (
            <button
              type="button"
              className="link-button forgot-password-link"
              onClick={() => switchMode('forgot')}
            >
              Forgot your password?
            </button>
          )}
        </div>

        {isRegister && (
          <div className="auth-field">
            <label htmlFor="confirmPassword">Confirm password</label>
            <input
              id="confirmPassword"
              type="password"
              required
              autoComplete="new-password"
              value={form.confirmPassword}
              onChange={updateField('confirmPassword')}
            />
          </div>
        )}

        {status === 'error' && <p className="auth-error">{errorMessage}</p>}

        <button type="submit" className="primary-button auth-submit" disabled={status === 'submitting'}>
          {status === 'submitting' ? 'Please wait…' : isRegister ? 'Create account' : 'Log in'}
        </button>
      </form>

      <p className="auth-toggle">
        {isRegister ? (
          <>
            Already have an account?{' '}
            <button type="button" className="link-button" onClick={() => switchMode('login')}>
              Log in
            </button>
          </>
        ) : (
          <>
            Don&apos;t have an account?{' '}
            <button type="button" className="link-button" onClick={() => switchMode('register')}>
              Sign up
            </button>
          </>
        )}
      </p>
    </section>
  )
}

export default AuthPage
