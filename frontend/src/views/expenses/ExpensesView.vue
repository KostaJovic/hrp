<script setup>
import { computed, onMounted, ref, watch } from 'vue'

import budgetsApi from '@/api/budgets'
import categoriesApi from '@/api/categories'
import expensesApi from '@/api/expenses'
import { formatCents } from '@/lib/money'

import BudgetFormDialog from './BudgetFormDialog.vue'
import ExpenseFormDialog from './ExpenseFormDialog.vue'

const UNIT_LABELS = { day: 'Tag(e)', week: 'Woche(n)', month: 'Monat(e)', year: 'Jahr(e)' }

const tab = ref('expenses')
const month = ref(new Date().toISOString().slice(0, 7))
const expenses = ref([])
const budgets = ref([])
const subscriptions = ref([])
const categories = ref([])
const categoryFilter = ref(null)

const expenseDialog = ref(false)
const editingExpense = ref(null)
const budgetDialog = ref(false)
const editingBudget = ref(null)

const today = new Date().toISOString().slice(0, 10)

const monthLabel = computed(() =>
  new Intl.DateTimeFormat('de-AT', { month: 'long', year: 'numeric' }).format(new Date(`${month.value}-01`)),
)

const monthTotal = computed(() =>
  expenses.value.reduce((sum, expense) => sum + expense.amount_cents, 0),
)

const monthlySubscriptionTotal = computed(() =>
  subscriptions.value
    .filter((expense) => expense.recurrence_unit === 'month' && expense.recurrence_interval === 1)
    .reduce((sum, expense) => sum + expense.amount_cents, 0),
)

function shiftMonth(delta) {
  const date = new Date(`${month.value}-01`)
  date.setMonth(date.getMonth() + delta)
  month.value = date.toISOString().slice(0, 7)
}

function monthRange() {
  const start = `${month.value}-01`
  const [year, monthNumber] = month.value.split('-').map(Number)
  const end = `${month.value}-${String(new Date(year, monthNumber, 0).getDate()).padStart(2, '0')}`
  return { from: start, to: end }
}

async function loadExpenses() {
  expenses.value = (
    await expensesApi.list({
      ...monthRange(),
      ...(categoryFilter.value ? { category_id: categoryFilter.value } : {}),
      per_page: 100,
    })
  ).data
}

async function loadBudgets() {
  budgets.value = await budgetsApi.list({ month: month.value })
}

async function loadSubscriptions() {
  subscriptions.value = (await expensesApi.list({ recurring: 1, per_page: 100 })).data
    .sort((a, b) => (a.next_due_on ?? '9999') < (b.next_due_on ?? '9999') ? -1 : 1)
}

async function reload() {
  await Promise.all([loadExpenses(), loadBudgets(), loadSubscriptions()])
}

watch(month, reload)
watch(categoryFilter, loadExpenses)

function budgetProgress(budget) {
  if (!budget.amount_cents) return { percent: 100, color: 'error' }
  const percent = Math.round((budget.spent_cents / budget.amount_cents) * 100)
  return {
    percent: Math.min(percent, 100),
    color: percent > 100 ? 'error' : percent >= 80 ? 'warning' : 'success',
    over: budget.spent_cents > budget.amount_cents ? budget.spent_cents - budget.amount_cents : null,
  }
}

function dueChip(expense) {
  if (!expense.next_due_on) return null
  if (expense.next_due_on <= today) return { text: `fällig ${expense.next_due_on}`, color: 'error' }
  return { text: `am ${expense.next_due_on}`, color: 'default' }
}

function openCreateExpense() {
  editingExpense.value = null
  expenseDialog.value = true
}

function openEditExpense(expense) {
  editingExpense.value = expense
  expenseDialog.value = true
}

async function removeExpense(expense) {
  await expensesApi.destroy(expense.id)
  await reload()
}

function openCreateBudget() {
  editingBudget.value = null
  budgetDialog.value = true
}

function openEditBudget(budget) {
  editingBudget.value = budget
  budgetDialog.value = true
}

async function removeBudget(budget) {
  await budgetsApi.destroy(budget.id)
  await loadBudgets()
}

onMounted(async () => {
  await reload()
  categories.value = await categoriesApi.list({ type: 'expense' })
})
</script>

<template>
  <div>
    <div class="d-flex align-center mb-4">
      <h1 class="text-h5">Ausgaben</h1>
      <v-spacer />
      <v-btn
        v-if="tab === 'budgets'"
        color="primary"
        prepend-icon="mdi-plus"
        @click="openCreateBudget"
      >
        Neues Budget
      </v-btn>
      <v-btn v-else color="primary" prepend-icon="mdi-plus" @click="openCreateExpense">
        Neue Ausgabe
      </v-btn>
    </div>

    <div class="d-flex align-center mb-3">
      <v-btn icon="mdi-chevron-left" variant="text" size="small" @click="shiftMonth(-1)" />
      <span class="text-subtitle-1 mx-2" style="min-width: 140px; text-align: center">
        {{ monthLabel }}
      </span>
      <v-btn icon="mdi-chevron-right" variant="text" size="small" @click="shiftMonth(1)" />
    </div>

    <v-tabs v-model="tab" class="mb-4">
      <v-tab value="expenses">Ausgaben</v-tab>
      <v-tab value="budgets">Budgets</v-tab>
      <v-tab value="calendar">Kalender</v-tab>
    </v-tabs>

    <template v-if="tab === 'expenses'">
      <div class="d-flex align-center mb-3 flex-wrap ga-3">
        <v-select
          v-model="categoryFilter"
          label="Kategorie"
          :items="categories"
          item-title="name"
          item-value="id"
          clearable
          hide-details
          density="compact"
          max-width="240"
        />
        <v-spacer />
        <span class="text-subtitle-1">Summe: {{ formatCents(monthTotal) }}</span>
      </div>

      <v-card v-if="expenses.length">
        <v-list lines="two">
          <v-list-item v-for="expense in expenses" :key="expense.id" :title="expense.description">
            <template #subtitle>
              {{ expense.spent_on }}
              <span v-if="expense.category"> · {{ expense.category.name }}</span>
              <span v-if="expense.recurrence_interval"> · <v-icon icon="mdi-repeat" size="x-small" /> Abo</span>
            </template>
            <template #append>
              <span class="text-subtitle-2 mr-3">{{ formatCents(expense.amount_cents) }}</span>
              <v-btn
                icon="mdi-pencil"
                size="small"
                variant="text"
                title="Bearbeiten"
                @click="openEditExpense(expense)"
              />
              <v-btn
                icon="mdi-delete"
                size="small"
                variant="text"
                title="Löschen"
                @click="removeExpense(expense)"
              />
            </template>
          </v-list-item>
        </v-list>
      </v-card>
      <v-empty-state
        v-else
        icon="mdi-currency-eur-off"
        title="Keine Ausgaben in diesem Monat"
        text="Lege die erste Ausgabe an — schnell erfasst ist halb budgetiert."
      />
    </template>

    <template v-else-if="tab === 'budgets'">
      <v-card v-if="budgets.length">
        <v-list lines="two">
          <v-list-item v-for="budget in budgets" :key="budget.id" :title="budget.category.name">
            <template #subtitle>
              <div class="d-flex align-center mt-1">
                <v-progress-linear
                  :model-value="budgetProgress(budget).percent"
                  :color="budgetProgress(budget).color"
                  height="8"
                  rounded
                  style="max-width: 320px"
                />
                <span class="ml-3 text-no-wrap">
                  {{ formatCents(budget.spent_cents) }} / {{ formatCents(budget.amount_cents) }}
                  <strong v-if="budgetProgress(budget).over" class="text-error">
                    (+{{ formatCents(budgetProgress(budget).over) }})
                  </strong>
                </span>
              </div>
            </template>
            <template #append>
              <v-btn
                icon="mdi-pencil"
                size="small"
                variant="text"
                title="Bearbeiten"
                @click="openEditBudget(budget)"
              />
              <v-btn
                icon="mdi-delete"
                size="small"
                variant="text"
                title="Löschen"
                @click="removeBudget(budget)"
              />
            </template>
          </v-list-item>
        </v-list>
      </v-card>
      <v-empty-state
        v-else
        icon="mdi-chart-donut"
        title="Noch keine Budgets"
        text="Lege je Ausgabenkategorie ein Monatsbudget fest."
      />
    </template>

    <template v-else>
      <p v-if="monthlySubscriptionTotal" class="text-subtitle-2 mb-3">
        Monatliche Abos gesamt: {{ formatCents(monthlySubscriptionTotal) }}
      </p>
      <v-card v-if="subscriptions.length">
        <v-list lines="two">
          <v-list-item
            v-for="expense in subscriptions"
            :key="expense.id"
            :title="expense.description"
          >
            <template #subtitle>
              alle {{ expense.recurrence_interval }} {{ UNIT_LABELS[expense.recurrence_unit] }}
              <span v-if="expense.category"> · {{ expense.category.name }}</span>
            </template>
            <template #append>
              <span class="text-subtitle-2 mr-2">{{ formatCents(expense.amount_cents) }}</span>
              <v-chip
                v-if="dueChip(expense)"
                :color="dueChip(expense).color"
                size="small"
                class="mr-2"
              >
                {{ dueChip(expense).text }}
              </v-chip>
              <v-btn
                icon="mdi-pencil"
                size="small"
                variant="text"
                title="Bearbeiten"
                @click="openEditExpense(expense)"
              />
            </template>
          </v-list-item>
        </v-list>
      </v-card>
      <v-empty-state
        v-else
        icon="mdi-calendar-blank"
        title="Keine Abos oder wiederkehrenden Rechnungen"
        text="Markiere eine Ausgabe als wiederkehrend, dann erscheint sie hier mit Fälligkeit."
      />
    </template>

    <ExpenseFormDialog v-model="expenseDialog" :expense="editingExpense" @saved="reload" />
    <BudgetFormDialog
      v-model="budgetDialog"
      :budget="editingBudget"
      :existing-category-ids="budgets.map((budget) => budget.category_id)"
      @saved="loadBudgets"
    />
  </div>
</template>
