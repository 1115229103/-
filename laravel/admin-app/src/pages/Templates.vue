<script setup>
import { ref, onMounted } from 'vue';
import api from '../api.js';

const templates = ref([]);
const loading = ref(true);

onMounted(async () => {
  try {
    const { data } = await api.get('/admin/templates');
    templates.value = data.data || [];
  } catch { templates.value = []; }
  loading.value = false;
});
</script>

<template>
  <div>
    <h2 style="margin-bottom:20px">模板管理</h2>
    <div v-if="loading" style="color:var(--text-muted)">加载中...</div>
    <table v-else class="data-table">
      <thead><tr><th>ID</th><th>名称</th><th>类别</th><th>描述</th><th>状态</th><th>排序</th></tr></thead>
      <tbody>
        <tr v-for="t in templates" :key="t.id">
          <td>{{ t.id }}</td>
          <td>{{ t.name }}</td>
          <td>{{ t.category || '—' }}</td>
          <td style="font-size:0.85rem;max-width:300px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ t.description || '—' }}</td>
          <td><span class="badge" :class="t.status === 'active' ? 'success' : 'error'">{{ t.status === 'active' ? '启用' : '禁用' }}</span></td>
          <td>{{ t.sort_order }}</td>
        </tr>
      </tbody>
    </table>
  </div>
</template>
