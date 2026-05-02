<template>
  <div>
    <h2 class="page-title">Dashboard</h2>
    <div class="stats-grid">
      <div class="stat-card" v-for="s in stats" :key="s.label">
        <div class="stat-value">{{ s.value }}</div>
        <div class="stat-label">{{ s.label }}</div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import client from '../api/client';

const stats = ref([
  { label: 'Total Users', value: '-' },
  { label: 'Total Works', value: '-' },
  { label: 'Total Orders', value: '-' },
  { label: 'Revenue', value: '-' },
]);

onMounted(async () => {
  try { const r = await client.get('/dashboard'); if (r.data) { stats.value[0].value = r.data.users ?? '-'; stats.value[1].value = r.data.works ?? '-'; stats.value[2].value = r.data.orders ?? '-'; stats.value[3].value = '¥' + (r.data.revenue ?? '0'); } } catch {}
});
</script>

<style scoped>
.page-title { font-size: 1.75rem; font-weight: 700; margin-bottom: 1.5rem; }
.stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 1.5rem; }
.stat-card { padding: 1.5rem; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); border-radius: 0.75rem; }
.stat-value { font-size: 2rem; font-weight: 700; margin-bottom: 0.25rem; }
.stat-label { color: rgba(255,255,255,0.4); font-size: 0.875rem; }
</style>
