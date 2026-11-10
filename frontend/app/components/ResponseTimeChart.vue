<script setup lang="ts">
import { PROFILES, type Measurement } from '~/composables/useEcoPulse'

// Mesures reçues de la plus récente à la plus ancienne (ordre de l'API).
const props = defineProps<{ measurements: Measurement[] }>()

// Dimensions du repère SVG (le SVG est ensuite redimensionné à 100 % de la largeur).
const WIDTH = 720
const HEIGHT = 260
const PAD = { top: 12, right: 8, bottom: 24, left: 56 }
const plotW = WIDTH - PAD.left - PAD.right
const plotH = HEIGHT - PAD.top - PAD.bottom

/** Ordre chronologique : la mesure la plus ancienne à gauche. */
const points = computed(() => [...props.measurements].reverse())

/** Maximum de l'axe Y arrondi à une valeur "ronde" (100, 200, 500, 1000…). */
const yMax = computed(() => {
  const max = Math.max(...points.value.map(m => m.durationMs), 1)
  const step = [50, 100, 200, 250, 500, 1000, 2000, 5000].find(s => s * 4 >= max) ?? 10000
  return Math.ceil(max / step) * step
})

const ticks = computed(() => [0, 0.25, 0.5, 0.75, 1].map(r => r * yMax.value))

const y = (ms: number) => PAD.top + plotH - (ms / yMax.value) * plotH

const slot = computed(() => plotW / Math.max(points.value.length, 1))
// 2 px d'espace entre barres, largeur plafonnée pour rester lisible avec peu de mesures.
const barW = computed(() => Math.max(Math.min(slot.value - 2, 28), 1))

/** Barre à sommet arrondi (rayon 4 px max), posée sur la ligne de base. */
function barPath(i: number, ms: number): string {
  const x = PAD.left + i * slot.value + (slot.value - barW.value) / 2
  const top = y(ms)
  const base = PAD.top + plotH
  const r = Math.min(4, barW.value / 2, base - top)
  return `M${x},${base} V${top + r} Q${x},${top} ${x + r},${top} H${x + barW.value - r} Q${x + barW.value},${top} ${x + barW.value},${top + r} V${base} Z`
}

// Survol : index de la barre survolée (réactif).
const hovered = ref<number | null>(null)
const hoveredPoint = computed(() => (hovered.value === null ? null : points.value[hovered.value] ?? null))
const tooltipStyle = computed(() => {
  if (hovered.value === null) return {}
  const center = PAD.left + (hovered.value + 0.5) * slot.value
  // Borné pour que l'infobulle ne déborde pas aux extrémités du graphique.
  const percent = Math.min(Math.max((center / WIDTH) * 100, 15), 85)
  return { left: `${percent}%` }
})

const timeFormat = new Intl.DateTimeFormat('fr-FR', { timeStyle: 'medium' })
</script>

<template>
  <figure class="chart">
    <ul class="legend" aria-label="Légende">
      <li v-for="p in PROFILES" :key="p">
        <span class="swatch" :style="{ background: `var(--series-${p})` }" aria-hidden="true" />{{ p }}
      </li>
    </ul>

    <div v-if="points.length" class="chart__canvas" @mouseleave="hovered = null">
      <svg
        :viewBox="`0 0 ${WIDTH} ${HEIGHT}`"
        role="img"
        :aria-label="`Temps de traitement serveur des ${points.length} dernières requêtes`"
      >
        <!-- Grille horizontale et graduations de l'axe Y -->
        <g v-for="t in ticks" :key="t">
          <line :x1="PAD.left" :x2="WIDTH - PAD.right" :y1="y(t)" :y2="y(t)" class="grid" />
          <text :x="PAD.left - 8" :y="y(t)" class="axis-label" text-anchor="end" dominant-baseline="middle">
            {{ formatNumber(t) }} ms
          </text>
        </g>

        <!-- Une barre par mesure, colorée selon l'endpoint -->
        <path
          v-for="(m, i) in points"
          :key="m.id"
          :d="barPath(i, m.durationMs)"
          :fill="`var(--series-${m.endpoint})`"
          :opacity="hovered === null || hovered === i ? 1 : 0.35"
        />

        <!-- Zones de survol : toute la hauteur de la colonne, plus larges que la barre -->
        <rect
          v-for="(m, i) in points"
          :key="`hit-${m.id}`"
          :x="PAD.left + i * slot"
          :y="PAD.top"
          :width="slot"
          :height="plotH"
          fill="transparent"
          @mouseenter="hovered = i"
        />

        <text :x="PAD.left" :y="HEIGHT - 4" class="axis-label">plus ancienne</text>
        <text :x="WIDTH - PAD.right" :y="HEIGHT - 4" class="axis-label" text-anchor="end">plus récente</text>
      </svg>

      <div v-if="hoveredPoint" class="tooltip" :style="tooltipStyle" role="status">
        <strong>GET /api/demo/{{ hoveredPoint.endpoint }}</strong>
        <span>{{ formatMs(hoveredPoint.durationMs) }} · {{ formatBytes(hoveredPoint.responseSize) }}</span>
        <span class="tooltip__muted">HTTP {{ hoveredPoint.statusCode }} · {{ timeFormat.format(new Date(hoveredPoint.createdAt)) }}</span>
      </div>
    </div>

    <p v-else class="empty">Le graphique apparaîtra après les premières mesures.</p>

    <figcaption>
      Temps de traitement côté serveur des {{ points.length }} dernières requêtes (hors latence réseau).
    </figcaption>
  </figure>
</template>

<style scoped>
.chart {
  margin: 0;
}

.legend {
  display: flex;
  gap: 1rem;
  margin: 0 0 0.75rem;
  padding: 0;
  list-style: none;
  font-size: 0.8125rem;
  color: var(--text-secondary);
}

.legend li {
  display: flex;
  align-items: center;
  gap: 0.4rem;
}

.swatch {
  width: 0.625rem;
  height: 0.625rem;
  border-radius: 2px;
}

.chart__canvas {
  position: relative;
}

svg {
  display: block;
  width: 100%;
  height: auto;
}

.grid {
  stroke: var(--grid);
  stroke-width: 1;
}

.axis-label {
  fill: var(--text-muted);
  font-size: 11px;
  font-variant-numeric: tabular-nums;
}

.tooltip {
  position: absolute;
  top: 0;
  transform: translateX(-50%);
  display: flex;
  flex-direction: column;
  gap: 0.15rem;
  padding: 0.5rem 0.75rem;
  background: var(--surface-1);
  border: 1px solid var(--border);
  border-radius: 8px;
  box-shadow: 0 4px 16px rgb(0 0 0 / 0.08);
  font-size: 0.8125rem;
  white-space: nowrap;
  pointer-events: none;
}

.tooltip__muted {
  color: var(--text-muted);
}

.empty {
  margin: 0;
  padding: 2rem 0;
  color: var(--text-muted);
}

figcaption {
  margin-top: 0.5rem;
  font-size: 0.8125rem;
  color: var(--text-muted);
}
</style>
