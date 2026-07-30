<script setup>
import { computed, onMounted, ref } from 'vue'

import itemsApi from '@/api/items'
import { logs as logsApi, plans as plansApi } from '@/api/maintenance'
import DocumentsSection from '@/components/DocumentsSection.vue'
import { formatCents } from '@/lib/money'

import LogFormDialog from './LogFormDialog.vue'
import PlanFormDialog from './PlanFormDialog.vue'

const tab = ref('calendar')
const plans = ref([])
const logs = ref([])
const items = ref([])
const itemFilter = ref(null)

const planDialog = ref(false)
const editingPlan = ref(null)
const logDialog = ref(false)
const editingLog = ref(null)
const completingPlan = ref(null)

const itemName = (id) => items.value.find((item) => item.id === id)?.name ?? '–'

const today = new Date().toISOString().slice(0, 10)

function dueLabel(plan) {
  const days = Math.round((new Date(plan.next_due_on) - new Date(today)) / 86_400_000)
  if (days < 0) return { text: `überfällig seit ${-days} Tag(en)`, color: 'error' }
  if (days === 0) return { text: 'heute fällig', color: 'error' }
  if (days <= 14) return { text: `in ${days} Tag(en)`, color: 'warning' }
  return { text: `am ${plan.next_due_on}`, color: 'default' }
}

const filteredLogs = computed(() =>
  itemFilter.value ? logs.value.filter((log) => log.item_id === itemFilter.value) : logs.value,
)

async function loadPlans() {
  plans.value = await plansApi.list(itemFilter.value ? { item_id: itemFilter.value } : {})
}

async function loadLogs() {
  logs.value = (await logsApi.list({ per_page: 100 })).data
}

async function reload() {
  await Promise.all([loadPlans(), loadLogs()])
}

function openCreatePlan() {
  editingPlan.value = null
  planDialog.value = true
}

function openEditPlan(plan) {
  editingPlan.value = plan
  planDialog.value = true
}

async function removePlan(plan) {
  await plansApi.destroy(plan.id)
  await reload()
}

function openComplete(plan) {
  completingPlan.value = plan
  editingLog.value = null
  logDialog.value = true
}

function openCreateLog() {
  completingPlan.value = null
  editingLog.value = null
  logDialog.value = true
}

function openEditLog(log) {
  completingPlan.value = null
  editingLog.value = log
  logDialog.value = true
}

async function removeLog(log) {
  await logsApi.destroy(log.id)
  await loadLogs()
}

onMounted(async () => {
  items.value = (await itemsApi.list({ per_page: 100, sort: 'name' })).data
  await reload()
})
</script>

<template>
  <div>
    <div class="d-flex align-center mb-4">
      <h1 class="text-h5">Wartung</h1>
      <v-spacer />
      <v-btn
        v-if="tab === 'calendar'"
        color="primary"
        prepend-icon="mdi-plus"
        @click="openCreatePlan"
      >
        Neuer Plan
      </v-btn>
      <v-btn v-else color="primary" prepend-icon="mdi-plus" @click="openCreateLog">
        Neuer Eintrag
      </v-btn>
    </div>

    <v-select
      v-model="itemFilter"
      label="Gegenstand"
      :items="items"
      item-title="name"
      item-value="id"
      clearable
      hide-details
      density="compact"
      max-width="320"
      class="mb-3"
      @update:model-value="loadPlans"
    />

    <v-tabs v-model="tab" class="mb-4">
      <v-tab value="calendar">Kalender</v-tab>
      <v-tab value="history">Historie</v-tab>
    </v-tabs>

    <template v-if="tab === 'calendar'">
      <v-card v-if="plans.length">
        <v-list lines="two">
          <v-list-item
            v-for="plan in plans"
            :key="plan.id"
            :title="plan.name"
            :subtitle="`${plan.item?.name ?? itemName(plan.item_id)} · alle ${plan.recurrence_interval} ${
              { day: 'Tag(e)', week: 'Woche(n)', month: 'Monat(e)', year: 'Jahr(e)' }[plan.recurrence_unit]
            }`"
          >
            <template #append>
              <v-chip :color="dueLabel(plan).color" size="small" class="mr-2">
                {{ dueLabel(plan).text }}
              </v-chip>
              <v-btn
                icon="mdi-check"
                size="small"
                variant="text"
                title="Erledigt"
                @click="openComplete(plan)"
              />
              <v-btn
                icon="mdi-pencil"
                size="small"
                variant="text"
                title="Bearbeiten"
                @click="openEditPlan(plan)"
              />
              <v-btn
                icon="mdi-delete"
                size="small"
                variant="text"
                title="Löschen"
                @click="removePlan(plan)"
              />
            </template>
          </v-list-item>
        </v-list>
      </v-card>
      <v-empty-state
        v-else
        icon="mdi-wrench"
        title="Keine Wartungspläne"
        text="Lege den ersten wiederkehrenden Plan an, z. B. Boiler entkalken alle 6 Monate."
      />
    </template>

    <template v-else>
      <v-expansion-panels v-if="filteredLogs.length" variant="accordion">
        <v-expansion-panel v-for="log in filteredLogs" :key="log.id">
          <v-expansion-panel-title>
            <div class="d-flex align-center flex-wrap w-100">
              <span class="mr-3">{{ log.performed_on }}</span>
              <span class="font-weight-medium mr-3">{{ log.item?.name ?? itemName(log.item_id) }}</span>
              <span v-if="log.cost_cents !== null" class="text-medium-emphasis mr-3">
                {{ formatCents(log.cost_cents) }}
              </span>
              <v-chip v-if="log.maintenance_plan_id" size="x-small" class="mr-2">Plan</v-chip>
            </div>
          </v-expansion-panel-title>
          <v-expansion-panel-text>
            <p v-if="log.notes" class="mb-3">{{ log.notes }}</p>
            <DocumentsSection documentable-type="maintenance_log" :documentable-id="log.id" />
            <div class="d-flex justify-end mt-2">
              <v-btn
                size="small"
                variant="text"
                prepend-icon="mdi-pencil"
                @click="openEditLog(log)"
              >
                Bearbeiten
              </v-btn>
              <v-btn
                size="small"
                variant="text"
                prepend-icon="mdi-delete"
                @click="removeLog(log)"
              >
                Löschen
              </v-btn>
            </div>
          </v-expansion-panel-text>
        </v-expansion-panel>
      </v-expansion-panels>
      <v-empty-state
        v-else
        icon="mdi-history"
        title="Noch keine Wartungshistorie"
        text="Erledigte Pläne und freie Einträge landen hier."
      />
    </template>

    <PlanFormDialog v-model="planDialog" :plan="editingPlan" :items="items" @saved="reload" />
    <LogFormDialog
      v-model="logDialog"
      :log="editingLog"
      :plan="completingPlan"
      :items="items"
      @saved="reload"
    />
  </div>
</template>
