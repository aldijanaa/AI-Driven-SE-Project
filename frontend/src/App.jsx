import { useEffect, useState } from 'react'
import AuthPage from './components/AuthPage'
import ExplorePage from './components/ExplorePage'
import FavoritesPage from './components/FavoritesPage'
import HistoryPage from './components/HistoryPage'
import Hero from './components/Hero'
import NavBar from './components/NavBar'
import ProfilePage from './components/ProfilePage'
import Quiz from './components/Quiz'
import ResetPasswordPage from './components/ResetPasswordPage'
import ResultsList from './components/ResultsList'
import { fetchCurrentUser, logout as logoutRequest } from './api/authApi'
import { getMatches } from './api/travelApi'
import { applyTheme, getStoredTheme } from './lib/theme'
import './App.css'

const TOKEN_STORAGE_KEY = 'travelmatch_token'

function App() {
  const [resetToken, setResetToken] = useState(
    () => new URLSearchParams(window.location.search).get('resetToken')
  )

  const [authStage, setAuthStage] = useState(() =>
    localStorage.getItem(TOKEN_STORAGE_KEY) ? 'checking' : 'signedOut'
  )
  const [user, setUser] = useState(null)
  const [token, setToken] = useState(null)

  const [view, setView] = useState('home') // home | explore | history | favorites | profile
  const [stage, setStage] = useState('landing') // landing | quiz | loading | results | error
  const [results, setResults] = useState([])
  const [errorMessage, setErrorMessage] = useState('')

  useEffect(() => {
    applyTheme(getStoredTheme())
  }, [])

  useEffect(() => {
    const storedToken = localStorage.getItem(TOKEN_STORAGE_KEY)
    if (!storedToken) return

    fetchCurrentUser(storedToken)
      .then(({ user: currentUser }) => {
        setUser(currentUser)
        setToken(storedToken)
        setAuthStage('signedIn')
      })
      .catch(() => {
        localStorage.removeItem(TOKEN_STORAGE_KEY)
        setAuthStage('signedOut')
      })
  }, [])

  const handleAuthenticated = (authUser, authToken) => {
    localStorage.setItem(TOKEN_STORAGE_KEY, authToken)
    setUser(authUser)
    setToken(authToken)
    setAuthStage('signedIn')
  }

  const handleLogout = () => {
    if (token) logoutRequest(token)
    localStorage.removeItem(TOKEN_STORAGE_KEY)
    setUser(null)
    setToken(null)
    setAuthStage('signedOut')
    setView('home')
    setStage('landing')
    setResults([])
  }

  const handleComplete = async (answers) => {
    setStage('loading')
    try {
      const matches = await getMatches(answers, token)
      setResults(matches)
      setStage('results')
    } catch (err) {
      setErrorMessage(err.message)
      setStage('error')
    }
  }

  const restart = () => {
    setResults([])
    setStage('landing')
  }

  const handleResetDone = () => {
    setResetToken(null)
    window.history.replaceState({}, '', window.location.pathname)
  }

  if (resetToken) {
    return (
      <main className="app-shell">
        <ResetPasswordPage token={resetToken} onDone={handleResetDone} />
      </main>
    )
  }

  if (authStage === 'checking') {
    return (
      <main className="app-shell">
        <p className="status-message">Loading…</p>
      </main>
    )
  }

  if (authStage === 'signedOut') {
    return (
      <main className="app-shell">
        <AuthPage onAuthenticated={handleAuthenticated} />
      </main>
    )
  }

  return (
    <main className="app-shell">
      <NavBar firstName={user.firstName} view={view} onNavigate={setView} onLogout={handleLogout} />

      {view === 'explore' && <ExplorePage token={token} />}

      {view === 'history' && <HistoryPage token={token} />}

      {view === 'favorites' && <FavoritesPage token={token} />}

      {view === 'profile' && <ProfilePage user={user} token={token} onUserUpdated={setUser} />}

      {view === 'home' && (
        <>
          {stage === 'landing' && <Hero onStart={() => setStage('quiz')} />}
          {stage === 'quiz' && <Quiz onComplete={handleComplete} />}
          {stage === 'loading' && <p className="status-message">Finding your matches…</p>}
          {stage === 'error' && (
            <div className="status-message error">
              <p>Something went wrong: {errorMessage}</p>
              <button type="button" className="secondary-button" onClick={restart}>
                Try again
              </button>
            </div>
          )}
          {stage === 'results' && <ResultsList results={results} onRestart={restart} token={token} />}
        </>
      )}
    </main>
  )
}

export default App
