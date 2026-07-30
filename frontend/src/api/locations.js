import http from '@/api/http'

export default {
  list: (params) => http.get('/locations', { params }).then((r) => r.data.data),
  create: (data) => http.post('/locations', data).then((r) => r.data.data),
  update: (id, data) => http.patch(`/locations/${id}`, data).then((r) => r.data.data),
  destroy: (id) => http.delete(`/locations/${id}`),
}
