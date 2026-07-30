import http from '@/api/http'

export const plans = {
  list: (params) => http.get('/maintenance-plans', { params }).then((r) => r.data.data),
  create: (data) => http.post('/maintenance-plans', data).then((r) => r.data.data),
  update: (id, data) => http.patch(`/maintenance-plans/${id}`, data).then((r) => r.data.data),
  destroy: (id) => http.delete(`/maintenance-plans/${id}`),
}

export const logs = {
  list: (params) => http.get('/maintenance-logs', { params }).then((r) => r.data),
  create: (data) => http.post('/maintenance-logs', data).then((r) => r.data.data),
  update: (id, data) => http.patch(`/maintenance-logs/${id}`, data).then((r) => r.data.data),
  destroy: (id) => http.delete(`/maintenance-logs/${id}`),
}
