<script setup>
import { ref, onMounted } from 'vue';
import api from '../api.js';
import Pagination from '../components/Pagination.vue';

const logs = ref([]);
const loading = ref(true);
const loadError = ref('');
const page = ref(1);
const lastPage = ref(1);
const total = ref(0);

async function load() {
  loading.value = true;
  try {
    const { data } = await api.get('/admin/system/operation-logs', { params: { page: page.value } });
    const p = data.data;
    logs.value = p.data || [];
    lastPage.value = p.last_page || 1;
    total.value = p.total || 0;
  } catch { logs.value = []; loadError.value = '加载失败，请检查网络后重试'; }
  loading.value = false;
}

function goToPage(p) { page.value = p; load(); }

onMounted(load);
</script>

<template>
  <div>
    <h2 style="margin-bottom:20px">操作日志</h2>
    <div v-if="loading" style="color:var(--text-muted)">加载中...</div>
    <div v-if="loadError" class="error-banner">{{ loadError }}</div>
    <table v-else class="data-table">
      <thead><tr><th>ID</th><th>操作人</th><th>操作类型</th><th>目标</th><th>详情</th><th>时间</th></tr></thead>
      <tbody>
        <tr v-for="l in logs" :key="l.id">
          <td>{{ l.id }}</td>
          <td>{{ l.user?.name || l.user_id || '—' }}</td>
          <td><span class="badge info">{{ l.action }}</span></td>
          <td>{{ l.target_type }}{{ l.target_id ? '#' + l.target_id : '' }}</td>
          <td style="font-size:0.85rem;max-width:250px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ l.module }}:{{ l.action }}{{ l.target_id ? ' #' + l.target_id : '' }}</td>
          <td style="font-size:0.85rem">{{ l.created_at }}</td>
        </tr>
        <tr v-if="logs.length === 0">
          <td colspan="6" style="text-align:center;color:var(--text-muted);padding:32px">暂无操作日志</td>
        </tr>
      </tbody>
    </table>
    <Pagination :currentPage="page" :lastPage="lastPage" :total="total" :perPage="20" :loading="loading" @page-change="goToPage" />
  </div>
</template>
