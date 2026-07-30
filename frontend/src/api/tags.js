import http from '@/api/http'

export default {
  list: () => http.get('/tags').then((r) => r.data.data),
}
