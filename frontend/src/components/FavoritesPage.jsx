import { useEffect, useState } from 'react'
import { getFavorites } from '../api/travelApi'
import { useFavorites } from '../hooks/useFavorites'
import ExploreDestinationCard from './ExploreDestinationCard'

function FavoritesPage({ token }) {
  const [status, setStatus] = useState('loading') // loading | loaded | error
  const [destinations, setDestinations] = useState([])
  const [errorMessage, setErrorMessage] = useState('')
  const { favoriteIds, toggleFavorite } = useFavorites(token)

  useEffect(() => {
    let cancelled = false

    getFavorites(token)
      .then((data) => {
        if (cancelled) return
        setDestinations(data)
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

  // Un-favoriting here should drop the card immediately rather than wait for
  // a refetch, so wrap the shared toggle to also update the local list.
  const handleToggle = (destinationId) => {
    toggleFavorite(destinationId)
    setDestinations((prev) => prev.filter((d) => d.id !== destinationId))
  }

  if (status === 'loading') {
    return <p className="status-message">Loading your favorites…</p>
  }

  if (status === 'error') {
    return (
      <div className="status-message error">
        <p>Couldn&apos;t load your favorites: {errorMessage}</p>
      </div>
    )
  }

  if (destinations.length === 0) {
    return (
      <section className="explore-section">
        <h2>Your favorites</h2>
        <p className="status-message">
          Nothing saved yet. Tap the ♡ on any suggestion to add it here.
        </p>
      </section>
    )
  }

  return (
    <section className="explore-section">
      <h2>Your favorites</h2>
      <div className="results-grid">
        {destinations.map((destination) => (
          <ExploreDestinationCard
            key={destination.id}
            destination={destination}
            isFavorited={favoriteIds.has(destination.id)}
            onToggleFavorite={handleToggle}
          />
        ))}
      </div>
    </section>
  )
}

export default FavoritesPage
