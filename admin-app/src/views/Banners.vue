<template>
  <div><h2 class="pg-t">Banners ({{ items.length }})</h2><p v-if="!items.length" class="mt">No banners yet.</p>
    <div v-for="b in items" :key="b.id" class="card"><span class="ttl">{{ b.title || 'Untitled' }}</span><span class="url">{{ b.image_url || '-' }}</span></div>
  </div>
</template>
<script setup>
import { ref, onMounted } from 'vue'; import client from '../api/client';
const items = ref([]);
onMounted(async () => { try { const r = await client.get('/banners'); items.value = r.data?.data || []; } catch {} });
</script>
<style scoped>.pg-t { font-size:1.75rem; font-weight:700; margin-bottom:1.5rem; } .mt { color:rgba(255,255,255,.4); } .card { display:flex; gap:1rem; padding:.75rem; background:rgba(255,255,255,.03); border:1px solid rgba(255,255,255,.08); border-radius:.35rem; margin-bottom:.35rem; } .ttl { font-weight:600; } .url { color:rgba(255,255,255,.4); font-size:.8rem; }</style>
