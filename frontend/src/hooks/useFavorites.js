import { useCallback, useEffect, useState } from 'react'
import { getFavorites, toggleFavorite as toggleFavoriteRequest } from '../api/travelApi'

function flipped(set, id) {
  const next = new Set(set)
  if (next.has(id)) {
    next.delete(id)
  } else {
    next.add(id)
  }
  return next
}

// Shared by every page that shows a heart button (results, history, explore,
// favorites): tracks which destination ids the user has saved, and toggles
// with an optimistic update that reverts if the request fails.
export function useFavorites(token) {
  const [favoriteIds, setFavoriteIds] = useState(new Set())

  useEffect(() => {
    if (!token) return
    let cancelled = false

    getFavorites(token)
      .then((destinations) => {
        if (cancelled) return
        setFavoriteIds(new Set(destinations.map((d) => d.id)))
      })
      .catch(() => {})

    return () => {
      cancelled = true
    }
  }, [token])

  const toggleFavorite = useCallback(
    async (destinationId) => {
      if (!token || destinationId == null) return

      setFavoriteIds((prev) => flipped(prev, destinationId))

      try {
        await toggleFavoriteRequest(token, destinationId)
      } catch {
        setFavoriteIds((prev) => flipped(prev, destinationId))
      }
    },
    [token]
  )

  return { favoriteIds, toggleFavorite }
}
