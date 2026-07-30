import 'vuetify/styles'
import '@mdi/font/css/materialdesignicons.css'

import { createApp } from 'vue'
import { createVuetify } from 'vuetify'
import App from './App.vue'
import router from './router'

const app = createApp(App)

app.use(router)
app.use(createVuetify())

app.mount('#app')
