<script setup>
import { ref, watch } from 'vue'

import categoriesApi from '@/api/categories'
import itemsApi from '@/api/items'
import locationsApi from '@/api/locations'
import tagsApi from '@/api/tags'
import { centsToEuros, eurosToCents } from '@/lib/money'

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  item: { type: Object, default: null },
})
const emit = defineEmits(['update:modelValue', 'saved'])

const form = ref({})
const errors = ref({})
const saving = ref(false)
const categories = ref([])
const locations = ref([])
const knownTags = ref([])

function reset() {
  const item = props.item
  form.value = {
    name: item?.name ?? '',
    description: item?.description ?? '',
    notes: item?.notes ?? '',
    serial_number: item?.serial_number ?? '',
    quantity: item?.quantity ?? 1,
    category_id: item?.category_id ?? null,
    location_id: item?.location_id ?? null,
    project_id: item?.project_id ?? null,
    purchased_at: item?.purchased_at ?? null,
    warranty_until: item?.warranty_until ?? null,
    purchase_price: centsToEuros(item?.purchase_price_cents),
    current_value: centsToEuros(item?.current_value_cents),
    tags: item?.tags?.map((tag) => tag.name) ?? [],
  }
  errors.value = {}
}

watch(
  () => props.modelValue,
  async (open) => {
    if (!open) return
    reset()
    categories.value = await categoriesApi.list({ type: 'item' })
    locations.value = await locationsApi.list()
    knownTags.value = (await tagsApi.list()).map((tag) => tag.name)
  },
)

async function save() {
  saving.value = true
  errors.value = {}

  const { purchase_price, current_value, ...rest } = form.value
  const payload = {
    ...rest,
    description: rest.description || null,
    notes: rest.notes || null,
    serial_number: rest.serial_number || null,
    purchased_at: rest.purchased_at || null,
    warranty_until: rest.warranty_until || null,
    purchase_price_cents: eurosToCents(purchase_price),
    current_value_cents: eurosToCents(current_value),
  }

  try {
    const saved = props.item
      ? await itemsApi.update(props.item.id, payload)
      : await itemsApi.create(payload)
    emit('update:modelValue', false)
    emit('saved', saved)
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
    max-width="640"
    @update:model-value="emit('update:modelValue', $event)"
  >
    <v-card :title="item ? 'Gegenstand bearbeiten' : 'Neuer Gegenstand'">
      <v-card-text>
        <v-form @submit.prevent="save">
          <v-text-field v-model="form.name" label="Name" :error-messages="errors.name" />
          <v-row density="comfortable">
            <v-col cols="12" sm="6">
              <v-select
                v-model="form.category_id"
                label="Kategorie"
                :items="categories"
                item-title="name"
                item-value="id"
                clearable
                :error-messages="errors.category_id"
              />
            </v-col>
            <v-col cols="12" sm="6">
              <v-select
                v-model="form.location_id"
                label="Ort"
                :items="locations"
                item-title="name"
                item-value="id"
                clearable
                :error-messages="errors.location_id"
              />
            </v-col>
          </v-row>
          <v-combobox
            v-model="form.tags"
            label="Tags"
            :items="knownTags"
            multiple
            chips
            closable-chips
            :error-messages="errors.tags"
          />
          <v-row density="comfortable">
            <v-col cols="6" sm="3">
              <v-text-field
                v-model.number="form.quantity"
                label="Menge"
                type="number"
                min="1"
                :error-messages="errors.quantity"
              />
            </v-col>
            <v-col cols="6" sm="3">
              <v-text-field
                v-model="form.purchase_price"
                label="Kaufpreis (€)"
                :error-messages="errors.purchase_price_cents"
              />
            </v-col>
            <v-col cols="6" sm="3">
              <v-text-field
                v-model="form.current_value"
                label="Wert (€)"
                :error-messages="errors.current_value_cents"
              />
            </v-col>
            <v-col cols="6" sm="3">
              <v-text-field
                v-model="form.serial_number"
                label="Seriennummer"
                :error-messages="errors.serial_number"
              />
            </v-col>
          </v-row>
          <v-row density="comfortable">
            <v-col cols="12" sm="6">
              <v-text-field
                v-model="form.purchased_at"
                label="Kaufdatum"
                type="date"
                clearable
                :error-messages="errors.purchased_at"
              />
            </v-col>
            <v-col cols="12" sm="6">
              <v-text-field
                v-model="form.warranty_until"
                label="Garantie bis"
                type="date"
                clearable
                :error-messages="errors.warranty_until"
              />
            </v-col>
          </v-row>
          <v-textarea
            v-model="form.description"
            label="Beschreibung"
            rows="2"
            :error-messages="errors.description"
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
