<script setup>
import { ref, onMounted } from 'vue';
import api from '../api.js';

const assets = ref([]);
const loading = ref(true);
const loadError = ref('');

onMounted(async () => {
  try {
    const { data } = await api.get('/admin/assets');
    assets.value = data.data || [];
  } catch { assets.value = []; loadError.value = '加载失败，请检查网络后重试'; }
  loading.value = false;
});
</script>

<template>
  <div>
    <h2 style="margin-bottom:20px">素材管理</h2>
    <div v-if="loading" style="color:var(--text-muted)">加载中...</div>
    <div v-if="loadError" class="error-banner">{{ loadError }}</div>
    <table v-else class="data-table">
      <thead><tr><th>ID</th><th>名称</th><th>类型</th><th>标签</th><th>大小</th><th>状态</th></tr></thead>
      <tbody>
        <tr v-for="a in assets" :key="a.id">
          <td>{{ a.id }}</td>
          <td>{{ a.name }}</td>
          <td><span class="badge info">{{ a.type === 'bgm' ? 'BGM' : a.type === 'sfx' ? '音效' : a.type === 'image' ? '图片' : a.type || '—' }}</span></td>
          <td style="font-size:0.85rem">{{ a.tags || '—' }}</td>
          <td>{{ a.file_size_bytes ? (a.file_size_bytes / 1024).toFixed(1) + ' KB' : '—' }}</td>
          <td><span class="badge" :class="a.status === 'active' ? 'success' : 'error'">{{ a.status === 'active' ? '启用' : '禁用' }}</span></td>
        </tr>
        <tr v-if="assets.length === 0">
          <td colspan="6" style="text-align:center;color:var(--text-muted);padding:32px">暂无素材</td>
        </tr>
      </tbody>
    </table>
  </div>
</template>
