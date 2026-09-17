/**
 * Booking.com has no free public API for real listings (their Partner API
 * needs an approved affiliate account), so this just builds a deep link to
 * a prefilled search results page for the destination.
 */
export function bookingSearchUrl(name, country) {
  const query = encodeURIComponent(`${name}, ${country}`)
  return `https://www.booking.com/searchresults.html?ss=${query}`
}
