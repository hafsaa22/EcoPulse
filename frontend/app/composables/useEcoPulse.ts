// Contrat de l'API Symfony (cf. backend/src/Controller/MetricsController.php)

export type Profile = 'fast' | 'medium' | 'slow'

export const PROFILES: Profile[] = ['fast', 'medium', 'slow']

export interface Measurement {
  id: number
  endpoint: Profile
  method: string
  statusCode: number
  durationMs: number
  responseSize: number
  createdAt: string
}

export interface EndpointStats {
  endpoint: Profile
  requests: number
  avgDurationMs: number
  minDurationMs: number
  maxDurationMs: number
  avgSizeBytes: number
  totalBytes: number
}

export interface StatsResponse {
  summary: { totalRequests: number, avgDurationMs: number, totalBytes: number }
  endpoints: EndpointStats[]
}

/**
 * Centralise l'état du dashboard : chargement des statistiques et des mesures,
 * déclenchement des endpoints de démonstration, gestion du chargement et des erreurs.
 */
export function useEcoPulse() {
  const stats = useFetch<StatsResponse>('/api/stats', { key: 'stats' })
  const metrics = useFetch<{ items: Measurement[] }>('/api/metrics', { key: 'metrics', query: { limit: 50 } })

  /** Endpoint en cours de déclenchement (null si aucun). */
  const triggering = ref<Profile | null>(null)
  const actionError = ref<string | null>(null)

  const loading = computed(() => stats.status.value === 'pending' || metrics.status.value === 'pending')

  const error = computed(() => {
    if (actionError.value) return actionError.value
    if (stats.error.value || metrics.error.value) {
      return 'Impossible de récupérer les mesures : l\'API Symfony est-elle démarrée ?'
    }
    return null
  })

  async function refresh() {
    actionError.value = null
    await Promise.all([stats.refresh(), metrics.refresh()])
  }

  /** Appelle un endpoint de démonstration (ce qui crée une mesure), puis recharge les données. */
  async function trigger(profile: Profile) {
    triggering.value = profile
    actionError.value = null
    try {
      await $fetch(`/api/demo/${profile}`)
      await Promise.all([stats.refresh(), metrics.refresh()])
    }
    catch {
      actionError.value = `L'appel à /api/demo/${profile} a échoué.`
    }
    finally {
      triggering.value = null
    }
  }

  return {
    summary: computed(() => stats.data.value?.summary ?? null),
    endpoints: computed(() => stats.data.value?.endpoints ?? []),
    measurements: computed(() => metrics.data.value?.items ?? []),
    loading,
    error,
    triggering,
    refresh,
    trigger,
  }
}
