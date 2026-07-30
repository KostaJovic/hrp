<script setup>
import { computed, ref, watch } from 'vue'

import budgetsApi from '@/api/budgets'
import categoriesApi from '@/api/categories'
import { centsToEuros, eurosToCents } from '@/lib/money'

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  budget: { type: Object, default: null },
  existingCategoryIds: { type: Array, default: () => [] },
})
const emit = defineEmits(['update:modelValue', 'saved'])

const form = ref({})
const errors = ref({})
const saving = ref(false)
const categories = ref([])

const selectableCategories = computed(() =>
  props.budget
    ? categories.value
    : categories.value.filter((category) => !props.existingCategoryIds.includes(category.id)),
)

watch(
  () => props.modelValue,
  async (open) => {
    if (!open) return
    form.value = {
      category_id: props.budget?.category_id ?? null,
      amount: centsToEuros(props.budget?.amount_cents),
    }
    errors.value = {}
    categories.value = await categoriesApi.list({ type: 'expense' })
  },
)

async function save() {
  saving.value = true
  errors.value = {}
  try {
    if (props.budget) {
      await budgetsApi.update(props.budget.id, { amount_cents: eurosToCents(form.value.amount) })
    } else {
      await budgetsApi.create({
        category_id: form.value.category_id,
        amount_cents: eurosToCents(form.value.amount),
      })
    }
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
    max-width="420"
    @update:model-value="emit('update:modelValue', $event)"
  >
    <v-card :title="budget ? `Budget: ${budget.category.name}` : 'Neues Budget'">
      <v-card-text>
        <v-form @submit.prevent="save">
          <v-select
            v-if="!budget"
            v-model="form.category_id"
            label="Kategorie"
            :items="selectableCategories"
            item-title="name"
            item-value="id"
            :error-messages="errors.category_id"
          />
          <v-text-field
            v-model="form.amount"
            label="Monatsbudget (€)"
            :error-messages="errors.amount_cents"
          />
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
