<script setup>
import { ref, watch } from 'vue'

import categoriesApi from '@/api/categories'
import expensesApi from '@/api/expenses'
import itemsApi from '@/api/items'
import projectsApi from '@/api/projects'
import { centsToEuros, eurosToCents } from '@/lib/money'

const UNIT_OPTIONS = [
  { value: 'day', title: 'Tag(e)' },
  { value: 'week', title: 'Woche(n)' },
  { value: 'month', title: 'Monat(e)' },
  { value: 'year', title: 'Jahr(e)' },
]

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  expense: { type: Object, default: null },
})
const emit = defineEmits(['update:modelValue', 'saved'])

const form = ref({})
const errors = ref({})
const saving = ref(false)
const recurring = ref(false)
const categories = ref([])
const projects = ref([])
const items = ref([])

watch(
  () => props.modelValue,
  async (open) => {
    if (!open) return
    const expense = props.expense
    form.value = {
      description: expense?.description ?? '',
      amount: centsToEuros(expense?.amount_cents),
      category_id: expense?.category_id ?? null,
      spent_on: expense?.spent_on ?? new Date().toISOString().slice(0, 10),
      project_id: expense?.project_id ?? null,
      item_id: expense?.item_id ?? null,
      recurrence_interval: expense?.recurrence_interval ?? 1,
      recurrence_unit: expense?.recurrence_unit ?? 'month',
      next_due_on: expense?.next_due_on ?? null,
    }
    recurring.value = expense ? expense.recurrence_interval !== null : false
    errors.value = {}
    categories.value = await categoriesApi.list({ type: 'expense' })
    projects.value = await projectsApi.list()
    items.value = (await itemsApi.list({ per_page: 100, sort: 'name' })).data
  },
)

async function save() {
  saving.value = true
  errors.value = {}
  try {
    const { amount, ...rest } = form.value
    const payload = {
      ...rest,
      amount_cents: eurosToCents(amount),
      recurrence_interval: recurring.value ? rest.recurrence_interval : null,
      recurrence_unit: recurring.value ? rest.recurrence_unit : null,
      next_due_on: recurring.value ? rest.next_due_on : null,
    }
    props.expense
      ? await expensesApi.update(props.expense.id, payload)
      : await expensesApi.create(payload)
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
    <v-card :title="expense ? 'Ausgabe bearbeiten' : 'Neue Ausgabe'">
      <v-card-text>
        <v-form @submit.prevent="save">
          <v-text-field
            v-model="form.description"
            label="Beschreibung"
            :error-messages="errors.description"
          />
          <v-row density="comfortable">
            <v-col cols="6">
              <v-text-field
                v-model="form.amount"
                label="Betrag (€)"
                :error-messages="errors.amount_cents"
              />
            </v-col>
            <v-col cols="6">
              <v-text-field
                v-model="form.spent_on"
                label="Datum"
                type="date"
                :error-messages="errors.spent_on"
              />
            </v-col>
          </v-row>
          <v-select
            v-model="form.category_id"
            label="Kategorie"
            :items="categories"
            item-title="name"
            item-value="id"
            clearable
            :error-messages="errors.category_id"
          />
          <v-row density="comfortable">
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
            <v-col cols="6">
              <v-select
                v-model="form.item_id"
                label="Gegenstand"
                :items="items"
                item-title="name"
                item-value="id"
                clearable
                :error-messages="errors.item_id"
              />
            </v-col>
          </v-row>
          <v-switch v-model="recurring" label="Wiederkehrend (Abo/Rechnung)" color="primary" hide-details />
          <v-row v-if="recurring" density="comfortable">
            <v-col cols="3">
              <v-text-field
                v-model.number="form.recurrence_interval"
                label="Alle"
                type="number"
                min="1"
                :error-messages="errors.recurrence_interval"
              />
            </v-col>
            <v-col cols="4">
              <v-select
                v-model="form.recurrence_unit"
                label="Einheit"
                :items="UNIT_OPTIONS"
                :error-messages="errors.recurrence_unit"
              />
            </v-col>
            <v-col cols="5">
              <v-text-field
                v-model="form.next_due_on"
                label="Nächste Fälligkeit"
                type="date"
                :error-messages="errors.next_due_on"
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
