<template>
  <div><h2 class="pg-t">Watermark Configuration</h2>
    <div v-if="cfg" class="card">
      <div class="row"><span class="lbl">Enabled:</span><span :class="cfg.enabled ? 'on' : 'off'">{{ cfg.enabled ? 'Yes' : 'No' }}</span></div>
      <div class="row"><span class="lbl">Type:</span><span>{{ cfg.type }}</span></div>
      <div class="row"><span class="lbl">Text:</span><span>{{ cfg.text || '-' }}</span></div>
      <div class="row"><span class="lbl">Position:</span><span>{{ cfg.position }}</span></div>
      <div class="row"><span class="lbl">Opacity:</span><span>{{ cfg.opacity }}</span></div>
    </div>
  </div>
</template>
<script setup>
import { ref, onMounted } from 'vue'; import client from '../api/client';
const cfg = ref(null);
onMounted(async () => { try { const r = await client.get('/watermark-config'); cfg.value = r.data?.data || r.data; } catch {} });
</script>
<style scoped>.pg-t { font-size:1.75rem; font-weight:700; margin-bottom:1.5rem; } .card { padding:1.5rem; background:rgba(255,255,255,.03); border:1px solid rgba(255,255,255,.08); border-radius:.75rem; } .row { display:flex; gap:1rem; padding:.5rem 0; } .lbl { color:rgba(255,255,255,.4); min-width:100px; } .on { color:#4ade80; } .off { color:#f87171; }</style>
