import http from '@/api/http'

export default {
  list: (params) => http.get('/projects', { params }).then((r) => r.data.data),
}
