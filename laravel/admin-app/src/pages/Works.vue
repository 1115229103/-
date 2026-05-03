<script setup>
import { ref, onMounted } from 'vue';
import api from '../api.js';
import Pagination from '../components/Pagination.vue';

const works = ref([]);
const loading = ref(true);
const loadError = ref('');
const page = ref(1);
const lastPage = ref(1);
const total = ref(0);

async function load() {
  loading.value = true;
  try {
    const { data } = await api.get('/admin/works', { params: { page: page.value } });
    const p = data.data;
    works.value = p.data || [];
    lastPage.value = p.last_page || 1;
    total.value = p.total || 0;
  } catch { works.value = []; loadError.value = '加载失败，请检查网络后重试'; }
  loading.value = false;
}

function goToPage(p) { page.value = p; load(); }

onMounted(load);
</script>

<template>
  <div>
    <h2 style="margin-bottom:20px">作品管理</h2>
    <div v-if="loading" style="color:var(--text-muted)">加载中...</div>
    <div v-if="loadError" class="error-banner">{{ loadError }}</div>
    <table v-else class="data-table">
      <thead><tr><th>ID</th><th>标题</th><th>风格</th><th>状态</th><th>创建时间</th></tr></thead>
      <tbody>
        <tr v-for="w in works" :key="w.id">
          <td>{{ w.id }}</td>
          <td>{{ w.title }}</td>
          <td>{{ w.style || '—' }}</td>
          <td><span class="badge" :class="w.status === 'completed' ? 'success' : w.status === 'processing' ? 'info' : ''">{{ w.status }}</span></td>
          <td>{{ w.created_at?.substring(0, 10) }}</td>
        </tr>
        <tr v-if="works.length === 0">
          <td colspan="5" style="text-align:center;color:var(--text-muted);padding:32px">暂无作品</td>
        </tr>
      </tbody>
    </table>
    <Pagination :currentPage="page" :lastPage="lastPage" :total="total" :perPage="20" :loading="loading" @page-change="goToPage" />
  </div>
</template>
