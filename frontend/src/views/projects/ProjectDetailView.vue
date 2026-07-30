<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'

import expensesApi from '@/api/expenses'
import itemsApi from '@/api/items'
import projectsApi from '@/api/projects'
import tasksApi from '@/api/tasks'
import DocumentsSection from '@/components/DocumentsSection.vue'
import { formatCents } from '@/lib/money'

import ProjectFormDialog from './ProjectFormDialog.vue'

const STATUS_LABELS = { planned: 'Geplant', active: 'Aktiv', on_hold: 'Pausiert', done: 'Fertig' }
const STATUS_COLORS = { planned: 'default', active: 'primary', on_hold: 'warning', done: 'success' }

const route = useRoute()
const router = useRouter()

const project = ref(null)
const tasks = ref([])
const expenses = ref([])
const items = ref([])
const editDialog = ref(false)
const snackbar = ref(false)
const snackbarText = ref('')

const openTasks = computed(() => tasks.value.filter((task) => task.status !== 'done'))
const doneTasks = computed(() => tasks.value.filter((task) => task.status === 'done'))

const budgetShare = computed(() => {
  if (!project.value?.budget_cents) return null
  return Math.min(Math.round((project.value.spent_cents / project.value.budget_cents) * 100), 100)
})

async function load() {
  const id = route.params.id
  project.value = await projectsApi.get(id)
  ;[tasks.value, expenses.value, items.value] = await Promise.all([
    tasksApi.list({ project_id: id, per_page: 100 }).then((r) => r.data),
    expensesApi.list({ project_id: id, per_page: 100 }).then((r) => r.data),
    itemsApi.list({ project_id: id, per_page: 100 }).then((r) => r.data),
  ])
}

async function completeTask(task) {
  const result = await tasksApi.complete(task.id)
  if (result.next) {
    snackbarText.value = `Erledigt — nächste Aufgabe am ${result.next.due_date} angelegt.`
    snackbar.value = true
  }
  await load()
}

async function removeProject() {
  await projectsApi.destroy(project.value.id)
  router.push('/projects')
}

onMounted(load)
</script>

<template>
  <div v-if="project">
    <div class="d-flex align-center mb-4">
      <v-btn icon="mdi-arrow-left" variant="text" @click="router.push('/projects')" />
      <h1 class="text-h5 ml-1">{{ project.name }}</h1>
      <v-chip :color="STATUS_COLORS[project.status]" size="small" class="ml-3">
        {{ STATUS_LABELS[project.status] }}
      </v-chip>
      <v-spacer />
      <v-btn icon="mdi-pencil" variant="text" title="Bearbeiten" @click="editDialog = true" />
      <v-btn icon="mdi-delete" variant="text" title="Löschen" @click="removeProject" />
    </div>

    <v-row>
      <v-col cols="12" md="6">
        <v-card title="Überblick" class="mb-4">
          <v-card-text>
            <p v-if="project.starts_on" class="mb-1 text-medium-emphasis">
              {{ project.starts_on }}<template v-if="project.ends_on"> – {{ project.ends_on }}</template>
            </p>
            <p v-if="project.description" class="mb-2">{{ project.description }}</p>
            <p v-if="project.notes" class="mb-2 text-medium-emphasis">{{ project.notes }}</p>
            <div class="d-flex align-center">
              <v-progress-linear
                v-if="budgetShare !== null"
                :model-value="budgetShare"
                :color="project.spent_cents > project.budget_cents ? 'error' : 'primary'"
                height="8"
                rounded
                style="max-width: 320px"
              />
              <span class="ml-3 text-no-wrap">
                {{ formatCents(project.spent_cents) }}<template v-if="project.budget_cents"> / {{ formatCents(project.budget_cents) }}</template>
              </span>
            </div>
          </v-card-text>
        </v-card>

        <v-card :title="`Aufgaben (${openTasks.length} offen)`" class="mb-4">
          <v-card-text v-if="tasks.length" class="pa-0">
            <v-list density="compact">
              <v-list-item v-for="task in openTasks" :key="task.id" :title="task.title">
                <template #prepend>
                  <v-btn
                    icon="mdi-checkbox-blank-outline"
                    variant="text"
                    size="small"
                    title="Erledigen"
                    @click="completeTask(task)"
                  />
                </template>
                <template #subtitle>
                  <span v-if="task.due_date">fällig {{ task.due_date }}</span>
                </template>
              </v-list-item>
              <v-list-item v-for="task in doneTasks" :key="task.id">
                <template #prepend>
                  <v-icon icon="mdi-checkbox-marked" color="success" class="mr-2" />
                </template>
                <v-list-item-title class="text-decoration-line-through text-medium-emphasis">
                  {{ task.title }}
                </v-list-item-title>
              </v-list-item>
            </v-list>
          </v-card-text>
          <v-card-text v-else class="text-medium-emphasis">
            Keine Aufgaben verknüpft — im Aufgabenmodul mit diesem Projekt anlegen.
          </v-card-text>
        </v-card>

        <v-card title="Fotos & Dokumente">
          <v-card-text>
            <DocumentsSection documentable-type="project" :documentable-id="project.id" />
          </v-card-text>
        </v-card>
      </v-col>

      <v-col cols="12" md="6">
        <v-card :title="`Ausgaben (${formatCents(project.spent_cents)})`" class="mb-4">
          <v-card-text v-if="expenses.length" class="pa-0">
            <v-list density="compact">
              <v-list-item
                v-for="expense in expenses"
                :key="expense.id"
                :title="expense.description"
                :subtitle="expense.spent_on"
              >
                <template #append>
                  <span class="text-subtitle-2">{{ formatCents(expense.amount_cents) }}</span>
                </template>
              </v-list-item>
            </v-list>
          </v-card-text>
          <v-card-text v-else class="text-medium-emphasis">
            Noch keine Ausgaben verknüpft.
          </v-card-text>
        </v-card>

        <v-card :title="`Gegenstände (${items.length})`">
          <v-card-text v-if="items.length" class="pa-0">
            <v-list density="compact">
              <v-list-item
                v-for="item in items"
                :key="item.id"
                :title="item.name"
                :subtitle="item.location?.name"
                @click="router.push(`/items/${item.id}`)"
              />
            </v-list>
          </v-card-text>
          <v-card-text v-else class="text-medium-emphasis">
            Keine Gegenstände zugeordnet.
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>

    <ProjectFormDialog v-model="editDialog" :project="project" @saved="load" />
    <v-snackbar v-model="snackbar" color="success">{{ snackbarText }}</v-snackbar>
  </div>
</template>
