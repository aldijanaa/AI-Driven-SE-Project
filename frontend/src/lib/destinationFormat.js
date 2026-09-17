const MONTH_NAMES = [
  '', 'January', 'February', 'March', 'April', 'May', 'June',
  'July', 'August', 'September', 'October', 'November', 'December',
]

export function formatBestTime({ best_month_start, best_month_end }) {
  const start = MONTH_NAMES[best_month_start]
  const end = MONTH_NAMES[best_month_end]
  return start === end ? start : `${start}–${end}`
}

export function formatBudget({ budget_min, budget_max }) {
  return `€${budget_min}–${budget_max}`
}

export function formatStay({ stay_min, stay_max }) {
  return `${stay_min}–${stay_max} days`
}
