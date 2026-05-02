import { createRouter, createWebHistory } from 'vue-router';

const routes = [
  { path: '/', redirect: '/dashboard' },
  { path: '/login', name: 'Login', component: () => import('../views/Login.vue'), meta: { noAuth: true } },
  { path: '/dashboard', name: 'Dashboard', component: () => import('../views/Dashboard.vue') },
  { path: '/users', name: 'Users', component: () => import('../views/Users.vue') },
  { path: '/models', name: 'Models', component: () => import('../views/Models.vue') },
  { path: '/pipeline', name: 'Pipeline', component: () => import('../views/Pipeline.vue') },
  { path: '/prompts', name: 'Prompts', component: () => import('../views/Prompts.vue') },
  { path: '/works', name: 'Works', component: () => import('../views/Works.vue') },
  { path: '/orders', name: 'Orders', component: () => import('../views/Orders.vue') },
  { path: '/visual-styles', name: 'Styles', component: () => import('../views/VisualStyles.vue') },
  { path: '/voice-library', name: 'Voices', component: () => import('../views/VoiceLibrary.vue') },
  { path: '/watermark', name: 'Watermark', component: () => import('../views/Watermark.vue') },
  { path: '/settings', name: 'Settings', component: () => import('../views/Settings.vue') },
  { path: '/banners', name: 'Banners', component: () => import('../views/Banners.vue') },
  { path: '/templates', name: 'Templates', component: () => import('../views/Templates.vue') },
  { path: '/logs', name: 'Logs', component: () => import('../views/Logs.vue') },
];

const router = createRouter({ history: createWebHistory(), routes });

router.beforeEach((to, from, next) => {
  const token = localStorage.getItem('admin_token');
  if (!to.meta.noAuth && !token) next('/login');
  else next();
});

export default router;
