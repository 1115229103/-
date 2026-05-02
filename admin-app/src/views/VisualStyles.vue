<template>
  <div><h2 class="pg-t">Visual Styles ({{ items.length }})</h2>
    <div class="grid"><div v-for="s in items" :key="s.id" class="card"><span class="nm">{{ s.name }}</span><span class="kw">{{ s.prompt_keyword }}</span></div></div>
  </div>
</template>
<script setup>
import { ref, onMounted } from 'vue'; import client from '../api/client';
const items = ref([]);
onMounted(async () => { try { const r = await client.get('/visual-styles'); items.value = r.data.data; } catch {} });
</script>
<style scoped>.pg-t { font-size:1.75rem; font-weight:700; margin-bottom:1.5rem; } .grid { display:grid; grid-template-columns:repeat(3,1fr); gap:1rem; } .card { padding:1rem; background:rgba(255,255,255,.03); border:1px solid rgba(255,255,255,.08); border-radius:.5rem; } .nm { display:block; font-weight:600; margin-bottom:.25rem; } .kw { color:rgba(255,255,255,.4); font-size:.8rem; }</style>
