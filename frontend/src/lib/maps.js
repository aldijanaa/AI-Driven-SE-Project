/**
 * Deep link to a Google Maps search for the destination - no API key
 * needed, same "smart search link" pattern as the Booking.com link.
 */
export function mapSearchUrl(name, country) {
  const query = encodeURIComponent(`${name}, ${country}`)
  return `https://www.google.com/maps/search/?api=1&query=${query}`
}
