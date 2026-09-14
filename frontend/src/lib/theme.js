const THEME_KEY = 'travelmatch_theme'

export function getStoredTheme() {
  try {
    return localStorage.getItem(THEME_KEY) || 'system'
  } catch {
    return 'system'
  }
}

export function applyTheme(theme) {
  const root = document.documentElement
  if (theme === 'light' || theme === 'dark') {
    root.setAttribute('data-theme', theme)
  } else {
    root.removeAttribute('data-theme')
  }

  try {
    localStorage.setItem(THEME_KEY, theme)
  } catch {
    // Private browsing / storage disabled: the toggle still works for this
    // page load, it just won't be remembered next time.
  }
}
