<script setup>
import { ref, onMounted } from 'vue';
import api from '../api.js';

const banners = ref([]);
const loading = ref(true);

onMounted(async () => {
  try {
    const { data } = await api.get('/admin/banners');
    banners.value = data.data || [];
  } catch { banners.value = []; }
  loading.value = false;
});
</script>

<template>
  <div>
    <h2 style="margin-bottom:20px">Banner管理</h2>
    <div v-if="loading" style="color:var(--text-muted)">加载中...</div>
    <table v-else class="data-table">
      <thead><tr><th>ID</th><th>标题</th><th>图片</th><th>链接</th><th>排序</th><th>状态</th></tr></thead>
      <tbody>
        <tr v-for="b in banners" :key="b.id">
          <td>{{ b.id }}</td>
          <td>{{ b.title }}</td>
          <td><span v-if="b.image_url" style="color:var(--primary);font-size:0.85rem">有图片</span><span v-else style="color:var(--text-muted)">—</span></td>
          <td style="font-size:0.85rem;max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ b.link_url || '—' }}</td>
          <td>{{ b.sort_order }}</td>
          <td><span class="badge" :class="b.status === 'active' ? 'success' : 'error'">{{ b.status === 'active' ? '启用' : '禁用' }}</span></td>
        </tr>
      </tbody>
    </table>
  </div>
</template>
