<script setup>
import { computed, onMounted, ref } from 'vue'

import projectsApi from '@/api/projects'
import tagsApi from '@/api/tags'
import tasksApi from '@/api/tasks'

import TaskFormDialog from './TaskFormDialog.vue'

const PRIORITY_LABELS = { low: 'niedrig', medium: 'mittel', high: 'hoch', urgent: 'dringend' }
const PRIORITY_COLORS = { low: 'default', medium: 'default', high: 'warning', urgent: 'error' }
const UNIT_LABELS = { day: 'Tag(e)', week: 'Woche(n)', month: 'Monat(e)', year: 'Jahr(e)' }

const tasks = ref([])
const projects = ref([])
const tags = ref([])
const showDone = ref(false)
const dialog = ref(false)
const editingTask = ref(null)
const snackbar = ref(false)
const snackbarText = ref('')

const filters = ref({ priority: null, tag: null, project_id: null })

const today = new Date().toISOString().slice(0, 10)

const projectName = (id) => projects.value.find((project) => project.id === id)?.name

const filtered = computed(() =>
  tasks.value.filter(
    (task) =>
      (!filters.value.priority || task.priority === filters.value.priority) &&
      (!filters.value.tag || task.tags.some((tag) => tag.name === filters.value.tag)) &&
      (!filters.value.project_id || task.project_id === filters.value.project_id),
  ),
)

const byDue = (a, b) => (a.due_date ?? '9999') < (b.due_date ?? '9999') ? -1 : 1

const overdue = computed(() =>
  filtered.value.filter((task) => task.status !== 'done' && task.due_date && task.due_date < today).sort(byDue),
)
const open = computed(() =>
  filtered.value.filter((task) => task.status !== 'done' && !(task.due_date && task.due_date < today)).sort(byDue),
)
const done = computed(() => filtered.value.filter((task) => task.status === 'done'))

async function load() {
  tasks.value = (await tasksApi.list({ per_page: 100, sort: 'due_date' })).data
  tags.value = await tagsApi.list()
}

function openCreate() {
  editingTask.value = null
  dialog.value = true
}

function openEdit(task) {
  editingTask.value = task
  dialog.value = true
}

async function complete(task) {
  const result = await tasksApi.complete(task.id)
  if (result.next) {
    snackbarText.value = `Erledigt — nächste Aufgabe am ${result.next.due_date} angelegt.`
    snackbar.value = true
  }
  await load()
}

async function remove(task) {
  await tasksApi.destroy(task.id)
  await load()
}

onMounted(async () => {
  await load()
  projects.value = await projectsApi.list()
})
</script>

<template>
  <div>
    <div class="d-flex align-center mb-4">
      <h1 class="text-h5">Aufgaben</h1>
      <v-spacer />
      <v-btn color="primary" prepend-icon="mdi-plus" @click="openCreate">Neue Aufgabe</v-btn>
    </div>

    <v-row density="comfortable" class="mb-1">
      <v-col cols="4" md="2">
        <v-select
          v-model="filters.priority"
          label="Priorität"
          :items="Object.entries(PRIORITY_LABELS).map(([value, title]) => ({ value, title }))"
          clearable
          hide-details
          density="compact"
        />
      </v-col>
      <v-col cols="4" md="2">
        <v-select
          v-model="filters.tag"
          label="Tag"
          :items="tags"
          item-title="name"
          item-value="name"
          clearable
          hide-details
          density="compact"
        />
      </v-col>
      <v-col cols="4" md="2">
        <v-select
          v-model="filters.project_id"
          label="Projekt"
          :items="projects"
          item-title="name"
          item-value="id"
          clearable
          hide-details
          density="compact"
        />
      </v-col>
    </v-row>

    <template v-for="group in [
      { key: 'overdue', title: 'Überfällig', items: overdue, color: 'error' },
      { key: 'open', title: 'Offen', items: open, color: undefined },
    ]" :key="group.key">
      <h2 v-if="group.items.length" class="text-subtitle-1 mt-4 mb-1" :class="group.color ? `text-${group.color}` : ''">
        {{ group.title }}
      </h2>
      <v-card v-if="group.items.length">
        <v-list lines="two">
          <v-list-item v-for="task in group.items" :key="task.id" :title="task.title">
            <template #prepend>
              <v-btn
                icon="mdi-checkbox-blank-outline"
                variant="text"
                title="Erledigen"
                @click="complete(task)"
              />
            </template>
            <template #subtitle>
              <span v-if="task.due_date" :class="group.key === 'overdue' ? 'text-error' : ''">
                fällig {{ task.due_date }}
              </span>
              <span v-if="task.recurrence_interval">
                · <v-icon icon="mdi-repeat" size="x-small" /> alle {{ task.recurrence_interval }}
                {{ UNIT_LABELS[task.recurrence_unit] }}
              </span>
              <span v-if="projectName(task.project_id)"> · {{ projectName(task.project_id) }}</span>
            </template>
            <template #append>
              <v-chip
                v-if="task.priority !== 'medium'"
                :color="PRIORITY_COLORS[task.priority]"
                size="x-small"
                class="mr-1"
              >
                {{ PRIORITY_LABELS[task.priority] }}
              </v-chip>
              <v-chip v-for="tag in task.tags" :key="tag.id" size="x-small" class="ml-1 d-none d-sm-flex">
                {{ tag.name }}
              </v-chip>
              <v-btn
                icon="mdi-pencil"
                size="small"
                variant="text"
                title="Bearbeiten"
                @click="openEdit(task)"
              />
              <v-btn
                icon="mdi-delete"
                size="small"
                variant="text"
                title="Löschen"
                @click="remove(task)"
              />
            </template>
          </v-list-item>
        </v-list>
      </v-card>
    </template>

    <v-empty-state
      v-if="!overdue.length && !open.length"
      icon="mdi-checkbox-marked-circle-outline"
      title="Nichts offen"
      text="Alle Aufgaben erledigt — oder lege die erste an."
    />

    <div v-if="done.length" class="mt-4">
      <v-btn variant="text" size="small" @click="showDone = !showDone">
        {{ showDone ? 'Erledigte ausblenden' : `Erledigte anzeigen (${done.length})` }}
      </v-btn>
      <v-card v-if="showDone" class="mt-1">
        <v-list density="compact">
          <v-list-item v-for="task in done" :key="task.id">
            <template #prepend>
              <v-icon icon="mdi-checkbox-marked" color="success" class="mr-2" />
            </template>
            <v-list-item-title class="text-decoration-line-through text-medium-emphasis">
              {{ task.title }}
            </v-list-item-title>
            <template #append>
              <v-btn
                icon="mdi-pencil"
                size="small"
                variant="text"
                title="Bearbeiten"
                @click="openEdit(task)"
              />
              <v-btn
                icon="mdi-delete"
                size="small"
                variant="text"
                title="Löschen"
                @click="remove(task)"
              />
            </template>
          </v-list-item>
        </v-list>
      </v-card>
    </div>

    <TaskFormDialog v-model="dialog" :task="editingTask" :projects="projects" @saved="load" />

    <v-snackbar v-model="snackbar" color="success">{{ snackbarText }}</v-snackbar>
  </div>
</template>
