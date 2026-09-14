const COUNTRY_CODES = {
  Portugal: 'PT',
  Hungary: 'HU',
  France: 'FR',
  Greece: 'GR',
  'Czech Republic': 'CZ',
  Indonesia: 'ID',
  Thailand: 'TH',
  Japan: 'JP',
  'United Arab Emirates': 'AE',
  Iceland: 'IS',
  Netherlands: 'NL',
  Morocco: 'MA',
  'South Africa': 'ZA',
  Austria: 'AT',
  Mexico: 'MX',
  'United States': 'US',
  Vietnam: 'VN',
  Peru: 'PE',
  Georgia: 'GE',
  Spain: 'ES',
  Poland: 'PL',
  Brazil: 'BR',
  Tanzania: 'TZ',
  Colombia: 'CO',
  'New Zealand': 'NZ',
  Croatia: 'HR',
}

/**
 * Converts a two-letter ISO country code into its Unicode flag emoji by
 * mapping each letter to a regional indicator symbol - no image request
 * needed, renders natively on any modern OS/browser.
 */
export function countryFlag(country) {
  const code = COUNTRY_CODES[country]
  if (!code) return '🏳️'

  return [...code.toUpperCase()]
    .map((char) => String.fromCodePoint(127397 + char.charCodeAt(0)))
    .join('')
}
