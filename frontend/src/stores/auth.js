import { defineStore } from 'pinia'
import { ref } from 'vue'

import http, { ensureCsrf } from '@/api/http'

export const useAuthStore = defineStore('auth', () => {
  const user = ref(null)
  const checked = ref(false)

  async function fetchUser() {
    try {
      user.value = (await http.get('/user')).data
    } catch {
      user.value = null
    } finally {
      checked.value = true
    }
  }

  async function login(email, password) {
    await ensureCsrf()
    user.value = (await http.post('/login', { email, password })).data
    checked.value = true
  }

  async function logout() {
    await http.post('/logout')
    user.value = null
  }

  return { user, checked, fetchUser, login, logout }
})
