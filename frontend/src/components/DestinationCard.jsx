const MEDALS = ['🥇', '🥈', '🥉']

function DestinationCard({ result, rank }) {
  return (
    <article className="destination-card">
      <div className="destination-header">
        <span className="medal">{MEDALS[rank] ?? '📍'}</span>
        <div>
          <h3>
            {result.name}, {result.country}
          </h3>
          <p className="match-score">{result.match}% match</p>
        </div>
      </div>

      <p className="destination-description">{result.description}</p>

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
    </article>
  )
}

export default DestinationCard
