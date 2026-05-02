<template>
  <div><h2 class="pg-t">System Settings ({{ items.length }})</h2>
    <div v-for="s in items" :key="s.id" class="row"><span class="key">{{ s.key }}</span><span class="val">{{ s.value }}</span><span class="grp">{{ s.group }}</span></div>
  </div>
</template>
<script setup>
import { ref, onMounted } from 'vue'; import client from '../api/client';
const items = ref([]);
onMounted(async () => { try { const r = await client.get('/system/settings'); items.value = r.data?.data || []; } catch {} });
</script>
<style scoped>.pg-t { font-size:1.75rem; font-weight:700; margin-bottom:1.5rem; } .row { display:flex; gap:1rem; padding:.75rem; background:rgba(255,255,255,.03); border:1px solid rgba(255,255,255,.08); border-radius:.35rem; margin-bottom:.35rem; } .key { font-family:monospace; font-weight:600; min-width:250px; } .val { color:rgba(255,255,255,.6); flex:1; } .grp { color:rgba(255,255,255,.3); font-size:.75rem; }</style>
