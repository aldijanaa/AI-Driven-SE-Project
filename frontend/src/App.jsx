import { useState } from 'react'
import Hero from './components/Hero'
import Quiz from './components/Quiz'
import ResultsList from './components/ResultsList'
import { getMatches } from './api/travelApi'
import './App.css'

function App() {
  const [stage, setStage] = useState('landing') // landing | quiz | loading | results | error
  const [results, setResults] = useState([])
  const [errorMessage, setErrorMessage] = useState('')

  const handleComplete = async (answers) => {
    setStage('loading')
    try {
      const matches = await getMatches(answers)
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

  return (
    <main className="app-shell">
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
      {stage === 'results' && <ResultsList results={results} onRestart={restart} />}
    </main>
  )
}

export default App
