<script setup>
import { ref, onMounted } from 'vue';
import api from '../api.js';

const works = ref([]);
const loading = ref(true);
const filterStatus = ref('pending_review');
const actionError = ref('');
const actionLoading = ref(null);
const loadError = ref('');

const statuses = [
  { value: 'pending_review', label: '待审核' },
  { value: 'completed', label: '已通过' },
  { value: 'rejected', label: '已拒绝' },
  { value: 'all', label: '全部' },
];

async function load() {
  loading.value = true;
  try {
    const { data } = await api.get('/admin/review/works', { params: { status: filterStatus.value } });
    works.value = data.data?.data || data.data || [];
  } catch { works.value = []; loadError.value = '加载失败，请检查网络后重试'; }
  loading.value = false;
}

onMounted(load);

async function approve(id) {
  actionError.value = '';
  actionLoading.value = id;
  try {
    await api.put(`/admin/review/works/${id}/approve`);
    await load();
  } catch (e) {
    actionError.value = '审核失败: ' + (e.response?.data?.message || '请重试');
  }
  actionLoading.value = null;
}

async function reject(id) {
  actionError.value = '';
  actionLoading.value = id;
  try {
    await api.put(`/admin/review/works/${id}/reject`);
    await load();
  } catch (e) {
    actionError.value = '操作失败: ' + (e.response?.data?.message || '请重试');
  }
  actionLoading.value = null;
}
</script>

<template>
  <div>
    <h2 style="margin-bottom:20px">作品审核</h2>
    <div style="display:flex;gap:8px;margin-bottom:16px;flex-wrap:wrap">
      <button v-for="s in statuses" :key="s.value" :class="'btn small ' + (filterStatus === s.value ? 'primary' : 'secondary')" @click="filterStatus = s.value; load()">{{ s.label }}</button>
    </div>
    <div v-if="actionError" class="error-banner">{{ actionError }}</div>
    <div v-if="loadError" class="error-banner">{{ loadError }}</div>
    <div v-if="loading" style="color:var(--text-muted)">加载中...</div>
    <table v-else class="data-table">
      <thead><tr><th>ID</th><th>标题</th><th>作者</th><th>风格</th><th>状态</th><th>创建时间</th><th>操作</th></tr></thead>
      <tbody>
        <tr v-if="works.length === 0">
          <td colspan="7" style="text-align:center;color:var(--text-muted);padding:32px">暂无待审核作品</td>
        </tr>
        <tr v-for="w in works" :key="w.id">
          <td>{{ w.id }}</td>
          <td>{{ w.title }}</td>
          <td>{{ w.user?.name || '—' }}</td>
          <td>{{ w.style || '—' }}</td>
          <td><span class="badge" :class="w.status === 'completed' ? 'success' : w.status === 'rejected' ? 'error' : w.status === 'processing' ? 'info' : ''">{{ w.status === 'completed' ? '已通过' : w.status === 'rejected' ? '已拒绝' : w.status === 'pending_review' ? '待审核' : w.status }}</span></td>
          <td>{{ w.created_at?.substring(0, 10) }}</td>
          <td>
            <div style="display:flex;gap:6px" v-if="w.status === 'pending_review'">
              <button class="btn small success" :disabled="actionLoading !== null" @click="approve(w.id)">通过</button>
              <button class="btn small" style="color:var(--error)" :disabled="actionLoading !== null" @click="reject(w.id)">拒绝</button>
            </div>
            <span v-else style="color:var(--text-muted);font-size:0.85rem">—</span>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>
