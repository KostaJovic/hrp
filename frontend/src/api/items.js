import http from '@/api/http'

export default {
  list: (params) => http.get('/items', { params }).then((r) => r.data),
  get: (id) => http.get(`/items/${id}`).then((r) => r.data.data),
  create: (data) => http.post('/items', data).then((r) => r.data.data),
  update: (id, data) => http.patch(`/items/${id}`, data).then((r) => r.data.data),
  destroy: (id) => http.delete(`/items/${id}`),
}
