import { useState } from 'react'
import { countryFlag } from '../lib/countryFlags'
import { bookingSearchUrl } from '../lib/booking'
import { mapSearchUrl } from '../lib/maps'
import FavoriteButton from './FavoriteButton'
import DestinationExpandPanel from './DestinationExpandPanel'

const MEDALS = ['🥇', '🥈', '🥉']

function DestinationCard({ result, rank, isFavorited, onToggleFavorite }) {
  const [expanded, setExpanded] = useState(false)

  return (
    <article className="destination-card">
      <div className="destination-photo">
        {result.photo_url ? (
          <img src={result.photo_url} alt={result.name} loading="lazy" />
        ) : (
          <div className="destination-photo-placeholder">🧳</div>
        )}
      </div>

      <div className="destination-card-body">
        <div className="destination-header">
          <span className="medal">{MEDALS[rank] ?? '📍'}</span>
          <div>
            <h3>
              {result.name}
              <span className="destination-flag" title={result.country}>
                {countryFlag(result.country)}
              </span>
            </h3>
            <p className="destination-country">{result.country}</p>
            <p className="match-score">{result.match}% match</p>
          </div>
          {result.id != null && onToggleFavorite && (
            <FavoriteButton isFavorited={isFavorited} onToggle={() => onToggleFavorite(result.id)} />
          )}
        </div>

        <p className="destination-description">{result.description}</p>
        {result.description_source === 'gemini' && (
          <p className="destination-source-note">
            ✨ AI-generated, grounded in curated facts
            {result.wikipedia_url && (
              <>
                {' '}
                +{' '}
                <a href={result.wikipedia_url} target="_blank" rel="noreferrer">
                  Wikipedia ↗
                </a>
              </>
            )}
          </p>
        )}

        <dl className="destination-facts">
          <div>
            <dt>Best time</dt>
            <dd>{result.best_time}</dd>
          </div>
          <div>
            <dt>Estimated budget</dt>
            <dd>{result.budget_estimate}</dd>
          </div>
          <div>
            <dt>Recommended stay</dt>
            <dd>{result.recommended_stay}</dd>
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
            extract={result.wikipedia_extract}
            wikipediaUrl={result.wikipedia_url}
            bookingUrl={bookingSearchUrl(result.name, result.country)}
            mapUrl={mapSearchUrl(result.name, result.country)}
          />
        )}
      </div>
    </article>
  )
}

export default DestinationCard
