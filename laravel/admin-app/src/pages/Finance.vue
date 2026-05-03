<script setup>
import { ref, onMounted } from 'vue';
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
          <div class="stat-label">本月收入</div>
          <div class="stat-value">¥{{ Number(report.monthly_revenue || 0).toFixed(2) }}</div>
          <div class="stat-hint">当月</div>
        </div>
        <div class="stat-card">
          <div class="stat-label">订单总数</div>
          <div class="stat-value">{{ report.total_orders || 0 }}</div>
          <div class="stat-hint">累计订单</div>
        </div>
        <div class="stat-card">
          <div class="stat-label">付费用户</div>
          <div class="stat-value">{{ report.paid_users || 0 }}</div>
          <div class="stat-hint">当前有效订阅</div>
        </div>
      </div>
      <div v-if="report.subscription_distribution" class="card" style="margin-top:16px">
        <h3>订阅类型分布</h3>
        <div style="margin-top:12px;display:flex;flex-direction:column;gap:8px">
          <div v-for="(count, type) in report.subscription_distribution" :key="type" style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid var(--border)">
            <span>{{ type }}</span>
            <span style="color:var(--text-h);font-weight:600">{{ count }} 人</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
