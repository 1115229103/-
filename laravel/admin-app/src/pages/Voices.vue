<script setup>
import { ref, onMounted } from 'vue';
import api from '../api.js';

const voices = ref([]);
const loading = ref(true);
const loadError = ref('');

onMounted(async () => {
  try {
    const { data } = await api.get('/admin/voice-library');
    voices.value = data.data || [];
  } catch { voices.value = []; loadError.value = '加载失败，请检查网络后重试'; }
  loading.value = false;
});
</script>

<template>
  <div>
    <h2 style="margin-bottom:20px">音色库管理</h2>
    <div v-if="loading" style="color:var(--text-muted)">加载中...</div>
    <div v-if="loadError" class="error-banner">{{ loadError }}</div>
    <table v-else class="data-table">
      <thead><tr><th>ID</th><th>名称</th><th>提供商</th><th>Voice ID</th><th>性别</th><th>语言</th><th>风格</th><th>状态</th></tr></thead>
      <tbody>
        <tr v-for="v in voices" :key="v.id">
          <td>{{ v.id }}</td>
          <td>{{ v.name }}</td>
          <td>{{ v.provider }}</td>
          <td style="font-family:var(--mono);font-size:0.8rem">{{ v.voice_id }}</td>
          <td>{{ v.gender === 'male' ? '男' : v.gender === 'female' ? '女' : v.gender || '—' }}</td>
          <td>{{ v.language }}</td>
          <td>{{ v.style }}</td>
          <td><span class="badge" :class="v.status === 'active' ? 'success' : 'error'">{{ v.status === 'active' ? '启用' : '禁用' }}</span></td>
        </tr>
        <tr v-if="voices.length === 0">
          <td colspan="8" style="text-align:center;color:var(--text-muted);padding:32px">暂无音色数据</td>
        </tr>
      </tbody>
    </table>
  </div>
</template>
