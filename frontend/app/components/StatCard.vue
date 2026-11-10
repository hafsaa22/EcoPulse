<script setup lang="ts">
// Carte d'indicateur clé : un libellé, une valeur mise en avant, une précision.
defineProps<{
  label: string
  value: string
  hint?: string
}>()
</script>

<template>
  <article class="stat-card">
    <p class="stat-card__label">{{ label }}</p>
    <p class="stat-card__value">{{ value }}</p>
    <p v-if="hint" class="stat-card__hint">{{ hint }}</p>
  </article>
</template>

<style scoped>
.stat-card {
  background: var(--surface-1);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  padding: 1.25rem 1.5rem;
}

.stat-card__label {
  margin: 0;
  font-size: 0.875rem;
  color: var(--text-secondary);
}

.stat-card__value {
  margin: 0.35rem 0 0;
  font-size: 2rem;
  font-weight: 650;
  letter-spacing: -0.02em;
  font-variant-numeric: tabular-nums;
}

.stat-card__hint {
  margin: 0.35rem 0 0;
  font-size: 0.8125rem;
  color: var(--text-muted);
}
</style>
