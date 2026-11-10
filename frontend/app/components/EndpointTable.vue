<script setup lang="ts">
import type { EndpointStats } from '~/composables/useEcoPulse'

const props = defineProps<{ endpoints: EndpointStats[] }>()

// Valeurs dérivées : recalculées automatiquement quand les statistiques changent.
const slowest = computed(() => maxBy(props.endpoints, e => e.avgDurationMs))
const largest = computed(() => maxBy(props.endpoints, e => e.avgSizeBytes))

function maxBy(items: EndpointStats[], pick: (e: EndpointStats) => number): string | null {
  if (items.length < 2) return null
  return items.reduce((best, e) => (pick(e) > pick(best) ? e : best)).endpoint
}
</script>

<template>
  <div class="table-wrapper">
    <table v-if="endpoints.length" class="endpoint-table">
      <thead>
        <tr>
          <th scope="col">Endpoint</th>
          <th scope="col" class="num">Requêtes</th>
          <th scope="col" class="num">Temps moyen</th>
          <th scope="col" class="num">Min / Max</th>
          <th scope="col" class="num">Taille moyenne</th>
          <th scope="col" class="num">Volume total</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="e in endpoints" :key="e.endpoint">
          <th scope="row">
            <span class="swatch" :style="{ background: `var(--series-${e.endpoint})` }" aria-hidden="true" />
            <code>GET /api/demo/{{ e.endpoint }}</code>
            <span v-if="e.endpoint === slowest" class="badge">Le plus lent</span>
            <span v-if="e.endpoint === largest" class="badge">Le plus volumineux</span>
          </th>
          <td class="num">{{ formatNumber(e.requests) }}</td>
          <td class="num strong">{{ formatMs(e.avgDurationMs) }}</td>
          <td class="num muted">{{ formatMs(e.minDurationMs) }} / {{ formatMs(e.maxDurationMs) }}</td>
          <td class="num">{{ formatBytes(e.avgSizeBytes) }}</td>
          <td class="num">{{ formatBytes(e.totalBytes) }}</td>
        </tr>
      </tbody>
    </table>
    <p v-else class="empty">Aucune mesure pour l'instant : déclenchez un endpoint ci-dessus.</p>
  </div>
</template>

<style scoped>
.table-wrapper {
  overflow-x: auto;
}

.endpoint-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.9375rem;
}

th,
td {
  padding: 0.75rem 0.75rem;
  text-align: left;
  border-bottom: 1px solid var(--border);
  white-space: nowrap;
}

thead th {
  font-size: 0.8125rem;
  font-weight: 550;
  color: var(--text-secondary);
}

tbody th {
  font-weight: 500;
}

tbody tr:last-child th,
tbody tr:last-child td {
  border-bottom: none;
}

.num {
  text-align: right;
  font-variant-numeric: tabular-nums;
}

.strong {
  font-weight: 600;
}

.muted {
  color: var(--text-muted);
}

.swatch {
  display: inline-block;
  width: 0.625rem;
  height: 0.625rem;
  border-radius: 2px;
  margin-right: 0.5rem;
}

.badge {
  margin-left: 0.5rem;
  padding: 0.1rem 0.5rem;
  border-radius: 999px;
  font-size: 0.75rem;
  font-weight: 500;
  background: var(--surface-2);
  color: var(--text-secondary);
  border: 1px solid var(--border);
}

.empty {
  margin: 0;
  padding: 1.5rem 0;
  color: var(--text-muted);
}
</style>
