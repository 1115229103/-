<template>
  <div><h2 class="pg-t">Pipeline Stages</h2>
    <div v-for="s in items" :key="s.id" class="row"><span class="lbl">{{ s.name }}</span><span class="code">{{ s.stage }}</span><span class="cat">{{ s.category }}</span><span :class="['tog', s.is_enabled ? 'on' : 'off']" @click="toggle(s)">{{ s.is_enabled ? 'Enabled' : 'Disabled' }}</span></div>
  </div>
</template>
<script setup>
import { ref, onMounted } from 'vue'; import client from '../api/client';
const items = ref([]);
onMounted(async () => { try { const r = await client.get('/pipeline-stages'); items.value = r.data.data; } catch {} });
const toggle = async (s) => { try { await client.put(`/pipeline-stages/${s.stage}`, { is_enabled: !s.is_enabled }); s.is_enabled = !s.is_enabled; } catch {} };
</script>
<style scoped>.pg-t { font-size:1.75rem; font-weight:700; margin-bottom:1.5rem; } .row { display:flex; align-items:center; gap:1rem; padding:1rem; background:rgba(255,255,255,.03); border:1px solid rgba(255,255,255,.08); border-radius:.5rem; margin-bottom:.5rem; } .lbl { font-weight:600; min-width:120px; } .code { color:rgba(255,255,255,.4); font-family:monospace; font-size:.8rem; } .cat { padding:.15rem .5rem; border-radius:9999px; font-size:.7rem; background:rgba(255,255,255,.1); } .tog { margin-left:auto; padding:.25rem .75rem; border-radius:9999px; font-size:.75rem; cursor:pointer; } .on { background:rgba(34,197,94,.15); color:#4ade80; } .off { background:rgba(234,179,8,.15); color:#facc15; }</style>
