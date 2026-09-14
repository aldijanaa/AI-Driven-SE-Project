import { useEffect, useState } from 'react'
import { getExploreDestinations, searchDestinations } from '../api/travelApi'
import { useFavorites } from '../hooks/useFavorites'
import ExploreDestinationCard from './ExploreDestinationCard'

const SORT_OPTIONS = [
  { value: 'popular', label: 'Most popular' },
  { value: 'cheapest', label: 'Cheapest first' },
  { value: 'expensive', label: 'Most expensive first' },
  { value: 'newest', label: 'Newest' },
]

function averageBudget(destination) {
  return (destination.budget_min + destination.budget_max) / 2
}

function sortDestinations(destinations, sortBy) {
  const sorted = [...destinations]

  switch (sortBy) {
    case 'cheapest':
      return sorted.sort((a, b) => averageBudget(a) - averageBudget(b))
    case 'expensive':
      return sorted.sort((a, b) => averageBudget(b) - averageBudget(a))
    case 'newest':
      return sorted.sort((a, b) => b.id - a.id)
    case 'popular':
    default:
      return sorted.sort((a, b) => b.match_count - a.match_count || a.name.localeCompare(b.name))
  }
}

function ExplorePage({ token }) {
  const [status, setStatus] = useState('loading') // loading | loaded | error
  const [destinations, setDestinations] = useState([])
  const [errorMessage, setErrorMessage] = useState('')
  const [countryFilter, setCountryFilter] = useState('all')
  const [budgetInput, setBudgetInput] = useState('')
  const [sortBy, setSortBy] = useState('popular')
  const { favoriteIds, toggleFavorite } = useFavorites(token)

  const [searchInput, setSearchInput] = useState('')
  const [searchQuery, setSearchQuery] = useState('')
  const [searchStatus, setSearchStatus] = useState('idle') // idle | loading | done | error
  const [searchResults, setSearchResults] = useState([])
  const [searchError, setSearchError] = useState('')

  const handleSearchSubmit = async (e) => {
    e.preventDefault()
    const query = searchInput.trim()
    if (!query) return

    setSearchStatus('loading')
    setSearchError('')
    setSearchQuery(query)

    try {
      const results = await searchDestinations(query)
      setSearchResults(results)
      setSearchStatus('done')
    } catch (err) {
      setSearchError(err.message)
      setSearchStatus('error')
    }
  }

  const clearSearch = () => {
    setSearchInput('')
    setSearchQuery('')
    setSearchStatus('idle')
    setSearchResults([])
    setSearchError('')
  }

  const isSearchActive = searchStatus !== 'idle'

  useEffect(() => {
    let cancelled = false

    getExploreDestinations()
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
  }, [])

  if (status === 'loading') {
    return <p className="status-message">Loading destinations…</p>
  }

  if (status === 'error') {
    return (
      <div className="status-message error">
        <p>Couldn&apos;t load destinations: {errorMessage}</p>
      </div>
    )
  }

  const countries = [...new Set(destinations.map((d) => d.country))].sort()
  const budgetValue = budgetInput === '' ? null : Number(budgetInput)

  const filtered = destinations
    .filter((d) => countryFilter === 'all' || d.country === countryFilter)
    .filter((d) => budgetValue === null || (budgetValue >= d.budget_min && budgetValue <= d.budget_max))

  const visible = sortDestinations(filtered, sortBy)

  return (
    <section className="explore-section">
      <h2>Explore all destinations</h2>
      <p className="subtitle">
        The full catalog, ranked by how many quiz-takers were matched with each one.
      </p>

      <form className="explore-search" onSubmit={handleSearchSubmit}>
        <input
          type="text"
          className="explore-search-input"
          placeholder="Describe a trip: “cheap beach town with good nightlife”…"
          value={searchInput}
          onChange={(e) => setSearchInput(e.target.value)}
        />
        <button type="submit" className="secondary-button" disabled={searchStatus === 'loading' || !searchInput.trim()}>
          {searchStatus === 'loading' ? 'Searching…' : 'Search'}
        </button>
        {isSearchActive && (
          <button type="button" className="link-button" onClick={clearSearch}>
            Clear
          </button>
        )}
      </form>
      <p className="explore-search-hint">
        Semantic search over destination descriptions (via embeddings) — separate from the filters below.
      </p>

      {isSearchActive ? (
        <>
          {searchStatus === 'error' && (
            <p className="status-message error">Search failed: {searchError}</p>
          )}
          {searchStatus === 'done' && (
            <>
              <p className="subtitle">
                {searchResults.length > 0
                  ? `Best semantic matches for “${searchQuery}”:`
                  : `No good semantic matches for “${searchQuery}”.`}
              </p>
              {searchResults.length > 0 && (
                <div className="results-grid">
                  {searchResults.map((destination) => (
                    <ExploreDestinationCard
                      key={destination.id}
                      destination={destination}
                      isFavorited={favoriteIds.has(destination.id)}
                      onToggleFavorite={toggleFavorite}
                    />
                  ))}
                </div>
              )}
            </>
          )}
        </>
      ) : (
        <>
          <div className="explore-controls">
            <label className="explore-select-label">
              Country
              <select
                className="explore-select"
                value={countryFilter}
                onChange={(e) => setCountryFilter(e.target.value)}
              >
                <option value="all">All countries</option>
                {countries.map((country) => (
                  <option key={country} value={country}>
                    {country}
                  </option>
                ))}
              </select>
            </label>

            <label className="explore-select-label">
              Your budget (€)
              <input
                type="number"
                min="0"
                className="explore-select"
                placeholder="Any budget"
                value={budgetInput}
                onChange={(e) => setBudgetInput(e.target.value)}
              />
            </label>

            <label className="explore-select-label">
              Sort by
              <select className="explore-select" value={sortBy} onChange={(e) => setSortBy(e.target.value)}>
                {SORT_OPTIONS.map((option) => (
                  <option key={option.value} value={option.value}>
                    {option.label}
                  </option>
                ))}
              </select>
            </label>
          </div>

          {visible.length === 0 ? (
            <p className="status-message">No destinations match these filters.</p>
          ) : (
            <div className="results-grid">
              {visible.map((destination) => (
                <ExploreDestinationCard
                  key={destination.id}
                  destination={destination}
                  isFavorited={favoriteIds.has(destination.id)}
                  onToggleFavorite={toggleFavorite}
                />
              ))}
            </div>
          )}
        </>
      )}
    </section>
  )
}

export default ExplorePage
