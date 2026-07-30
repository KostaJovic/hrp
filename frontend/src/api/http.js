import axios from 'axios'

import router from '@/router'

// Same-origin via the Vite proxy, so axios picks up Sanctum's XSRF-TOKEN
// cookie and sends the X-XSRF-TOKEN header automatically.
const http = axios.create({
  baseURL: '/api/v1',
  headers: { Accept: 'application/json' },
})

let csrfReady = false

export async function ensureCsrf() {
  if (!csrfReady) {
    await axios.get('/sanctum/csrf-cookie')
    csrfReady = true
  }
}

http.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401 && router.currentRoute.value.name !== 'login') {
      router.push({ name: 'login', query: { redirect: router.currentRoute.value.fullPath } })
    }
    return Promise.reject(error)
  },
)

export default http
