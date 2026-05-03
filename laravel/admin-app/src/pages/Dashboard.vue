<script setup>
import { ref, onMounted } from 'vue';
import api from '../api.js';

const stats = ref({ users: 0, works: 0, models: 0, orders: 0 });
const loading = ref(true);
const loadError = ref('');

onMounted(async () => {
  try {
    const { data } = await api.get('/admin/dashboard');
    const d = data.data || data;
    stats.value = {
      users: d.total_users ?? 0,
      works: d.total_works ?? 0,
      models: d.total_models ?? 0,
      orders: d.today_works ?? 0,
    };
  } catch (e) {
    loadError.value = '加载失败，请检查网络后重试';
  } finally {
    loading.value = false;
  }
});
</script>

<template>
  <div class="page-dashboard">
    <h2 style="margin-bottom:20px">仪表盘</h2>
    <div v-if="loadError" class="error-banner">{{ loadError }}</div>
    <div class="stats-grid">
      <div class="stat-card">
        <div class="stat-label">用户总数</div>
        <div class="stat-value">{{ loading ? '—' : stats.users }}</div>
        <div class="stat-hint">全部注册用户</div>
      </div>
      <div class="stat-card">
        <div class="stat-label">作品总数</div>
        <div class="stat-value">{{ loading ? '—' : stats.works }}</div>
        <div class="stat-hint">用户创作作品</div>
      </div>
      <div class="stat-card">
        <div class="stat-label">模型数量</div>
        <div class="stat-value">{{ loading ? '—' : stats.models }}</div>
        <div class="stat-hint">平台已注册AI模型</div>
      </div>
      <div class="stat-card">
        <div class="stat-label">订单数</div>
        <div class="stat-value">{{ loading ? '—' : stats.orders }}</div>
        <div class="stat-hint">今日新增</div>
      </div>
    </div>
    <div class="card">
      <div class="card-header">
        <h3>系统概览</h3>
      </div>
      <p style="color:var(--text-muted)">AIStory 管理后台 — 模型注册管理、用户管理、内容审核、系统配置</p>
    </div>
  </div>
</template>
