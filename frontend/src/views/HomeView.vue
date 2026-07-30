<script setup>
import { ref, onMounted } from 'vue'

const api = ref(null)

onMounted(async () => {
  const res = await fetch('/api/ping')
  api.value = await res.json()
})
</script>

<template>
  <v-card title="Home">
    <v-card-text>
      <template v-if="api">Backend erreichbar — Laravel {{ api.laravel }}, MySQL {{ api.mysql }}</template>
      <v-progress-circular v-else indeterminate size="20" />
    </v-card-text>
  </v-card>
</template>
