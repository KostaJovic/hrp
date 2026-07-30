<script setup>
import { computed, onMounted, ref } from 'vue'

import locationsApi from '@/api/locations'

const KIND_LABELS = {
  room: 'Raum',
  shelf: 'Regal',
  box: 'Box',
  cabinet: 'Schrank',
  garage: 'Garage',
  vehicle: 'Fahrzeug',
  other: 'Sonstiges',
}

const KIND_ICONS = {
  room: 'mdi-door',
  shelf: 'mdi-bookshelf',
  box: 'mdi-package-variant',
  cabinet: 'mdi-wardrobe',
  garage: 'mdi-garage',
  vehicle: 'mdi-car',
  other: 'mdi-map-marker',
}

const locations = ref([])
const loading = ref(false)
const snackbar = ref(false)
const snackbarText = ref('')

function notify(text) {
  snackbarText.value = text
  snackbar.value = true
}

const dialog = ref(false)
const saving = ref(false)
const editing = ref(null)
const form = ref({ name: '', kind: 'room', parent_id: null, description: '' })
const formErrors = ref({})

// Flatten the tree depth-first so the list reads Raum → Regal → Box.
const tree = computed(() => {
  const byParent = Map.groupBy(locations.value, (location) => location.parent_id)
  const walk = (parentId, depth) =>
    (byParent.get(parentId) ?? []).flatMap((location) => [
      { ...location, depth },
      ...walk(location.id, depth + 1),
    ])
  return walk(null, 0)
})

const parentOptions = computed(() =>
  tree.value
    .filter((location) => location.id !== editing.value?.id)
    .map((location) => ({
      value: location.id,
      title: `${' '.repeat(location.depth * 4)}${location.name}`,
    })),
)

const kindOptions = Object.entries(KIND_LABELS).map(([value, title]) => ({ value, title }))

async function load() {
  loading.value = true
  try {
    locations.value = await locationsApi.list()
  } finally {
    loading.value = false
  }
}

function openCreate(parentId = null) {
  editing.value = null
  form.value = { name: '', kind: 'room', parent_id: parentId, description: '' }
  formErrors.value = {}
  dialog.value = true
}

function openEdit(location) {
  editing.value = location
  form.value = {
    name: location.name,
    kind: location.kind,
    parent_id: location.parent_id,
    description: location.description ?? '',
  }
  formErrors.value = {}
  dialog.value = true
}

async function save() {
  saving.value = true
  formErrors.value = {}
  try {
    if (editing.value) {
      await locationsApi.update(editing.value.id, form.value)
    } else {
      await locationsApi.create(form.value)
    }
    dialog.value = false
    await load()
  } catch (e) {
    if (e.response?.status === 422) {
      formErrors.value = e.response.data.errors ?? {}
    } else {
      notify('Speichern fehlgeschlagen.')
    }
  } finally {
    saving.value = false
  }
}

async function remove(location) {
  try {
    await locationsApi.destroy(location.id)
    await load()
  } catch (e) {
    notify(
      e.response?.status === 409
        ? 'Dieser Ort enthält noch andere Orte und kann nicht gelöscht werden.'
        : 'Löschen fehlgeschlagen.',
    )
  }
}

onMounted(load)
</script>

<template>
  <div>
    <div class="d-flex align-center mb-4">
      <h1 class="text-h5">Orte</h1>
      <v-spacer />
      <v-btn color="primary" prepend-icon="mdi-plus" @click="openCreate()">Neuer Ort</v-btn>
    </div>

    <v-progress-linear v-if="loading" indeterminate class="mb-2" />

    <v-list v-if="tree.length" density="compact" lines="one">
      <v-list-item
        v-for="location in tree"
        :key="location.id"
        :prepend-icon="KIND_ICONS[location.kind]"
        :title="location.name"
        :subtitle="KIND_LABELS[location.kind]"
        :style="{ paddingInlineStart: `${16 + location.depth * 28}px` }"
      >
        <template #append>
          <v-btn icon="mdi-plus" size="small" variant="text" title="Unterort anlegen" @click="openCreate(location.id)" />
          <v-btn icon="mdi-pencil" size="small" variant="text" title="Bearbeiten" @click="openEdit(location)" />
          <v-btn icon="mdi-delete" size="small" variant="text" title="Löschen" @click="remove(location)" />
        </template>
      </v-list-item>
    </v-list>

    <v-empty-state
      v-else-if="!loading"
      icon="mdi-map-marker-off"
      title="Noch keine Orte"
      text="Lege den ersten Raum an, dann Regale und Boxen darunter."
    />

    <v-dialog v-model="dialog" max-width="480">
      <v-card :title="editing ? 'Ort bearbeiten' : 'Neuer Ort'">
        <v-card-text>
          <v-form @submit.prevent="save">
            <v-text-field
              v-model="form.name"
              label="Name"
              :error-messages="formErrors.name"
              required
            />
            <v-select
              v-model="form.kind"
              label="Art"
              :items="kindOptions"
              :error-messages="formErrors.kind"
            />
            <v-select
              v-model="form.parent_id"
              label="Übergeordneter Ort"
              :items="parentOptions"
              :error-messages="formErrors.parent_id"
              clearable
            />
            <v-textarea
              v-model="form.description"
              label="Beschreibung"
              rows="2"
              :error-messages="formErrors.description"
            />
          </v-form>
        </v-card-text>
        <v-card-actions>
          <v-spacer />
          <v-btn @click="dialog = false">Abbrechen</v-btn>
          <v-btn color="primary" :loading="saving" @click="save">Speichern</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <v-snackbar v-model="snackbar" color="error">
      {{ snackbarText }}
    </v-snackbar>
  </div>
</template>
