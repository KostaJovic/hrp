import http from '@/api/http'

export default {
  list: (params) => http.get('/expenses', { params }).then((r) => r.data),
  create: (data) => http.post('/expenses', data).then((r) => r.data.data),
  update: (id, data) => http.patch(`/expenses/${id}`, data).then((r) => r.data.data),
  destroy: (id) => http.delete(`/expenses/${id}`),
}
