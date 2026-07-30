<script setup>
import { onMounted, reactive, ref, watch } from 'vue'
import { useRouter } from 'vue-router'

import categoriesApi from '@/api/categories'
import itemsApi from '@/api/items'
import locationsApi from '@/api/locations'
import tagsApi from '@/api/tags'
import { formatCents } from '@/lib/money'

import ItemFormDialog from './ItemFormDialog.vue'

const router = useRouter()

const items = ref([])
const meta = ref(null)
const loading = ref(false)
const dialog = ref(false)
const editingItem = ref(null)

const categories = ref([])
const locations = ref([])
const tags = ref([])

const filters = reactive({
  q: '',
  category_id: null,
  location_id: null,
  tag: null,
  warrantyOnly: false,
  sort: '-created_at',
  page: 1,
})

const sortOptions = [
  { value: '-created_at', title: 'Neueste zuerst' },
  { value: 'name', title: 'Name A–Z' },
  { value: '-current_value_cents', title: 'Wert absteigend' },
  { value: '-purchased_at', title: 'Kaufdatum absteigend' },
]

async function load() {
  loading.value = true
  try {
    const params = {
      page: filters.page,
      sort: filters.sort,
      ...(filters.q ? { q: filters.q } : {}),
      ...(filters.category_id ? { category_id: filters.category_id } : {}),
      ...(filters.location_id ? { location_id: filters.location_id } : {}),
      ...(filters.tag ? { tag: filters.tag } : {}),
      ...(filters.warrantyOnly ? { warranty: 'active' } : {}),
    }
    const response = await itemsApi.list(params)
    items.value = response.data
    meta.value = response.meta
  } finally {
    loading.value = false
  }
}

let debounce
watch(
  () => ({ ...filters }),
  (next, prev) => {
    if (next.page !== prev.page) {
      load()
      return
    }
    filters.page = 1
    clearTimeout(debounce)
    debounce = setTimeout(load, next.q !== prev.q ? 300 : 0)
  },
)

function openCreate() {
  editingItem.value = null
  dialog.value = true
}

function openEdit(item) {
  editingItem.value = item
  dialog.value = true
}

async function refreshOptions() {
  tags.value = await tagsApi.list()
}

async function onSaved() {
  await Promise.all([load(), refreshOptions()])
}

onMounted(async () => {
  await load()
  categories.value = await categoriesApi.list({ type: 'item' })
  locations.value = await locationsApi.list()
  await refreshOptions()
})
</script>

<template>
  <div>
    <div class="d-flex align-center mb-4">
      <h1 class="text-h5">Gegenstände</h1>
      <v-spacer />
      <v-btn color="primary" prepend-icon="mdi-plus" @click="openCreate">Neuer Gegenstand</v-btn>
    </div>

    <v-row density="comfortable" class="mb-1">
      <v-col cols="12" md="4">
        <v-text-field
          v-model="filters.q"
          label="Suchen…"
          prepend-inner-icon="mdi-magnify"
          clearable
          hide-details
          density="compact"
        />
      </v-col>
      <v-col cols="6" md="2">
        <v-select
          v-model="filters.category_id"
          label="Kategorie"
          :items="categories"
          item-title="name"
          item-value="id"
          clearable
          hide-details
          density="compact"
        />
      </v-col>
      <v-col cols="6" md="2">
        <v-select
          v-model="filters.location_id"
          label="Ort"
          :items="locations"
          item-title="name"
          item-value="id"
          clearable
          hide-details
          density="compact"
        />
      </v-col>
      <v-col cols="6" md="2">
        <v-select
          v-model="filters.tag"
          label="Tag"
          :items="tags"
          item-title="name"
          item-value="name"
          clearable
          hide-details
          density="compact"
        />
      </v-col>
      <v-col cols="6" md="2">
        <v-select
          v-model="filters.sort"
          label="Sortierung"
          :items="sortOptions"
          hide-details
          density="compact"
        />
      </v-col>
    </v-row>
    <v-switch
      v-model="filters.warrantyOnly"
      label="Nur laufende Garantie"
      color="primary"
      density="compact"
      hide-details
      class="mb-2"
    />

    <v-progress-linear v-if="loading" indeterminate class="mb-2" />

    <v-card v-if="items.length">
      <v-list lines="two">
        <v-list-item
          v-for="item in items"
          :key="item.id"
          :title="item.name"
          @click="router.push(`/items/${item.id}`)"
        >
          <template #subtitle>
            <span v-if="item.category">{{ item.category.name }}</span>
            <span v-if="item.category && item.location"> · </span>
            <span v-if="item.location">{{ item.location.name }}</span>
            <span v-if="formatCents(item.current_value_cents ?? item.purchase_price_cents)">
              · {{ formatCents(item.current_value_cents ?? item.purchase_price_cents) }}
            </span>
          </template>
          <template #append>
            <v-chip
              v-for="tag in item.tags"
              :key="tag.id"
              size="x-small"
              class="ml-1 d-none d-sm-flex"
            >
              {{ tag.name }}
            </v-chip>
            <v-icon
              v-if="item.warranty_until && item.warranty_until >= new Date().toISOString().slice(0, 10)"
              icon="mdi-shield-check"
              color="success"
              size="small"
              class="ml-2"
              title="Garantie läuft"
            />
            <v-btn
              icon="mdi-pencil"
              size="small"
              variant="text"
              title="Bearbeiten"
              @click.stop="openEdit(item)"
            />
          </template>
        </v-list-item>
      </v-list>
    </v-card>

    <v-empty-state
      v-else-if="!loading"
      icon="mdi-package-variant"
      title="Keine Gegenstände gefunden"
      text="Filter anpassen oder einen neuen Gegenstand anlegen."
    />

    <v-pagination
      v-if="meta && meta.last_page > 1"
      v-model="filters.page"
      :length="meta.last_page"
      class="mt-4"
    />

    <ItemFormDialog v-model="dialog" :item="editingItem" @saved="onSaved" />
  </div>
</template>
