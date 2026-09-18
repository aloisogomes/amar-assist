export function formatBrl(cents: number): string {
  return (cents / 100).toLocaleString('pt-BR', {
    style: 'currency',
    currency: 'BRL',
  })
}

export function parseReaisToCents(value: string): number | null {
  const trimmed = value.trim()

  if (trimmed === '') {
    return null
  }

  const normalized = trimmed.replace(/\./g, '').replace(',', '.')

  if (!/^-?\d+(\.\d+)?$/.test(normalized)) {
    return null
  }

  const amount = Number.parseFloat(normalized)

  if (Number.isNaN(amount)) {
    return null
  }

  return Math.round(amount * 100)
}

export function reaisToCents(value: string): number {
  return parseReaisToCents(value) ?? 0
}

export function centsToReaisInput(cents: number): string {
  return (cents / 100).toFixed(2).replace('.', ',')
}
