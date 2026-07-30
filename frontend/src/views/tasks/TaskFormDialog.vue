<script setup>
import { ref, watch } from 'vue'

import tagsApi from '@/api/tags'
import tasksApi from '@/api/tasks'

const PRIORITY_OPTIONS = [
  { value: 'low', title: 'Niedrig' },
  { value: 'medium', title: 'Mittel' },
  { value: 'high', title: 'Hoch' },
  { value: 'urgent', title: 'Dringend' },
]

const STATUS_OPTIONS = [
  { value: 'open', title: 'Offen' },
  { value: 'in_progress', title: 'In Arbeit' },
  { value: 'done', title: 'Erledigt' },
]

const UNIT_OPTIONS = [
  { value: 'day', title: 'Tag(e)' },
  { value: 'week', title: 'Woche(n)' },
  { value: 'month', title: 'Monat(e)' },
  { value: 'year', title: 'Jahr(e)' },
]

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  task: { type: Object, default: null },
  projects: { type: Array, default: () => [] },
})
const emit = defineEmits(['update:modelValue', 'saved'])

const form = ref({})
const errors = ref({})
const saving = ref(false)
const knownTags = ref([])
const recurring = ref(false)

watch(
  () => props.modelValue,
  async (open) => {
    if (!open) return
    const task = props.task
    form.value = {
      title: task?.title ?? '',
      description: task?.description ?? '',
      status: task?.status ?? 'open',
      priority: task?.priority ?? 'medium',
      due_date: task?.due_date ?? null,
      project_id: task?.project_id ?? null,
      recurrence_interval: task?.recurrence_interval ?? 1,
      recurrence_unit: task?.recurrence_unit ?? 'month',
      tags: task?.tags?.map((tag) => tag.name) ?? [],
    }
    recurring.value = task ? task.recurrence_interval !== null : false
    errors.value = {}
    knownTags.value = (await tagsApi.list()).map((tag) => tag.name)
  },
)

async function save() {
  saving.value = true
  errors.value = {}
  try {
    const payload = {
      ...form.value,
      description: form.value.description || null,
      due_date: form.value.due_date || null,
      recurrence_interval: recurring.value ? form.value.recurrence_interval : null,
      recurrence_unit: recurring.value ? form.value.recurrence_unit : null,
    }
    props.task
      ? await tasksApi.update(props.task.id, payload)
      : await tasksApi.create(payload)
    emit('update:modelValue', false)
    emit('saved')
  } catch (e) {
    errors.value = e.response?.status === 422 ? (e.response.data.errors ?? {}) : {}
  } finally {
    saving.value = false
  }
}
</script>

<template>
  <v-dialog
    :model-value="modelValue"
    max-width="560"
    @update:model-value="emit('update:modelValue', $event)"
  >
    <v-card :title="task ? 'Aufgabe bearbeiten' : 'Neue Aufgabe'">
      <v-card-text>
        <v-form @submit.prevent="save">
          <v-text-field v-model="form.title" label="Titel" :error-messages="errors.title" />
          <v-textarea
            v-model="form.description"
            label="Beschreibung"
            rows="2"
            :error-messages="errors.description"
          />
          <v-row density="comfortable">
            <v-col cols="6">
              <v-select
                v-model="form.priority"
                label="Priorität"
                :items="PRIORITY_OPTIONS"
                :error-messages="errors.priority"
              />
            </v-col>
            <v-col cols="6">
              <v-select
                v-if="task"
                v-model="form.status"
                label="Status"
                :items="STATUS_OPTIONS"
                :error-messages="errors.status"
              />
            </v-col>
          </v-row>
          <v-row density="comfortable">
            <v-col cols="6">
              <v-text-field
                v-model="form.due_date"
                label="Fällig am"
                type="date"
                clearable
                :error-messages="errors.due_date"
              />
            </v-col>
            <v-col cols="6">
              <v-select
                v-model="form.project_id"
                label="Projekt"
                :items="projects"
                item-title="name"
                item-value="id"
                clearable
                :error-messages="errors.project_id"
              />
            </v-col>
          </v-row>
          <v-combobox
            v-model="form.tags"
            label="Kontext-Tags"
            :items="knownTags"
            multiple
            chips
            closable-chips
            :error-messages="errors.tags"
          />
          <v-switch v-model="recurring" label="Wiederkehrend" color="primary" hide-details />
          <v-row v-if="recurring" density="comfortable">
            <v-col cols="4">
              <v-text-field
                v-model.number="form.recurrence_interval"
                label="Alle"
                type="number"
                min="1"
                :error-messages="errors.recurrence_interval"
              />
            </v-col>
            <v-col cols="8">
              <v-select
                v-model="form.recurrence_unit"
                label="Einheit"
                :items="UNIT_OPTIONS"
                :error-messages="errors.recurrence_unit"
              />
            </v-col>
          </v-row>
        </v-form>
      </v-card-text>
      <v-card-actions>
        <v-spacer />
        <v-btn @click="emit('update:modelValue', false)">Abbrechen</v-btn>
        <v-btn color="primary" :loading="saving" @click="save">Speichern</v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>
