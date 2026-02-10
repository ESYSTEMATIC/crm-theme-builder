import { createRouter, createWebHistory } from 'vue-router'
import ThemePicker from '../components/ThemePicker.vue'
import MicrositeEditor from '../components/MicrositeEditor.vue'

const routes = [
  {
    path: '/',
    name: 'home',
    component: ThemePicker,
  },
  {
    path: '/sites/create',
    name: 'create-site',
    component: ThemePicker,
  },
  {
    path: '/sites/:id',
    name: 'editor',
    component: MicrositeEditor,
    props: true,
  },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
})

export default router
