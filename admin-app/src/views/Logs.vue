<template>
  <div><h2 class="pg-t">Operation Logs ({{ items.length }})</h2>
    <table class="tbl"><thead><tr><th>ID</th><th>User</th><th>Action</th><th>Target</th><th>Time</th></tr></thead><tbody><tr v-for="l in items" :key="l.id"><td>{{ l.id }}</td><td>{{ l.user?.name || '#'+l.user_id }}</td><td>{{ l.action }}</td><td>{{ l.target_type }}#{{ l.target_id }}</td><td>{{ new Date(l.created_at).toLocaleString() }}</td></tr></tbody></table></div>
</template>
<script setup>
import { ref, onMounted } from 'vue'; import client from '../api/client';
const items = ref([]);
onMounted(async () => { try { const r = await client.get('/system/operation-logs'); items.value = r.data?.data || []; } catch {} });
</script>
<style scoped>.pg-t { font-size:1.75rem; font-weight:700; margin-bottom:1.5rem; } .tbl { width:100%; border-collapse:collapse; font-size:.875rem; } .tbl th { text-align:left; padding:.75rem; border-bottom:1px solid rgba(255,255,255,.1); color:rgba(255,255,255,.4); } .tbl td { padding:.75rem; border-bottom:1px solid rgba(255,255,255,.05); }</style>
