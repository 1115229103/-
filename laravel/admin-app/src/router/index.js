import { createRouter, createWebHistory } from 'vue-router';
import Dashboard from '../pages/Dashboard.vue';
import Models from '../pages/Models.vue';
import Prompts from '../pages/Prompts.vue';
import Pipeline from '../pages/Pipeline.vue';
import Styles from '../pages/Styles.vue';
import Voices from '../pages/Voices.vue';
import Actions from '../pages/Actions.vue';
import Works from '../pages/Works.vue';
import Review from '../pages/Review.vue';
import SensitiveWords from '../pages/SensitiveWords.vue';
import Users from '../pages/Users.vue';
import Plans from '../pages/Plans.vue';
import Banners from '../pages/Banners.vue';
import Templates from '../pages/Templates.vue';
import Assets from '../pages/Assets.vue';
import Orders from '../pages/Orders.vue';
import Finance from '../pages/Finance.vue';
import Settings from '../pages/Settings.vue';
import Roles from '../pages/Roles.vue';
import Logs from '../pages/Logs.vue';

const routes = [
  { path: '/', name: 'Dashboard', component: Dashboard },
  { path: '/models', name: 'Models', component: Models },
  { path: '/prompts', name: 'Prompts', component: Prompts },
  { path: '/pipeline', name: 'Pipeline', component: Pipeline },
  { path: '/styles', name: 'Styles', component: Styles },
  { path: '/voices', name: 'Voices', component: Voices },
  { path: '/actions', name: 'Actions', component: Actions },
  { path: '/works', name: 'Works', component: Works },
  { path: '/review', name: 'Review', component: Review },
  { path: '/sensitive-words', name: 'SensitiveWords', component: SensitiveWords },
  { path: '/users', name: 'Users', component: Users },
  { path: '/plans', name: 'Plans', component: Plans },
  { path: '/banners', name: 'Banners', component: Banners },
  { path: '/templates', name: 'Templates', component: Templates },
  { path: '/assets', name: 'Assets', component: Assets },
  { path: '/orders', name: 'Orders', component: Orders },
  { path: '/finance', name: 'Finance', component: Finance },
  { path: '/settings', name: 'Settings', component: Settings },
  { path: '/roles', name: 'Roles', component: Roles },
  { path: '/logs', name: 'Logs', component: Logs },
];

const router = createRouter({
  history: createWebHistory(),
  routes,
});

export default router;
