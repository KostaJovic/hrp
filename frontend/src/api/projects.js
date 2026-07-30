import http from '@/api/http'

export default {
  list: (params) => http.get('/projects', { params }).then((r) => r.data.data),
  get: (id) => http.get(`/projects/${id}`).then((r) => r.data.data),
  create: (data) => http.post('/projects', data).then((r) => r.data.data),
  update: (id, data) => http.patch(`/projects/${id}`, data).then((r) => r.data.data),
  destroy: (id) => http.delete(`/projects/${id}`),
}
