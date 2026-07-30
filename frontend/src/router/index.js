import { createRouter, createWebHistory } from 'vue-router'

import { useAuthStore } from '@/stores/auth'

const placeholder = (title) => ({
  component: () => import('@/views/PlaceholderView.vue'),
  props: { title },
})

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: '/login',
      name: 'login',
      meta: { public: true },
      component: () => import('@/views/auth/LoginView.vue'),
    },
    {
      path: '/',
      component: () => import('@/layouts/AppLayout.vue'),
      children: [
        { path: '', redirect: '/items' },
        { path: 'items', name: 'items', ...placeholder('Gegenstände') },
        {
          path: 'locations',
          name: 'locations',
          component: () => import('@/views/locations/LocationsView.vue'),
        },
        { path: 'tasks', name: 'tasks', ...placeholder('Aufgaben') },
        { path: 'maintenance', name: 'maintenance', ...placeholder('Wartung') },
        { path: 'expenses', name: 'expenses', ...placeholder('Ausgaben') },
        { path: 'projects', name: 'projects', ...placeholder('Projekte') },
      ],
    },
  ],
})

router.beforeEach(async (to) => {
  if (to.meta.public) {
    return true
  }

  const auth = useAuthStore()

  if (!auth.checked) {
    await auth.fetchUser()
  }

  if (!auth.user) {
    return { name: 'login', query: { redirect: to.fullPath } }
  }

  return true
})

export default router
