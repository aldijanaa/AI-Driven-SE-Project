import { useEffect, useState } from 'react'
import { deleteHistoryEntry, getHistory } from '../api/travelApi'
import { useFavorites } from '../hooks/useFavorites'
import DestinationCard from './DestinationCard'

function formatDate(isoString) {
  return new Date(isoString).toLocaleDateString(undefined, {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
  })
}

function HistoryPage({ token }) {
  const [status, setStatus] = useState('loading') // loading | loaded | error
  const [submissions, setSubmissions] = useState([])
  const [errorMessage, setErrorMessage] = useState('')
  const [deletingId, setDeletingId] = useState(null)
  const [deleteError, setDeleteError] = useState('')
  const { favoriteIds, toggleFavorite } = useFavorites(token)

  useEffect(() => {
    let cancelled = false

    getHistory(token)
      .then((data) => {
        if (cancelled) return
        setSubmissions(data)
        setStatus('loaded')
      })
      .catch((err) => {
        if (cancelled) return
        setErrorMessage(err.message)
        setStatus('error')
      })

    return () => {
      cancelled = true
    }
  }, [token])

  async function handleDelete(submissionId) {
    if (!window.confirm('Delete this quiz result from your history?')) return

    setDeleteError('')
    setDeletingId(submissionId)

    try {
      await deleteHistoryEntry(token, submissionId)
      setSubmissions((prev) => prev.filter((submission) => submission.id !== submissionId))
    } catch (err) {
      setDeleteError(err.message)
    } finally {
      setDeletingId(null)
    }
  }

  if (status === 'loading') {
    return <p className="status-message">Loading your history…</p>
  }

  if (status === 'error') {
    return (
      <div className="status-message error">
        <p>Couldn&apos;t load your history: {errorMessage}</p>
      </div>
    )
  }

  if (submissions.length === 0) {
    return (
      <section className="history-section">
        <h2>Your quiz history</h2>
        <p className="status-message">You haven&apos;t taken the quiz yet. Your past results will show up here.</p>
      </section>
    )
  }

  return (
    <section className="history-section">
      <h2>Your quiz history</h2>
      {deleteError && <p className="status-message error">Couldn&apos;t delete that entry: {deleteError}</p>}
      <div className="history-list">
        {submissions.map((submission) => (
          <article key={submission.id} className="history-entry">
            <div className="history-entry-header">
              <span className="history-entry-date">{formatDate(submission.createdAt)}</span>
              <div className="history-entry-tags">
                {submission.interests.map((interest) => (
                  <span key={interest} className="history-tag">
                    {interest}
                  </span>
                ))}
                {submission.style && <span className="history-tag">{submission.style}</span>}
                {submission.budgetLevel && <span className="history-tag">{submission.budgetLevel}</span>}
              </div>
              <button
                type="button"
                className="history-delete-button"
                onClick={() => handleDelete(submission.id)}
                disabled={deletingId === submission.id}
              >
                {deletingId === submission.id ? 'Deleting…' : 'Delete'}
              </button>
            </div>
            <div className="results-grid">
              {submission.results.map((result, index) => (
                <DestinationCard
                  key={result.name}
                  result={result}
                  rank={index}
                  isFavorited={result.id != null && favoriteIds.has(result.id)}
                  onToggleFavorite={toggleFavorite}
                />
              ))}
            </div>
          </article>
        ))}
      </div>
    </section>
  )
}

export default HistoryPage
