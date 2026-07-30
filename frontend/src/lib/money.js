const formatter = new Intl.NumberFormat('de-AT', { style: 'currency', currency: 'EUR' })

export function formatCents(cents) {
  return cents === null || cents === undefined ? null : formatter.format(cents / 100)
}

export function eurosToCents(value) {
  if (value === null || value === undefined || String(value).trim() === '') return null
  const number = Number(String(value).replace(/\./g, '').replace(',', '.'))
  return Number.isNaN(number) ? null : Math.round(number * 100)
}

export function centsToEuros(cents) {
  return cents === null || cents === undefined ? '' : String(cents / 100).replace('.', ',')
}
