<script setup>
import { ref, onMounted } from 'vue';
import api from '../api.js';

const styles = ref([]);
const loading = ref(true);

onMounted(async () => {
  try {
    const { data } = await api.get('/admin/visual-styles');
    styles.value = data.data || [];
  } catch { styles.value = []; }
  loading.value = false;
});
</script>

<template>
  <div>
    <h2 style="margin-bottom:20px">风格预设管理</h2>
    <div v-if="loading" style="color:var(--text-muted)">加载中...</div>
    <table v-else class="data-table">
      <thead><tr><th>ID</th><th>名称</th><th>类别</th><th>提示词关键词</th><th>状态</th><th>排序</th></tr></thead>
      <tbody>
        <tr v-for="s in styles" :key="s.id">
          <td>{{ s.id }}</td>
          <td>{{ s.name }}</td>
          <td><span class="badge info">{{ s.category === 'image' ? '图像' : s.category === 'video' ? '视频' : s.category }}</span></td>
          <td style="font-size:0.85rem;max-width:300px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ s.prompt_keyword }}</td>
          <td><span class="badge" :class="s.status === 'active' ? 'success' : 'error'">{{ s.status === 'active' ? '启用' : '禁用' }}</span></td>
          <td>{{ s.sort_order }}</td>
        </tr>
      </tbody>
    </table>
  </div>
</template>
