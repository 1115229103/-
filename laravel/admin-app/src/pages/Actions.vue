<script setup>
import { ref, onMounted } from 'vue';
import api from '../api.js';

const actions = ref([]);
const loading = ref(true);
const loadError = ref('');

onMounted(async () => {
  try {
    const { data } = await api.get('/admin/action-templates');
    actions.value = data.data || [];
  } catch { actions.value = []; loadError.value = '加载失败，请检查网络后重试'; }
  loading.value = false;
});
</script>

<template>
  <div>
    <h2 style="margin-bottom:20px">动作模板管理</h2>
    <div v-if="loading" style="color:var(--text-muted)">加载中...</div>
    <div v-if="loadError" class="error-banner">{{ loadError }}</div>
    <table v-else class="data-table">
      <thead><tr><th>ID</th><th>名称</th><th>类别</th><th>提示词(中文)</th><th>标签</th><th>状态</th><th>排序</th></tr></thead>
      <tbody>
        <tr v-for="a in actions" :key="a.id">
          <td>{{ a.id }}</td>
          <td>{{ a.name }}</td>
          <td><span class="badge info">{{ a.category || '—' }}</span></td>
          <td style="font-size:0.85rem;max-width:300px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ a.prompt_cn || '—' }}</td>
          <td style="font-size:0.85rem">{{ a.tags ? (typeof a.tags === 'string' ? a.tags : JSON.stringify(a.tags)) : '—' }}</td>
          <td><span class="badge" :class="a.status === 'active' ? 'success' : 'error'">{{ a.status === 'active' ? '启用' : '禁用' }}</span></td>
          <td>{{ a.sort_order }}</td>
        </tr>
        <tr v-if="actions.length === 0">
          <td colspan="7" style="text-align:center;color:var(--text-muted);padding:32px">暂无动作模板</td>
        </tr>
      </tbody>
    </table>
  </div>
</template>
