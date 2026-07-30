<script setup>
import { onMounted, ref, watch } from 'vue'
import { useRouter } from 'vue-router'

import projectsApi from '@/api/projects'
import { formatCents } from '@/lib/money'

import ProjectFormDialog from './ProjectFormDialog.vue'

const STATUS_LABELS = { planned: 'Geplant', active: 'Aktiv', on_hold: 'Pausiert', done: 'Fertig' }
const STATUS_COLORS = { planned: 'default', active: 'primary', on_hold: 'warning', done: 'success' }

const router = useRouter()
const projects = ref([])
const statusFilter = ref(null)
const dialog = ref(false)
const editingProject = ref(null)

async function load() {
  projects.value = await projectsApi.list(statusFilter.value ? { status: statusFilter.value } : {})
}

watch(statusFilter, load)

function budgetShare(project) {
  if (!project.budget_cents) return null
  return Math.min(Math.round((project.spent_cents / project.budget_cents) * 100), 100)
}

function openCreate() {
  editingProject.value = null
  dialog.value = true
}

function openEdit(project) {
  editingProject.value = project
  dialog.value = true
}

async function remove(project) {
  await projectsApi.destroy(project.id)
  await load()
}

onMounted(load)
</script>

<template>
  <div>
    <div class="d-flex align-center mb-4">
      <h1 class="text-h5">Projekte</h1>
      <v-spacer />
      <v-btn color="primary" prepend-icon="mdi-plus" @click="openCreate">Neues Projekt</v-btn>
    </div>

    <v-select
      v-model="statusFilter"
      label="Status"
      :items="Object.entries(STATUS_LABELS).map(([value, title]) => ({ value, title }))"
      clearable
      hide-details
      density="compact"
      max-width="240"
      class="mb-3"
    />

    <v-card v-if="projects.length">
      <v-list lines="two">
        <v-list-item
          v-for="project in projects"
          :key="project.id"
          :title="project.name"
          @click="router.push(`/projects/${project.id}`)"
        >
          <template #subtitle>
            <span v-if="project.starts_on">{{ project.starts_on }}<template v-if="project.ends_on"> – {{ project.ends_on }}</template> · </span>
            <span>{{ formatCents(project.spent_cents) }}<template v-if="project.budget_cents"> / {{ formatCents(project.budget_cents) }}</template></span>
            <v-progress-linear
              v-if="budgetShare(project) !== null"
              :model-value="budgetShare(project)"
              :color="project.spent_cents > project.budget_cents ? 'error' : 'primary'"
              height="6"
              rounded
              class="mt-1"
              style="max-width: 280px"
            />
          </template>
          <template #append>
            <v-chip :color="STATUS_COLORS[project.status]" size="small" class="mr-2">
              {{ STATUS_LABELS[project.status] }}
            </v-chip>
            <v-btn
              icon="mdi-pencil"
              size="small"
              variant="text"
              title="Bearbeiten"
              @click.stop="openEdit(project)"
            />
            <v-btn
              icon="mdi-delete"
              size="small"
              variant="text"
              title="Löschen"
              @click.stop="remove(project)"
            />
          </template>
        </v-list-item>
      </v-list>
    </v-card>

    <v-empty-state
      v-else
      icon="mdi-hammer-wrench"
      title="Keine Projekte"
      text="Lege das erste Vorhaben an — Umbau, Reise oder Side Project."
    />

    <ProjectFormDialog v-model="dialog" :project="editingProject" @saved="load" />
  </div>
</template>
