<script setup>
import { ref, onMounted } from 'vue';
import api from '../api.js';

const orders = ref([]);
const loading = ref(true);
const loadError = ref('');

onMounted(async () => {
  try {
    const { data } = await api.get('/admin/orders');
    orders.value = data.data?.data || data.data || [];
  } catch { orders.value = []; loadError.value = '加载失败，请检查网络后重试'; }
  loading.value = false;
});
</script>

<template>
  <div>
    <h2 style="margin-bottom:20px">订单管理</h2>
    <div v-if="loading" style="color:var(--text-muted)">加载中...</div>
    <div v-if="loadError" class="error-banner">{{ loadError }}</div>
    <table v-else class="data-table">
      <thead>
        <tr><th>ID</th><th>用户</th><th>套餐</th><th>金额</th><th>状态</th><th>时间</th></tr>
      </thead>
      <tbody>
        <tr v-for="o in orders" :key="o.id">
          <td>{{ o.id }}</td>
          <td>
            <div>{{ o.user?.name }}</div>
            <div style="font-size:0.8rem;color:var(--text-muted)">{{ o.user?.email }}</div>
          </td>
          <td>{{ o.plan?.name || o.plan_id }}</td>
          <td>¥{{ Number(o.amount_cny || 0).toFixed(2) }}</td>
          <td>
            <span class="badge" :class="o.status === 'paid' ? 'success' : o.status === 'pending' ? 'warning' : o.status === 'refunded' ? 'error' : ''"
              :style="!['paid','pending','refunded'].includes(o.status) ? 'background:rgba(156,163,175,0.1);color:#9ca3af' : ''">
              {{ { paid: '已支付', pending: '待支付', refunded: '已退款', cancelled: '已取消' }[o.status] || o.status }}
            </span>
          </td>
          <td style="font-size:0.85rem">{{ o.created_at?.substring(0, 10) }}</td>
        </tr>
        <tr v-if="orders.length === 0">
          <td colspan="6" style="text-align:center;color:var(--text-muted);padding:32px">暂无订单</td>
        </tr>
      </tbody>
    </table>
  </div>
</template>
