<template>
  <div><h2 class="pg-t">Works ({{ items.length }})</h2>
    <table class="tbl"><thead><tr><th>ID</th><th>Title</th><th>User</th><th>Status</th><th>Pipeline</th><th>Created</th></tr></thead><tbody><tr v-for="w in items" :key="w.id"><td>{{ w.id }}</td><td>{{ w.title || 'Untitled' }}</td><td>{{ w.user?.name || '#'+w.user_id }}</td><td><span :class="['bdg', w.status==='completed'?'bdg-g':w.status==='failed'?'bdg-r':'bdg-y']">{{ w.status }}</span></td><td>{{ w.pipeline_state || '-' }}</td><td>{{ new Date(w.created_at).toLocaleDateString() }}</td></tr></tbody></table></div>
</template>
<script setup>
import { ref, onMounted } from 'vue'; import client from '../api/client';
const items = ref([]);
onMounted(async () => { try { const r = await client.get('/works'); items.value = r.data.data; } catch {} });
</script>
<style scoped>.pg-t { font-size:1.75rem; font-weight:700; margin-bottom:1.5rem; } .tbl { width:100%; border-collapse:collapse; font-size:.875rem; } .tbl th { text-align:left; padding:.75rem; border-bottom:1px solid rgba(255,255,255,.1); color:rgba(255,255,255,.4); } .tbl td { padding:.75rem; border-bottom:1px solid rgba(255,255,255,.05); } .bdg { padding:.15rem .5rem; border-radius:9999px; font-size:.7rem; background:rgba(255,255,255,.1); } .bdg-g { background:rgba(34,197,94,.15); color:#4ade80; } .bdg-r { background:rgba(239,68,68,.15); color:#f87171; } .bdg-y { background:rgba(234,179,8,.15); color:#facc15; }</style>
