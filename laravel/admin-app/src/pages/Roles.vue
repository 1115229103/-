<script setup>
import { ref, onMounted } from 'vue';
import api from '../api.js';
import Pagination from '../components/Pagination.vue';

const users = ref([]);
const loading = ref(true);
const loadError = ref('');
const actionError = ref('');
const page = ref(1);
const lastPage = ref(1);
const total = ref(0);

async function load() {
  loading.value = true;
  try {
    const { data } = await api.get('/admin/roles', { params: { page: page.value } });
    const p = data.data;
    users.value = p.data || [];
    lastPage.value = p.last_page || 1;
    total.value = p.total || 0;
  } catch { users.value = []; loadError.value = '加载失败，请检查网络后重试'; }
  loading.value = false;
}

function goToPage(p) { page.value = p; load(); }

onMounted(load);

async function toggleRole(user) {
  actionError.value = '';
  const newRole = user.role === 'admin' ? 'user' : 'admin';
  const prevRole = user.role;
  user.role = newRole;
  try {
    await api.put(`/admin/roles/${user.id}`, { role: newRole });
  } catch (e) {
    user.role = prevRole;
    actionError.value = '操作失败: ' + (e.response?.data?.message || '请重试');
  }
}
</script>

<template>
  <div>
    <h2 style="margin-bottom:20px">权限管理</h2>
    <div v-if="actionError" class="error-banner">{{ actionError }}</div>
    <div v-if="loadError" class="error-banner">{{ loadError }}</div>
    <div v-if="loading" style="color:var(--text-muted)">加载中...</div>
    <table v-else class="data-table">
      <thead><tr><th>ID</th><th>用户</th><th>邮箱</th><th>角色</th><th>注册时间</th><th>操作</th></tr></thead>
      <tbody>
        <tr v-for="u in users" :key="u.id">
          <td>{{ u.id }}</td>
          <td>{{ u.name }}</td>
          <td>{{ u.email }}</td>
          <td><span class="badge" :class="u.role === 'admin' ? 'success' : ''">{{ u.role === 'admin' ? '管理员' : '普通用户' }}</span></td>
          <td>{{ u.created_at?.substring(0, 10) }}</td>
          <td>
            <button class="btn small" :class="u.role === 'admin' ? 'secondary' : 'primary'" @click="toggleRole(u)">
              {{ u.role === 'admin' ? '降级为用户' : '升级为管理员' }}
            </button>
          </td>
        </tr>
        <tr v-if="users.length === 0">
          <td colspan="6" style="text-align:center;color:var(--text-muted);padding:32px">暂无用户数据</td>
        </tr>
      </tbody>
    </table>
    <Pagination :currentPage="page" :lastPage="lastPage" :total="total" :perPage="30" :loading="loading" @page-change="goToPage" />
  </div>
</template>
