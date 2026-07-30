<script setup>
import { useDisplay } from 'vuetify'
import { useRouter } from 'vue-router'

import { useAuthStore } from '@/stores/auth'

const { mdAndUp } = useDisplay()
const auth = useAuthStore()
const router = useRouter()

const modules = [
  { title: 'Gegenstände', icon: 'mdi-package-variant-closed', to: '/items' },
  { title: 'Orte', icon: 'mdi-map-marker', to: '/locations' },
  { title: 'Aufgaben', icon: 'mdi-checkbox-marked-outline', to: '/tasks' },
  { title: 'Wartung', icon: 'mdi-wrench', to: '/maintenance' },
  { title: 'Ausgaben', icon: 'mdi-currency-eur', to: '/expenses' },
  { title: 'Projekte', icon: 'mdi-hammer-wrench', to: '/projects' },
]

// Bottom nav has room for fewer entries than the drawer.
const bottomModules = modules.slice(0, 4)

const csvEntities = [
  { entity: 'items', title: 'Gegenstände' },
  { entity: 'locations', title: 'Orte' },
  { entity: 'categories', title: 'Kategorien' },
  { entity: 'tags', title: 'Tags' },
  { entity: 'projects', title: 'Projekte' },
  { entity: 'tasks', title: 'Aufgaben' },
  { entity: 'maintenance_plans', title: 'Wartungspläne' },
  { entity: 'maintenance_logs', title: 'Wartungshistorie' },
  { entity: 'expenses', title: 'Ausgaben' },
  { entity: 'budgets', title: 'Budgets' },
]

async function logout() {
  await auth.logout()
  router.push({ name: 'login' })
}
</script>

<template>
  <v-app-bar density="compact">
    <v-app-bar-title>Home Resource Planner</v-app-bar-title>
    <template #append>
      <span v-if="auth.user" class="text-body-2 mr-2 d-none d-sm-inline">{{ auth.user.name }}</span>
      <v-menu>
        <template #activator="{ props: menuProps }">
          <v-btn v-bind="menuProps" icon="mdi-database-export" title="Export & Backup" />
        </template>
        <v-list density="compact">
          <v-list-item
            title="Gesamtbackup (JSON)"
            prepend-icon="mdi-code-json"
            href="/api/v1/export/json"
          />
          <v-divider />
          <v-list-subheader>CSV-Export</v-list-subheader>
          <v-list-item
            v-for="entry in csvEntities"
            :key="entry.entity"
            :title="entry.title"
            prepend-icon="mdi-file-delimited"
            :href="`/api/v1/export/csv/${entry.entity}`"
          />
        </v-list>
      </v-menu>
      <v-btn icon="mdi-logout" title="Abmelden" @click="logout" />
    </template>
  </v-app-bar>

  <v-navigation-drawer v-if="mdAndUp" permanent>
    <v-list nav density="compact">
      <v-list-item
        v-for="module in modules"
        :key="module.to"
        :prepend-icon="module.icon"
        :title="module.title"
        :to="module.to"
      />
    </v-list>
  </v-navigation-drawer>

  <v-main>
    <v-container>
      <RouterView />
    </v-container>
  </v-main>

  <v-bottom-navigation v-if="!mdAndUp" grow>
    <v-btn v-for="module in bottomModules" :key="module.to" :to="module.to">
      <v-icon>{{ module.icon }}</v-icon>
      <span class="text-caption">{{ module.title }}</span>
    </v-btn>
  </v-bottom-navigation>
</template>
