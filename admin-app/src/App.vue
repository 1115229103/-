<template>
  <div v-if="!$route.meta.noAuth" class="admin-layout">
    <aside class="sidebar">
      <h1 class="logo">AIStory Admin</h1>
      <nav>
        <router-link v-for="item in menu" :key="item.path" :to="item.path" class="nav-item">
          <span>{{ item.icon }}</span> {{ item.label }}
        </router-link>
      </nav>
      <div class="sidebar-footer">
        <button @click="logout" class="logout-btn">Logout</button>
      </div>
    </aside>
    <main class="main-content"><router-view /></main>
  </div>
  <div v-else class="full-page"><router-view /></div>
</template>

<script setup>
import { useRouter, useRoute } from 'vue-router';
const router = useRouter();
const route = useRoute();

const menu = [
  { path: '/dashboard', label: 'Dashboard', icon: '📊' },
  { path: '/users', label: 'Users', icon: '👤' },
  { path: '/models', label: 'Model Registry', icon: '🤖' },
  { path: '/pipeline', label: 'Pipeline Stages', icon: '⚙️' },
  { path: '/prompts', label: 'Prompt Templates', icon: '📝' },
  { path: '/works', label: 'Works', icon: '🎬' },
  { path: '/orders', label: 'Orders', icon: '💰' },
  { path: '/visual-styles', label: 'Visual Styles', icon: '🎨' },
  { path: '/voice-library', label: 'Voice Library', icon: '🔊' },
  { path: '/watermark', label: 'Watermark', icon: '🖼️' },
  { path: '/banners', label: 'Banners', icon: '📢' },
  { path: '/templates', label: 'Templates', icon: '📋' },
  { path: '/settings', label: 'Settings', icon: '🔧' },
  { path: '/logs', label: 'Operation Logs', icon: '📜' },
];

const logout = () => { localStorage.removeItem('admin_token'); router.push('/login'); };
</script>

<style>
* { margin: 0; padding: 0; box-sizing: border-box; }
body { font-family: system-ui, -apple-system, sans-serif; background: #0f172a; color: #e2e8f0; }
.admin-layout { display: flex; min-height: 100vh; }
.sidebar { width: 240px; background: rgba(255,255,255,0.03); border-right: 1px solid rgba(255,255,255,0.08); padding: 1.5rem 1rem; display: flex; flex-direction: column; position: fixed; top: 0; bottom: 0; left: 0; }
.logo { font-size: 1.25rem; font-weight: 700; background: linear-gradient(to right, #c084fc, #f472b6); -webkit-background-clip: text; color: transparent; margin-bottom: 1.5rem; padding: 0 0.5rem; }
nav { flex: 1; overflow-y: auto; }
.nav-item { display: flex; align-items: center; gap: 0.5rem; padding: 0.6rem 0.75rem; border-radius: 0.5rem; color: rgba(255,255,255,0.6); text-decoration: none; font-size: 0.875rem; margin-bottom: 0.25rem; transition: all 0.15s; }
.nav-item:hover, .nav-item.router-link-active { background: rgba(168,85,247,0.2); color: #fff; }
.sidebar-footer { margin-top: 1rem; padding-top: 1rem; border-top: 1px solid rgba(255,255,255,0.08); }
.logout-btn { width: 100%; padding: 0.5rem; border: 1px solid rgba(239,68,68,0.5); border-radius: 0.5rem; background: none; color: #f87171; cursor: pointer; font-size: 0.875rem; }
.main-content { margin-left: 240px; flex: 1; padding: 2rem; }
.full-page { min-height: 100vh; }
</style>
