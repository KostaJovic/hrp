import http from '@/api/http'

export default {
  list: (params) => http.get('/budgets', { params }).then((r) => r.data.data),
  create: (data) => http.post('/budgets', data).then((r) => r.data.data),
  update: (id, data) => http.patch(`/budgets/${id}`, data).then((r) => r.data.data),
  destroy: (id) => http.delete(`/budgets/${id}`),
}
