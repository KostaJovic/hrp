<script setup>
import { ref, watch } from 'vue'

import { logs as logsApi } from '@/api/maintenance'
import { centsToEuros, eurosToCents } from '@/lib/money'

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  log: { type: Object, default: null },
  // When set, the dialog records work against this plan (item preset + locked).
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
      item_id: props.log?.item_id ?? props.plan?.item_id ?? null,
      performed_on: props.log?.performed_on ?? new Date().toISOString().slice(0, 10),
      cost: centsToEuros(props.log?.cost_cents),
      notes: props.log?.notes ?? '',
    }
    errors.value = {}
  },
)

async function save() {
  saving.value = true
  errors.value = {}
  try {
    const payload = {
      performed_on: form.value.performed_on,
      cost_cents: eurosToCents(form.value.cost),
      notes: form.value.notes || null,
    }
    if (props.log) {
      await logsApi.update(props.log.id, payload)
    } else {
      await logsApi.create({
        ...payload,
        item_id: form.value.item_id,
        maintenance_plan_id: props.plan?.id ?? null,
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
    max-width="520"
    @update:model-value="emit('update:modelValue', $event)"
  >
    <v-card
      :title="log ? 'Wartungseintrag bearbeiten' : plan ? `Erledigt: ${plan.name}` : 'Neuer Wartungseintrag'"
    >
      <v-card-text>
        <v-form @submit.prevent="save">
          <v-select
            v-if="!log"
            v-model="form.item_id"
            label="Gegenstand"
            :items="items"
            item-title="name"
            item-value="id"
            :disabled="!!plan"
            :error-messages="errors.item_id"
          />
          <v-text-field
            v-model="form.performed_on"
            label="Ausgeführt am"
            type="date"
            :error-messages="errors.performed_on"
          />
          <v-text-field
            v-model="form.cost"
            label="Kosten (€)"
            :error-messages="errors.cost_cents"
          />
          <v-textarea v-model="form.notes" label="Notizen" rows="3" :error-messages="errors.notes" />
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
