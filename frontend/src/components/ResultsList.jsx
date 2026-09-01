import DestinationCard from './DestinationCard'
import EmailCapture from './EmailCapture'

function ResultsList({ results, onRestart }) {
  return (
    <section className="results-section">
      <h2>✈️ Your recommended destinations</h2>
      <div className="results-grid">
        {results.map((result, index) => (
          <DestinationCard key={result.name} result={result} rank={index} />
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
