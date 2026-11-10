<script setup lang="ts">
import { PROFILES } from '~/composables/useEcoPulse'

const { summary, endpoints, measurements, loading, error, triggering, refresh, trigger } = useEcoPulse()

const PROFILE_HINTS = {
  fast: 'aucun délai simulé',
  medium: '≈ 150 ms simulés',
  slow: '≈ 600 ms simulés',
} as const
</script>

<template>
  <div class="page">
    <header class="header">
      <div>
        <h1 class="header__title"><span class="logo" aria-hidden="true">●</span> EcoPulse</h1>
        <p class="header__lead">
          Mesure et visualisation des performances d'une API REST Symfony : temps de traitement côté serveur et
          volume des réponses JSON.
        </p>
      </div>
      <button class="btn btn--ghost" :disabled="loading" @click="refresh">
        {{ loading ? 'Actualisation…' : 'Actualiser' }}
      </button>
    </header>

    <p v-if="error" class="alert" role="alert">{{ error }}</p>

    <section class="panel" aria-labelledby="trigger-title">
      <h2 id="trigger-title" class="panel__title">Déclencher un endpoint</h2>
      <p class="panel__hint">
        Chaque clic appelle l'API de démonstration et enregistre une nouvelle mesure. Les délais sont simulés,
        les mesures sont réelles.
      </p>
      <div class="actions">
        <button
          v-for="p in PROFILES"
          :key="p"
          class="btn"
          :disabled="triggering !== null"
          @click="trigger(p)"
        >
          <span class="swatch" :style="{ background: `var(--series-${p})` }" aria-hidden="true" />
          <span>
            <code>GET /api/demo/{{ p }}</code>
            <small>{{ triggering === p ? 'Appel en cours…' : PROFILE_HINTS[p] }}</small>
          </span>
        </button>
      </div>
    </section>

    <section class="stats" aria-label="Indicateurs clés">
      <StatCard label="Requêtes mesurées" :value="summary ? formatNumber(summary.totalRequests) : '—'" hint="sur les 3 endpoints de démo" />
      <StatCard label="Temps de réponse moyen" :value="summary ? formatMs(summary.avgDurationMs) : '—'" hint="traitement côté serveur" />
      <StatCard label="Données échangées" :value="summary ? formatBytes(summary.totalBytes) : '—'" hint="somme des réponses JSON" />
    </section>

    <section class="panel" aria-labelledby="chart-title">
      <h2 id="chart-title" class="panel__title">Évolution des temps de réponse</h2>
      <ResponseTimeChart :measurements="measurements" />
    </section>

    <section class="panel" aria-labelledby="table-title">
      <h2 id="table-title" class="panel__title">Performances par endpoint</h2>
      <EndpointTable :endpoints="endpoints" />
    </section>

    <footer class="footer">
      EcoPulse — MVP de démonstration · Symfony · Nuxt · PostgreSQL
    </footer>
  </div>
</template>

<style scoped>
.page {
  max-width: 1080px;
  margin: 0 auto;
  padding: 2rem 1rem 3rem;
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
}

.header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 1.5rem;
}

.header__title {
  margin: 0;
  font-size: 1.75rem;
  letter-spacing: -0.02em;
}

.logo {
  color: var(--accent);
}

.header__lead {
  margin: 0.35rem 0 0;
  max-width: 60ch;
  color: var(--text-secondary);
}

.alert {
  margin: 0;
  padding: 0.75rem 1rem;
  border-radius: var(--radius);
  background: var(--danger-bg);
  color: var(--danger-text);
  font-weight: 500;
}

.panel {
  background: var(--surface-1);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  padding: 1.25rem 1.5rem;
}

.panel__title {
  margin: 0 0 1rem;
  font-size: 1rem;
  font-weight: 600;
}

.panel__hint {
  margin: -0.5rem 0 1rem;
  font-size: 0.875rem;
  color: var(--text-muted);
}

.stats {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 1.25rem;
}

.actions {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 0.75rem;
}

.btn {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.75rem 1rem;
  border: 1px solid var(--border);
  border-radius: 10px;
  background: var(--surface-2);
  color: var(--text-primary);
  font: inherit;
  text-align: left;
  cursor: pointer;
  transition: border-color 0.15s, background 0.15s;
}

.btn:hover:not(:disabled) {
  border-color: var(--accent);
}

.btn:focus-visible {
  outline: 2px solid var(--accent);
  outline-offset: 2px;
}

.btn:disabled {
  cursor: progress;
  opacity: 0.6;
}

.btn small {
  display: block;
  font-size: 0.8125rem;
  color: var(--text-muted);
}

.btn--ghost {
  flex-shrink: 0;
  background: transparent;
  font-weight: 500;
}

.swatch {
  flex-shrink: 0;
  width: 0.75rem;
  height: 0.75rem;
  border-radius: 3px;
}

.footer {
  text-align: center;
  font-size: 0.8125rem;
  color: var(--text-muted);
}

@media (max-width: 640px) {
  .header {
    flex-direction: column;
  }
}
</style>
