<script setup>
import { ref, onMounted } from 'vue';
import api from '../api.js';

const words = ref([]);
const loading = ref(true);
const loadError = ref('');

onMounted(async () => {
  try {
    const { data } = await api.get('/admin/sensitive-words');
    words.value = data.data || [];
  } catch { words.value = []; loadError.value = '加载失败，请检查网络后重试'; }
  loading.value = false;
});
</script>

<template>
  <div>
    <h2 style="margin-bottom:20px">敏感词管理</h2>
    <div v-if="loading" style="color:var(--text-muted)">加载中...</div>
    <div v-if="loadError" class="error-banner">{{ loadError }}</div>
    <table v-else class="data-table">
      <thead><tr><th>ID</th><th>敏感词</th><th>类别</th><th>等级</th><th>状态</th><th>创建时间</th></tr></thead>
      <tbody>
        <tr v-if="words.length === 0">
          <td colspan="6" style="text-align:center;color:var(--text-muted);padding:32px">暂无敏感词</td>
        </tr>
        <tr v-for="w in words" :key="w.id">
          <td>{{ w.id }}</td>
          <td style="color:var(--error)">{{ w.word }}</td>
          <td>{{ w.category || '—' }}</td>
          <td>{{ w.level || '—' }}</td>
          <td><span class="badge" :class="w.status === 'active' ? 'success' : 'error'">{{ w.status === 'active' ? '启用' : '禁用' }}</span></td>
          <td>{{ w.created_at?.substring(0, 10) }}</td>
        </tr>
      </tbody>
    </table>
  </div>
</template>
