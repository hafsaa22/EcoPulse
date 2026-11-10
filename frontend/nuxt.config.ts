// https://nuxt.com/docs/api/configuration/nuxt-config

// URL de l'API Symfony, lue au démarrage (dev) ou au build.
const backendUrl = process.env.NUXT_BACKEND_URL ?? 'http://127.0.0.1:8000'

export default defineNuxtConfig({
  compatibilityDate: '2025-07-15',
  devtools: { enabled: true },

  css: ['~/assets/main.css'],

  app: {
    head: {
      htmlAttrs: { lang: 'fr' },
      title: 'EcoPulse — Performances d\'API REST',
      meta: [
        { name: 'description', content: 'Tableau de bord de mesure des performances d\'une API REST de démonstration.' },
      ],
    },
  },

  // Le serveur Nuxt relaie /api/** vers Symfony : une seule origine pour le navigateur, donc pas de CORS.
  routeRules: {
    '/api/**': { proxy: `${backendUrl}/api/**` },
  },
})
