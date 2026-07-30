<script setup>
import { ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'

import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
const route = useRoute()
const router = useRouter()

const email = ref('')
const password = ref('')
const error = ref(null)
const loading = ref(false)

async function submit() {
  loading.value = true
  error.value = null
  try {
    await auth.login(email.value, password.value)
    router.push(route.query.redirect || '/items')
  } catch (e) {
    error.value = e.response?.data?.message ?? 'Anmeldung fehlgeschlagen.'
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <v-main>
    <v-container class="fill-height" max-width="420">
      <v-card class="w-100" title="Anmelden">
        <v-card-text>
          <v-form @submit.prevent="submit">
            <v-text-field
              v-model="email"
              label="E-Mail"
              type="email"
              autocomplete="username"
              required
            />
            <v-text-field
              v-model="password"
              label="Passwort"
              type="password"
              autocomplete="current-password"
              required
            />
            <v-alert v-if="error" type="error" density="compact" class="mb-4">
              {{ error }}
            </v-alert>
            <v-btn type="submit" color="primary" block :loading="loading">Anmelden</v-btn>
          </v-form>
        </v-card-text>
      </v-card>
    </v-container>
  </v-main>
</template>
