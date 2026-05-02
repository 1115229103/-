<script setup>
import { ref, onMounted } from 'vue';
import api from '../api.js';

const plans = ref([]);
const loading = ref(true);

onMounted(async () => {
  try {
    const { data } = await api.get('/admin/plans');
    plans.value = data.data || [];
  } catch { plans.value = []; }
  loading.value = false;
});
</script>

<template>
  <div>
    <h2 style="margin-bottom:20px">套餐管理</h2>
    <div v-if="loading" style="color:var(--text-muted)">加载中...</div>
    <table v-else class="data-table">
      <thead><tr><th>ID</th><th>名称</th><th>标识</th><th>级别</th><th>月费(¥)</th><th>年费(¥)</th><th>权益</th><th>状态</th><th>排序</th></tr></thead>
      <tbody>
        <tr v-for="p in plans" :key="p.id">
          <td>{{ p.id }}</td>
          <td>{{ p.name }}</td>
          <td>{{ p.slug }}</td>
          <td><span class="badge" :class="p.tier === 'enterprise' ? 'info' : p.tier === 'professional' ? 'success' : ''">{{ p.tier }}</span></td>
          <td>¥{{ Number(p.price_monthly_cny || 0).toFixed(0) }}</td>
          <td>¥{{ Number(p.price_yearly_cny || 0).toFixed(0) }}</td>
          <td style="font-size:0.8rem;max-width:250px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ p.features ? (typeof p.features === 'string' ? p.features : p.features.join('、')) : '—' }}</td>
          <td><span class="badge" :class="p.is_active ? 'success' : 'error'">{{ p.is_active ? '启用' : '禁用' }}</span></td>
          <td>{{ p.sort_order }}</td>
        </tr>
      </tbody>
    </table>
  </div>
</template>
