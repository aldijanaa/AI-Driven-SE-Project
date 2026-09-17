import { useEffect, useRef, useState } from 'react'

function NavBar({ firstName, view, onNavigate, onLogout }) {
  const [menuOpen, setMenuOpen] = useState(false)
  const menuRef = useRef(null)

  useEffect(() => {
    if (!menuOpen) return

    const handleClickOutside = (e) => {
      if (menuRef.current && !menuRef.current.contains(e.target)) {
        setMenuOpen(false)
      }
    }

    document.addEventListener('mousedown', handleClickOutside)
    return () => document.removeEventListener('mousedown', handleClickOutside)
  }, [menuOpen])

  const goTo = (nextView) => {
    onNavigate(nextView)
    setMenuOpen(false)
  }

  return (
    <nav className="app-nav">
      <button type="button" className="app-nav-brand" onClick={() => onNavigate('home')}>
        ✈️ TravelMatch
      </button>

      <div className="app-nav-links">
        <button
          type="button"
          className={`app-nav-link ${view === 'home' ? 'active' : ''}`}
          onClick={() => onNavigate('home')}
        >
          Home
        </button>
        <button
          type="button"
          className={`app-nav-link ${view === 'explore' ? 'active' : ''}`}
          onClick={() => onNavigate('explore')}
        >
          Explore
        </button>
        <button
          type="button"
          className={`app-nav-link ${view === 'history' ? 'active' : ''}`}
          onClick={() => onNavigate('history')}
        >
          History
        </button>
      </div>

      <div className="app-nav-profile" ref={menuRef}>
        <button
          type="button"
          className="app-nav-profile-trigger"
          onClick={() => setMenuOpen((open) => !open)}
          aria-expanded={menuOpen}
          aria-haspopup="menu"
        >
          Hi, {firstName} <span className="app-nav-caret">▾</span>
        </button>

        {menuOpen && (
          <div className="app-nav-dropdown" role="menu">
            <button
              type="button"
              role="menuitem"
              className={`app-nav-dropdown-item ${view === 'profile' ? 'active' : ''}`}
              onClick={() => goTo('profile')}
            >
              👤 Profile
            </button>
            <button
              type="button"
              role="menuitem"
              className={`app-nav-dropdown-item ${view === 'favorites' ? 'active' : ''}`}
              onClick={() => goTo('favorites')}
            >
              ♥ Favorites
            </button>
            <button
              type="button"
              role="menuitem"
              className="app-nav-dropdown-item"
              onClick={() => {
                setMenuOpen(false)
                onLogout()
              }}
            >
              Log out
            </button>
          </div>
        )}
      </div>
    </nav>
  )
}

export default NavBar
