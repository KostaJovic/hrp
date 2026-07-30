import http from '@/api/http'

export default {
  list: (params) => http.get('/tasks', { params }).then((r) => r.data),
  create: (data) => http.post('/tasks', data).then((r) => r.data.data),
  update: (id, data) => http.patch(`/tasks/${id}`, data).then((r) => r.data.data),
  destroy: (id) => http.delete(`/tasks/${id}`),
  // Returns { data, next } — next is the spawned occurrence for recurring tasks.
  complete: (id) => http.post(`/tasks/${id}/complete`).then((r) => r.data),
}
