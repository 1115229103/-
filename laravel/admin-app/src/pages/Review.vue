<script setup>
import { ref, onMounted } from 'vue';
import api from '../api.js';

const works = ref([]);
const loading = ref(true);
const filterStatus = ref('pending_review');

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
  } catch { works.value = []; }
  loading.value = false;
}

onMounted(load);

async function approve(id) {
  await api.put(`/admin/review/works/${id}/approve`);
  load();
}

async function reject(id) {
  await api.put(`/admin/review/works/${id}/reject`);
  load();
}
</script>

<template>
  <div>
    <h2 style="margin-bottom:20px">作品审核</h2>
    <div style="display:flex;gap:8px;margin-bottom:16px;flex-wrap:wrap">
      <button v-for="s in statuses" :key="s.value" :class="'btn small ' + (filterStatus === s.value ? 'primary' : 'secondary')" @click="filterStatus = s.value; load()">{{ s.label }}</button>
    </div>
    <div v-if="loading" style="color:var(--text-muted)">加载中...</div>
    <table v-else class="data-table">
      <thead><tr><th>ID</th><th>标题</th><th>作者</th><th>风格</th><th>状态</th><th>创建时间</th><th>操作</th></tr></thead>
      <tbody>
        <tr v-for="w in works" :key="w.id">
          <td>{{ w.id }}</td>
          <td>{{ w.title }}</td>
          <td>{{ w.user?.name || '—' }}</td>
          <td>{{ w.style || '—' }}</td>
          <td><span class="badge" :class="w.status === 'completed' ? 'success' : w.status === 'rejected' ? 'error' : w.status === 'processing' ? 'info' : ''">{{ w.status === 'completed' ? '已通过' : w.status === 'rejected' ? '已拒绝' : w.status === 'pending_review' ? '待审核' : w.status }}</span></td>
          <td>{{ w.created_at?.substring(0, 10) }}</td>
          <td>
            <div style="display:flex;gap:6px" v-if="w.status === 'pending_review'">
              <button class="btn small success" @click="approve(w.id)">通过</button>
              <button class="btn small" style="color:var(--error)" @click="reject(w.id)">拒绝</button>
            </div>
            <span v-else style="color:var(--text-muted);font-size:0.85rem">—</span>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>
