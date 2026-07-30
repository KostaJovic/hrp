import http from '@/api/http'

export default {
  list: (params) => http.get('/categories', { params }).then((r) => r.data.data),
}
