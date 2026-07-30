<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'

import documentsApi from '@/api/documents'
import itemsApi from '@/api/items'
import { formatCents } from '@/lib/money'

import ItemFormDialog from './ItemFormDialog.vue'

const KIND_LABELS = {
  photo: 'Foto',
  receipt: 'Beleg',
  invoice: 'Rechnung',
  manual: 'Handbuch',
  warranty: 'Garantie',
  other: 'Sonstiges',
}

const route = useRoute()
const router = useRouter()

const item = ref(null)
const documents = ref([])
const editDialog = ref(false)
const uploading = ref(false)
const uploadKind = ref('photo')
const uploadFile = ref(null)
const error = ref(null)

const photos = computed(() => documents.value.filter((doc) => doc.kind === 'photo'))
const files = computed(() => documents.value.filter((doc) => doc.kind !== 'photo'))

const kindOptions = Object.entries(KIND_LABELS).map(([value, title]) => ({ value, title }))

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
  documents.value = await documentsApi.listFor('item', route.params.id)
}

async function upload() {
  if (!uploadFile.value) return
  uploading.value = true
  error.value = null
  try {
    await documentsApi.upload('item', item.value.id, {
      file: uploadFile.value,
      kind: uploadKind.value,
    })
    uploadFile.value = null
    documents.value = await documentsApi.listFor('item', item.value.id)
  } catch {
    error.value = 'Upload fehlgeschlagen.'
  } finally {
    uploading.value = false
  }
}

async function removeDocument(doc) {
  await documentsApi.destroy(doc.id)
  documents.value = await documentsApi.listFor('item', item.value.id)
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
            <v-row v-if="photos.length" dense class="mb-2">
              <v-col v-for="photo in photos" :key="photo.id" cols="4">
                <v-img
                  :src="documentsApi.downloadUrl(photo)"
                  aspect-ratio="1"
                  cover
                  class="rounded"
                />
                <v-btn
                  block
                  size="x-small"
                  variant="text"
                  prepend-icon="mdi-delete"
                  @click="removeDocument(photo)"
                >
                  Entfernen
                </v-btn>
              </v-col>
            </v-row>

            <v-list v-if="files.length" density="compact">
              <v-list-item
                v-for="doc in files"
                :key="doc.id"
                :title="doc.title || doc.original_name"
                :subtitle="KIND_LABELS[doc.kind]"
                :href="documentsApi.downloadUrl(doc)"
                prepend-icon="mdi-file-document"
              >
                <template #append>
                  <v-btn
                    icon="mdi-delete"
                    size="small"
                    variant="text"
                    @click.prevent="removeDocument(doc)"
                  />
                </template>
              </v-list-item>
            </v-list>

            <p v-if="!documents.length" class="text-medium-emphasis">
              Noch keine Fotos oder Dokumente.
            </p>

            <v-divider class="my-3" />

            <!-- capture opens the camera directly on phones -->
            <v-file-input
              v-model="uploadFile"
              label="Datei auswählen"
              density="compact"
              accept="image/*,.pdf"
              capture="environment"
            />
            <div class="d-flex align-center">
              <v-select
                v-model="uploadKind"
                :items="kindOptions"
                label="Art"
                density="compact"
                hide-details
                max-width="200"
              />
              <v-btn
                color="primary"
                class="ml-3"
                :loading="uploading"
                :disabled="!uploadFile"
                @click="upload"
              >
                Hochladen
              </v-btn>
            </div>
            <v-alert v-if="error" type="error" density="compact" class="mt-3">{{ error }}</v-alert>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>

    <ItemFormDialog v-model="editDialog" :item="item" @saved="load" />
  </div>
</template>
