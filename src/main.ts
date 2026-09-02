import { createApp } from 'vue'
import App from './App.vue'
import router from './router'
import './styles/main.css'
import './styles/workflow.css'
import './styles/features.css'
import './styles/steps.css'
import './styles/interactions.css'
import './styles/settings.css'
import './styles/form-typography.css'
import './styles/feature-hub.css'
import './styles/crud.css'
import './styles/crud-validation.css'
import './styles/feature-typography.css'
import './styles/members.css'
import './styles/feedback.css'
import './styles/storefront.css'
import './styles/storefront-typography.css'
import './styles/pagination.css'
import './styles/module-navigation.css'
import './styles/topbar-interactions.css'
import './styles/help-additions.css'
import './styles/control-focus.css'
import './styles/error-pages.css'

document.documentElement.dataset.inputModality = 'pointer'
document.addEventListener('pointerdown', () => { document.documentElement.dataset.inputModality = 'pointer' }, true)
document.addEventListener('keydown', (event) => {
  if (event.key === 'Tab') document.documentElement.dataset.inputModality = 'keyboard'
}, true)

createApp(App).use(router).mount('#app')
