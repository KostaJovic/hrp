<script setup>
import { ref, watch } from 'vue'

import { plans as plansApi } from '@/api/maintenance'

const UNIT_OPTIONS = [
  { value: 'day', title: 'Tag(e)' },
  { value: 'week', title: 'Woche(n)' },
  { value: 'month', title: 'Monat(e)' },
  { value: 'year', title: 'Jahr(e)' },
]

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  plan: { type: Object, default: null },
  items: { type: Array, default: () => [] },
})
const emit = defineEmits(['update:modelValue', 'saved'])

const form = ref({})
const errors = ref({})
const saving = ref(false)

watch(
  () => props.modelValue,
  (open) => {
    if (!open) return
    form.value = {
      item_id: props.plan?.item_id ?? null,
      name: props.plan?.name ?? '',
      notes: props.plan?.notes ?? '',
      recurrence_interval: props.plan?.recurrence_interval ?? 6,
      recurrence_unit: props.plan?.recurrence_unit ?? 'month',
      next_due_on: props.plan?.next_due_on ?? null,
    }
    errors.value = {}
  },
)

async function save() {
  saving.value = true
  errors.value = {}
  try {
    const payload = { ...form.value, notes: form.value.notes || null }
    props.plan
      ? await plansApi.update(props.plan.id, payload)
      : await plansApi.create(payload)
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
    max-width="520"
    @update:model-value="emit('update:modelValue', $event)"
  >
    <v-card :title="plan ? 'Wartungsplan bearbeiten' : 'Neuer Wartungsplan'">
      <v-card-text>
        <v-form @submit.prevent="save">
          <v-select
            v-model="form.item_id"
            label="Gegenstand"
            :items="items"
            item-title="name"
            item-value="id"
            :error-messages="errors.item_id"
          />
          <v-text-field v-model="form.name" label="Bezeichnung" :error-messages="errors.name" />
          <v-row density="comfortable">
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
          <v-text-field
            v-model="form.next_due_on"
            label="Nächste Fälligkeit"
            type="date"
            :error-messages="errors.next_due_on"
          />
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
