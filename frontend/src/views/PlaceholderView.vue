<script setup>
defineProps({ title: { type: String, required: true } })
</script>

<template>
  <div>
    <h1 class="text-h5 mb-4">{{ title }}</h1>
    <v-alert type="info" variant="tonal">
      Dieses Modul kommt mit einem späteren MVP-Schritt.
    </v-alert>
  </div>
</template>
