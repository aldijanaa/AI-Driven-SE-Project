import { useFavorites } from '../hooks/useFavorites'
import DestinationCard from './DestinationCard'
import EmailCapture from './EmailCapture'

function ResultsList({ results, onRestart, token }) {
  const { favoriteIds, toggleFavorite } = useFavorites(token)

  return (
    <section className="results-section">
      <h2>✈️ Your recommended destinations</h2>
      <div className="results-grid">
        {results.map((result, index) => (
          <DestinationCard
            key={result.name}
            result={result}
            rank={index}
            isFavorited={result.id != null && favoriteIds.has(result.id)}
            onToggleFavorite={toggleFavorite}
          />
        ))}
      </div>
      <EmailCapture results={results} />
      <button type="button" className="secondary-button" onClick={onRestart}>
        Retake the quiz
      </button>
    </section>
  )
}

export default ResultsList
