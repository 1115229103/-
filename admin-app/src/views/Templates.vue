<template>
  <div><h2 class="pg-t">Content Templates ({{ items.length }})</h2><p v-if="!items.length" class="mt">No templates yet.</p>
    <div v-for="t in items" :key="t.id" class="card"><span class="nm">{{ t.name || 'Untitled' }}</span><span class="cat">{{ t.category || '-' }}</span></div>
  </div>
</template>
<script setup>
import { ref, onMounted } from 'vue'; import client from '../api/client';
const items = ref([]);
onMounted(async () => { try { const r = await client.get('/templates'); items.value = r.data?.data || []; } catch {} });
</script>
<style scoped>.pg-t { font-size:1.75rem; font-weight:700; margin-bottom:1.5rem; } .mt { color:rgba(255,255,255,.4); } .card { display:flex; gap:1rem; padding:.75rem; background:rgba(255,255,255,.03); border:1px solid rgba(255,255,255,.08); border-radius:.35rem; margin-bottom:.35rem; } .nm { font-weight:600; } .cat { color:rgba(255,255,255,.4); }</style>
