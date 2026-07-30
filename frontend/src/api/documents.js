import http from '@/api/http'

export default {
  listFor: (type, id) =>
    http
      .get('/documents', { params: { documentable_type: type, documentable_id: id } })
      .then((r) => r.data.data),

  upload: (type, id, { file, kind, title }) => {
    const form = new FormData()
    form.append('documentable_type', type)
    form.append('documentable_id', id)
    form.append('kind', kind)
    form.append('file', file)
    if (title) form.append('title', title)
    return http.post('/documents', form).then((r) => r.data.data)
  },

  destroy: (id) => http.delete(`/documents/${id}`),

  // Relative URL through the Vite proxy: same-origin, session cookie included.
  downloadUrl: (doc) => `/api/v1/documents/${doc.id}/download`,
}
