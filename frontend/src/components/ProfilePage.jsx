import { useState } from 'react'
import { changePassword, updateProfile } from '../api/authApi'
import { applyTheme, getStoredTheme } from '../lib/theme'
import { isPasswordAcceptable } from '../lib/passwordStrength'
import PasswordStrengthMeter from './PasswordStrengthMeter'

const THEME_OPTIONS = [
  { value: 'system', label: 'System' },
  { value: 'light', label: 'Light' },
  { value: 'dark', label: 'Dark' },
]

function initials(firstName, lastName) {
  return `${firstName?.[0] ?? ''}${lastName?.[0] ?? ''}`.toUpperCase()
}

function formatMemberSince(isoString) {
  if (!isoString) return null
  return new Date(isoString).toLocaleDateString(undefined, { year: 'numeric', month: 'long' })
}

function ProfilePage({ user, token, onUserUpdated }) {
  const [profileForm, setProfileForm] = useState({
    firstName: user.firstName,
    lastName: user.lastName,
    email: user.email,
  })
  const [profileStatus, setProfileStatus] = useState('idle') // idle | saving | saved | error
  const [profileError, setProfileError] = useState('')

  const [passwordForm, setPasswordForm] = useState({ currentPassword: '', newPassword: '', confirmPassword: '' })
  const [passwordStatus, setPasswordStatus] = useState('idle')
  const [passwordError, setPasswordError] = useState('')

  const [theme, setTheme] = useState(getStoredTheme)

  const handleThemeChange = (value) => {
    setTheme(value)
    applyTheme(value)
  }

  const handleProfileSubmit = async (e) => {
    e.preventDefault()
    setProfileStatus('saving')
    setProfileError('')

    try {
      const { user: updatedUser } = await updateProfile(token, profileForm)
      onUserUpdated(updatedUser)
      setProfileStatus('saved')
    } catch (err) {
      setProfileStatus('error')
      setProfileError(err.message)
    }
  }

  const handlePasswordSubmit = async (e) => {
    e.preventDefault()

    if (passwordForm.newPassword !== passwordForm.confirmPassword) {
      setPasswordStatus('error')
      setPasswordError('New passwords do not match')
      return
    }

    if (!isPasswordAcceptable(passwordForm.newPassword)) {
      setPasswordStatus('error')
      setPasswordError('Password must be at least 8 characters and include a letter and a number')
      return
    }

    setPasswordStatus('saving')
    setPasswordError('')

    try {
      await changePassword(token, passwordForm)
      setPasswordForm({ currentPassword: '', newPassword: '', confirmPassword: '' })
      setPasswordStatus('saved')
    } catch (err) {
      setPasswordStatus('error')
      setPasswordError(err.message)
    }
  }

  const memberSince = formatMemberSince(user.createdAt)

  return (
    <section className="profile-section">
      <h2>Your profile</h2>

      <div className="profile-overview">
        <div className="profile-avatar">{initials(user.firstName, user.lastName)}</div>
        <div>
          <p className="profile-name">
            {user.firstName} {user.lastName}
          </p>
          <p className="profile-email">{user.email}</p>
          {memberSince && <p className="profile-since">Member since {memberSince}</p>}
        </div>
      </div>

      <div className="profile-card">
        <h3>Edit profile</h3>
        <form className="auth-form profile-form" onSubmit={handleProfileSubmit}>
          <div className="auth-form-row">
            <div className="auth-field">
              <label htmlFor="profile-firstName">First name</label>
              <input
                id="profile-firstName"
                type="text"
                required
                maxLength={100}
                value={profileForm.firstName}
                onChange={(e) => setProfileForm((f) => ({ ...f, firstName: e.target.value }))}
              />
            </div>
            <div className="auth-field">
              <label htmlFor="profile-lastName">Last name</label>
              <input
                id="profile-lastName"
                type="text"
                required
                maxLength={100}
                value={profileForm.lastName}
                onChange={(e) => setProfileForm((f) => ({ ...f, lastName: e.target.value }))}
              />
            </div>
          </div>
          <div className="auth-field">
            <label htmlFor="profile-email">Email</label>
            <input
              id="profile-email"
              type="email"
              required
              value={profileForm.email}
              onChange={(e) => setProfileForm((f) => ({ ...f, email: e.target.value }))}
            />
          </div>
          {profileStatus === 'error' && <p className="auth-error">{profileError}</p>}
          {profileStatus === 'saved' && <p className="profile-success">Profile updated.</p>}
          <button type="submit" className="primary-button" disabled={profileStatus === 'saving'}>
            {profileStatus === 'saving' ? 'Saving…' : 'Save changes'}
          </button>
        </form>
      </div>

      <div className="profile-card">
        <h3>Change password</h3>
        <form className="auth-form profile-form" onSubmit={handlePasswordSubmit}>
          <div className="auth-field">
            <label htmlFor="current-password">Current password</label>
            <input
              id="current-password"
              type="password"
              required
              autoComplete="current-password"
              value={passwordForm.currentPassword}
              onChange={(e) => setPasswordForm((f) => ({ ...f, currentPassword: e.target.value }))}
            />
          </div>
          <div className="auth-field">
            <label htmlFor="new-password">New password</label>
            <input
              id="new-password"
              type="password"
              required
              minLength={8}
              autoComplete="new-password"
              value={passwordForm.newPassword}
              onChange={(e) => setPasswordForm((f) => ({ ...f, newPassword: e.target.value }))}
            />
            <PasswordStrengthMeter password={passwordForm.newPassword} />
          </div>
          <div className="auth-field">
            <label htmlFor="confirm-new-password">Confirm new password</label>
            <input
              id="confirm-new-password"
              type="password"
              required
              autoComplete="new-password"
              value={passwordForm.confirmPassword}
              onChange={(e) => setPasswordForm((f) => ({ ...f, confirmPassword: e.target.value }))}
            />
          </div>
          {passwordStatus === 'error' && <p className="auth-error">{passwordError}</p>}
          {passwordStatus === 'saved' && <p className="profile-success">Password changed.</p>}
          <button type="submit" className="primary-button" disabled={passwordStatus === 'saving'}>
            {passwordStatus === 'saving' ? 'Saving…' : 'Update password'}
          </button>
        </form>
      </div>

      <div className="profile-card">
        <h3>Preferences</h3>
        <p className="profile-preference-label">Appearance</p>
        <div className="theme-switcher">
          {THEME_OPTIONS.map((option) => (
            <button
              key={option.value}
              type="button"
              className={`theme-option ${theme === option.value ? 'active' : ''}`}
              onClick={() => handleThemeChange(option.value)}
            >
              {option.label}
            </button>
          ))}
        </div>
      </div>

      <div className="profile-card">
        <h3>Policies</h3>
        <details className="policy-details">
          <summary>Privacy policy</summary>
          <p>
            TravelMatch is a student software-engineering project. Your quiz answers, saved
            favorites, and account details are stored to power the features you use (history,
            favorites, personalized matches) and are not shared with or sold to any third party.
          </p>
        </details>
        <details className="policy-details">
          <summary>Terms of service</summary>
          <p>
            TravelMatch recommends destinations based on quiz answers you provide; it does not
            book travel or handle payments. Recommendations are for inspiration only — always
            verify prices, availability, and travel requirements independently before booking.
          </p>
        </details>
      </div>
    </section>
  )
}

export default ProfilePage
