<script setup>
import { useRoute, useRouter } from 'vue-router';

const route = useRoute();
const router = useRouter();
const adminUser = JSON.parse(localStorage.getItem('admin_user') || 'null');

function logout() {
  localStorage.removeItem('admin_token');
  localStorage.removeItem('admin_user');
  router.push('/login');
}

const navSections = [
  {
    label: '概览',
    items: [
      { to: '/', label: '仪表盘' },
    ],
  },
  {
    label: 'AI 管理',
    items: [
      { to: '/models', label: '模型注册' },
      { to: '/prompts', label: '提示词模板' },
      { to: '/pipeline', label: '环节配置' },
      { to: '/styles', label: '风格预设' },
      { to: '/voices', label: '音色库' },
      { to: '/actions', label: '动作模板' },
    ],
  },
  {
    label: '内容',
    items: [
      { to: '/works', label: '作品管理' },
      { to: '/review', label: '作品审核' },
      { to: '/sensitive-words', label: '敏感词管理' },
    ],
  },
  {
    label: '用户',
    items: [
      { to: '/users', label: '用户管理' },
      { to: '/plans', label: '套餐管理' },
    ],
  },
  {
    label: '运营',
    items: [
      { to: '/banners', label: 'Banner管理' },
      { to: '/templates', label: '模板管理' },
      { to: '/assets', label: '素材管理' },
    ],
  },
  {
    label: '财务',
    items: [
      { to: '/orders', label: '订单管理' },
      { to: '/finance', label: '财务报表' },
    ],
  },
  {
    label: '系统',
    items: [
      { to: '/settings', label: '系统设置' },
      { to: '/roles', label: '权限管理' },
      { to: '/logs', label: '操作日志' },
    ],
  },
];
</script>

<template>
  <div class="admin-layout">
    <aside class="sidebar">
      <div class="sidebar-logo">
        <h1>AIStory 管理</h1>
      </div>
      <nav class="sidebar-nav">
        <template v-for="section in navSections" :key="section.label">
          <div class="nav-section">{{ section.label }}</div>
          <router-link
            v-for="item in section.items"
            :key="item.to"
            :to="item.to"
            :class="{ active: route.path === item.to }"
          >
            {{ item.label }}
          </router-link>
        </template>
      </nav>
      <div class="sidebar-footer">
        <span v-if="adminUser" style="display:block;margin-bottom:4px;font-size:0.8rem">{{ adminUser.name }}</span>
        <span v-if="adminUser" class="logout-link" @click="logout" style="cursor:pointer;font-size:0.75rem;color:var(--accent)">退出登录</span>
        <span style="display:block;margin-top:8px;font-size:0.7rem;color:var(--text-muted)">v1.0.0</span>
      </div>
    </aside>
    <div class="main-content">
      <div class="top-bar">
        <span style="color:var(--text-muted);font-size:0.85rem">AIStory 管理后台</span>
      </div>
      <div class="page-body">
        <router-view />
      </div>
    </div>
  </div>
</template>
