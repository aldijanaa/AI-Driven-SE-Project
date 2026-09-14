import { useState } from 'react'
import { formatBestTime, formatBudget, formatStay } from '../lib/destinationFormat'
import { countryFlag } from '../lib/countryFlags'
import { bookingSearchUrl } from '../lib/booking'
import { mapSearchUrl } from '../lib/maps'
import FavoriteButton from './FavoriteButton'
import DestinationExpandPanel from './DestinationExpandPanel'

function popularityLabel(matchCount) {
  if (!matchCount) return null
  if (matchCount === 1) return 'Matched by 1 traveler'
  return `Matched by ${matchCount} travelers`
}

function ExploreDestinationCard({ destination, isFavorited, onToggleFavorite }) {
  const label = popularityLabel(destination.match_count)
  const [expanded, setExpanded] = useState(false)

  return (
    <article className="destination-card">
      <div className="destination-photo">
        {destination.photo_url ? (
          <img src={destination.photo_url} alt={destination.name} loading="lazy" />
        ) : (
          <div className="destination-photo-placeholder">🧳</div>
        )}
      </div>

      <div className="destination-card-body">
        <div className="destination-header">
          <span className="medal">📍</span>
          <div>
            <h3>
              {destination.name}
              <span className="destination-flag" title={destination.country}>
                {countryFlag(destination.country)}
              </span>
            </h3>
            <p className="destination-country">{destination.country}</p>
            {label && <p className="match-score">🔥 {label}</p>}
          </div>
          {onToggleFavorite && (
            <FavoriteButton isFavorited={isFavorited} onToggle={() => onToggleFavorite(destination.id)} />
          )}
        </div>

        <div className="destination-vibe-tags">
          {destination.vibe_tags.map((tag) => (
            <span key={tag} className="history-tag">
              {tag.replace(/_/g, ' ')}
            </span>
          ))}
        </div>

        <dl className="destination-facts">
          <div>
            <dt>Best time</dt>
            <dd>{formatBestTime(destination)}</dd>
          </div>
          <div>
            <dt>Estimated budget</dt>
            <dd>{formatBudget(destination)}</dd>
          </div>
          <div>
            <dt>Recommended stay</dt>
            <dd>{formatStay(destination)}</dd>
          </div>
        </dl>

        <button
          type="button"
          className="destination-expand-toggle"
          onClick={() => setExpanded((v) => !v)}
        >
          {expanded ? 'Show less ▴' : 'Learn more ▾'}
        </button>

        {expanded && (
          <DestinationExpandPanel
            extract={destination.wikipedia_extract}
            wikipediaUrl={destination.wikipedia_url}
            bookingUrl={bookingSearchUrl(destination.name, destination.country)}
            mapUrl={mapSearchUrl(destination.name, destination.country)}
          />
        )}
      </div>
    </article>
  )
}

export default ExploreDestinationCard
