<template>
  <div>
    <h2 class="pg-t">Users ({{ items.length }})</h2>
    <table class="tbl"><thead><tr><th>ID</th><th>Name</th><th>Email</th><th>Role</th><th>Registered</th></tr></thead><tbody><tr v-for="u in items" :key="u.id"><td>{{ u.id }}</td><td>{{ u.name }}</td><td>{{ u.email }}</td><td><span :class="['bdg', u.role==='admin'?'bdg-g':'bdg-bl']">{{ u.role }}</span></td><td>{{ new Date(u.created_at).toLocaleDateString() }}</td></tr></tbody></table>
  </div>
</template>
<script setup>
import { ref, onMounted } from 'vue'; import client from '../api/client';
const items = ref([]);
onMounted(async () => { try { const r = await client.get('/users'); items.value = r.data.data; } catch {} });
</script>
<style scoped>.pg-t { font-size: 1.75rem; font-weight: 700; margin-bottom: 1.5rem; } .tbl { width: 100%; border-collapse: collapse; font-size: 0.875rem; } .tbl th { text-align: left; padding: 0.75rem; border-bottom: 1px solid rgba(255,255,255,0.1); color: rgba(255,255,255,0.4); } .tbl td { padding: 0.75rem; border-bottom: 1px solid rgba(255,255,255,0.05); } .bdg { padding: 0.15rem 0.5rem; border-radius: 9999px; font-size: 0.7rem; background: rgba(255,255,255,0.1); color: rgba(255,255,255,0.6); } .bdg-g { background: rgba(34,197,94,0.15); color: #4ade80; } .bdg-bl { background: rgba(59,130,246,0.15); color: #60a5fa; }</style>
