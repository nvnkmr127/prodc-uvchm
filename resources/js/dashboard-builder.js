import { createApp } from 'vue'
import { createPinia } from 'pinia'
import DashboardBuilder from './components/DashboardBuilder/DashboardBuilder.vue'

// Import CSS
import '../css/app.css'

// Create app
const app = createApp(DashboardBuilder)

// Install Pinia
app.use(createPinia())

// Mount app
app.mount('#dashboard-builder-app')