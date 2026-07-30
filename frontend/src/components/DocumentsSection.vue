<script setup>
import { computed, onMounted, ref, watch } from 'vue'

import documentsApi from '@/api/documents'

const KIND_LABELS = {
  photo: 'Foto',
  receipt: 'Beleg',
  invoice: 'Rechnung',
  manual: 'Handbuch',
  warranty: 'Garantie',
  other: 'Sonstiges',
}

const props = defineProps({
  documentableType: { type: String, required: true },
  documentableId: { type: [Number, String], required: true },
})

const documents = ref([])
const uploading = ref(false)
const uploadKind = ref('photo')
const uploadFile = ref(null)
const error = ref(null)

const photos = computed(() => documents.value.filter((doc) => doc.kind === 'photo'))
const files = computed(() => documents.value.filter((doc) => doc.kind !== 'photo'))
const kindOptions = Object.entries(KIND_LABELS).map(([value, title]) => ({ value, title }))

async function load() {
  documents.value = await documentsApi.listFor(props.documentableType, props.documentableId)
}

async function upload() {
  if (!uploadFile.value) return
  uploading.value = true
  error.value = null
  try {
    await documentsApi.upload(props.documentableType, props.documentableId, {
      file: uploadFile.value,
      kind: uploadKind.value,
    })
    uploadFile.value = null
    await load()
  } catch {
    error.value = 'Upload fehlgeschlagen.'
  } finally {
    uploading.value = false
  }
}

async function removeDocument(doc) {
  await documentsApi.destroy(doc.id)
  await load()
}

watch(() => props.documentableId, load)
onMounted(load)
</script>

<template>
  <div>
    <v-row v-if="photos.length" density="comfortable" class="mb-2">
      <v-col v-for="photo in photos" :key="photo.id" cols="4">
        <v-img :src="documentsApi.downloadUrl(photo)" aspect-ratio="1" cover class="rounded" />
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
          <v-btn icon="mdi-delete" size="small" variant="text" @click.prevent="removeDocument(doc)" />
        </template>
      </v-list-item>
    </v-list>

    <p v-if="!documents.length" class="text-medium-emphasis">Noch keine Fotos oder Dokumente.</p>

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
  </div>
</template>
