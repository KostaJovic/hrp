<script setup>
import { ref, watch } from 'vue'

import projectsApi from '@/api/projects'
import { centsToEuros, eurosToCents } from '@/lib/money'

const STATUS_OPTIONS = [
  { value: 'planned', title: 'Geplant' },
  { value: 'active', title: 'Aktiv' },
  { value: 'on_hold', title: 'Pausiert' },
  { value: 'done', title: 'Fertig' },
]

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  project: { type: Object, default: null },
})
const emit = defineEmits(['update:modelValue', 'saved'])

const form = ref({})
const errors = ref({})
const saving = ref(false)

watch(
  () => props.modelValue,
  (open) => {
    if (!open) return
    const project = props.project
    form.value = {
      name: project?.name ?? '',
      description: project?.description ?? '',
      status: project?.status ?? 'planned',
      starts_on: project?.starts_on ?? null,
      ends_on: project?.ends_on ?? null,
      budget: centsToEuros(project?.budget_cents),
      notes: project?.notes ?? '',
    }
    errors.value = {}
  },
)

async function save() {
  saving.value = true
  errors.value = {}
  try {
    const { budget, ...rest } = form.value
    const payload = {
      ...rest,
      description: rest.description || null,
      notes: rest.notes || null,
      starts_on: rest.starts_on || null,
      ends_on: rest.ends_on || null,
      budget_cents: eurosToCents(budget),
    }
    props.project
      ? await projectsApi.update(props.project.id, payload)
      : await projectsApi.create(payload)
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
    <v-card :title="project ? 'Projekt bearbeiten' : 'Neues Projekt'">
      <v-card-text>
        <v-form @submit.prevent="save">
          <v-text-field v-model="form.name" label="Name" :error-messages="errors.name" />
          <v-textarea
            v-model="form.description"
            label="Beschreibung"
            rows="2"
            :error-messages="errors.description"
          />
          <v-row density="comfortable">
            <v-col cols="6">
              <v-select
                v-model="form.status"
                label="Status"
                :items="STATUS_OPTIONS"
                :error-messages="errors.status"
              />
            </v-col>
            <v-col cols="6">
              <v-text-field
                v-model="form.budget"
                label="Budget (€)"
                :error-messages="errors.budget_cents"
              />
            </v-col>
          </v-row>
          <v-row density="comfortable">
            <v-col cols="6">
              <v-text-field
                v-model="form.starts_on"
                label="Von"
                type="date"
                clearable
                :error-messages="errors.starts_on"
              />
            </v-col>
            <v-col cols="6">
              <v-text-field
                v-model="form.ends_on"
                label="Bis"
                type="date"
                clearable
                :error-messages="errors.ends_on"
              />
            </v-col>
          </v-row>
          <v-textarea v-model="form.notes" label="Notizen" rows="2" :error-messages="errors.notes" />
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
