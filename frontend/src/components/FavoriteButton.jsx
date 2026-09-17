function FavoriteButton({ isFavorited, onToggle }) {
  return (
    <button
      type="button"
      className={`favorite-button ${isFavorited ? 'active' : ''}`}
      onClick={onToggle}
      aria-pressed={isFavorited}
      aria-label={isFavorited ? 'Remove from favorites' : 'Add to favorites'}
      title={isFavorited ? 'Remove from favorites' : 'Add to favorites'}
    >
      {isFavorited ? '♥' : '♡'}
    </button>
  )
}

export default FavoriteButton
