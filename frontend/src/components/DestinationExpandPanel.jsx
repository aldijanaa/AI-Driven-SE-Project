function DestinationExpandPanel({ extract, wikipediaUrl, bookingUrl, mapUrl }) {
  return (
    <div className="destination-expand-panel">
      {extract && <p className="destination-extract">{extract}</p>}

      <div className="destination-expand-links">
        {wikipediaUrl && (
          <a href={wikipediaUrl} target="_blank" rel="noreferrer" className="destination-expand-link">
            Wikipedia ↗
          </a>
        )}
        <a href={mapUrl} target="_blank" rel="noreferrer" className="destination-expand-link">
          View on map ↗
        </a>
        <a href={bookingUrl} target="_blank" rel="noreferrer" className="destination-expand-link">
          Hotels on Booking.com ↗
        </a>
      </div>
    </div>
  )
}

export default DestinationExpandPanel
