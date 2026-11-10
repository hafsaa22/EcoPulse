// Fonctions de formatage (auto-importées par Nuxt depuis app/utils).

const numberFormat = new Intl.NumberFormat('fr-FR', { maximumFractionDigits: 1 })

export function formatMs(value: number): string {
  return `${numberFormat.format(value)} ms`
}

export function formatBytes(bytes: number): string {
  if (bytes < 1024) return `${bytes} o`
  if (bytes < 1024 * 1024) return `${numberFormat.format(bytes / 1024)} Ko`
  return `${numberFormat.format(bytes / (1024 * 1024))} Mo`
}

export function formatNumber(value: number): string {
  return numberFormat.format(value)
}
