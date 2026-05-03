<script setup>
import { ref, computed, onMounted } from 'vue';
import api from '../api.js';

const report = ref({});
const loading = ref(true);
const loadError = ref('');

onMounted(async () => {
  try {
    const { data } = await api.get('/admin/finance/report');
    report.value = data.data || data || {};
  } catch { report.value = {}; loadError.value = '加载失败，请检查网络后重试'; }
  loading.value = false;
});

const monthlyRevenue = computed(() => {
  const days = report.value.revenue_by_day || [];
  return days.reduce((sum, d) => sum + Number(d.total || 0), 0);
});
</script>

<template>
  <div>
    <h2 style="margin-bottom:20px">财务报表</h2>
    <div v-if="loading" style="color:var(--text-muted)">加载中...</div>
    <div v-if="loadError" class="error-banner">{{ loadError }}</div>
    <div v-else>
      <div class="stats-grid" style="margin-bottom:20px">
        <div class="stat-card">
          <div class="stat-label">总收入</div>
          <div class="stat-value">¥{{ Number(report.total_revenue || 0).toFixed(2) }}</div>
          <div class="stat-hint">累计收入</div>
        </div>
        <div class="stat-card">
          <div class="stat-label">近30天收入</div>
          <div class="stat-value">¥{{ monthlyRevenue.toFixed(2) }}</div>
          <div class="stat-hint">按支付日期统计</div>
        </div>
        <div class="stat-card">
          <div class="stat-label">订单总数</div>
          <div class="stat-value">{{ report.total_orders || 0 }}</div>
          <div class="stat-hint">累计已完成支付</div>
        </div>
        <div class="stat-card">
          <div class="stat-label">待处理订单</div>
          <div class="stat-value">{{ report.pending_orders || 0 }}</div>
          <div class="stat-hint">待支付订单</div>
        </div>
      </div>
      <div v-if="(report.revenue_by_day || []).length" class="card">
        <h3>每日收入明细（近30天）</h3>
        <table class="data-table" style="margin-top:12px">
          <thead><tr><th>日期</th><th>收入</th></tr></thead>
          <tbody>
            <tr v-for="d in report.revenue_by_day" :key="d.date">
              <td>{{ d.date }}</td>
              <td>¥{{ Number(d.total || 0).toFixed(2) }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>
