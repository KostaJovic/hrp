<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'

import itemsApi from '@/api/items'
import DocumentsSection from '@/components/DocumentsSection.vue'
import { formatCents } from '@/lib/money'

import ItemFormDialog from './ItemFormDialog.vue'

const route = useRoute()
const router = useRouter()

const item = ref(null)
const editDialog = ref(false)

const facts = computed(() => {
  if (!item.value) return []
  return [
    ['Kategorie', item.value.category?.name],
    ['Ort', item.value.location?.name],
    ['Menge', item.value.quantity > 1 ? item.value.quantity : null],
    ['Seriennummer', item.value.serial_number],
    ['Kaufdatum', item.value.purchased_at],
    ['Kaufpreis', formatCents(item.value.purchase_price_cents)],
    ['Aktueller Wert', formatCents(item.value.current_value_cents)],
    ['Garantie bis', item.value.warranty_until],
  ].filter(([, value]) => value !== null && value !== undefined && value !== '')
})

async function load() {
  item.value = await itemsApi.get(route.params.id)
}

async function removeItem() {
  await itemsApi.destroy(item.value.id)
  router.push('/items')
}

onMounted(load)
</script>

<template>
  <div v-if="item">
    <div class="d-flex align-center mb-4">
      <v-btn icon="mdi-arrow-left" variant="text" @click="router.push('/items')" />
      <h1 class="text-h5 ml-1">{{ item.name }}</h1>
      <v-spacer />
      <v-btn icon="mdi-pencil" variant="text" title="Bearbeiten" @click="editDialog = true" />
      <v-btn icon="mdi-delete" variant="text" title="Löschen" @click="removeItem" />
    </div>

    <v-row>
      <v-col cols="12" md="6">
        <v-card title="Details">
          <v-card-text>
            <v-table density="compact">
              <tbody>
                <tr v-for="[label, value] in facts" :key="label">
                  <td class="text-medium-emphasis" style="width: 40%">{{ label }}</td>
                  <td>{{ value }}</td>
                </tr>
              </tbody>
            </v-table>
            <div v-if="item.tags?.length" class="mt-3">
              <v-chip v-for="tag in item.tags" :key="tag.id" size="small" class="mr-1">
                {{ tag.name }}
              </v-chip>
            </div>
            <p v-if="item.description" class="mt-3 mb-0">{{ item.description }}</p>
            <p v-if="item.notes" class="mt-2 mb-0 text-medium-emphasis">{{ item.notes }}</p>
          </v-card-text>
        </v-card>
      </v-col>

      <v-col cols="12" md="6">
        <v-card title="Fotos & Dokumente">
          <v-card-text>
            <DocumentsSection documentable-type="item" :documentable-id="item.id" />
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>

    <ItemFormDialog v-model="editDialog" :item="item" @saved="load" />
  </div>
</template>
