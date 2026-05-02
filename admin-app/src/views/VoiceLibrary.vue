<template>
  <div><h2 class="pg-t">Voice Library ({{ items.length }})</h2>
    <table class="tbl"><thead><tr><th>Name</th><th>Provider</th><th>Gender</th><th>Language</th><th>Style</th></tr></thead><tbody><tr v-for="v in items" :key="v.id"><td>{{ v.name }}</td><td>{{ v.provider }}</td><td>{{ v.gender }}</td><td>{{ v.language }}</td><td>{{ v.style }}</td></tr></tbody></table></div>
</template>
<script setup>
import { ref, onMounted } from 'vue'; import client from '../api/client';
const items = ref([]);
onMounted(async () => { try { const r = await client.get('/voice-library'); items.value = r.data.data; } catch {} });
</script>
<style scoped>.pg-t { font-size:1.75rem; font-weight:700; margin-bottom:1.5rem; } .tbl { width:100%; border-collapse:collapse; font-size:.875rem; } .tbl th { text-align:left; padding:.75rem; border-bottom:1px solid rgba(255,255,255,.1); color:rgba(255,255,255,.4); } .tbl td { padding:.75rem; border-bottom:1px solid rgba(255,255,255,.05); }</style>
