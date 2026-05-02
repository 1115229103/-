<script setup>
import { ref, onMounted } from 'vue';
import api from '../api.js';

const users = ref([]);
const loading = ref(true);

onMounted(async () => {
  try {
    const { data } = await api.get('/admin/roles');
    users.value = data.data?.data || data.data || [];
  } catch { users.value = []; }
  loading.value = false;
});

async function toggleRole(user) {
  const newRole = user.role === 'admin' ? 'user' : 'admin';
  try {
    await api.put(`/admin/roles/${user.id}`, { role: newRole });
    user.role = newRole;
  } catch {}
}
</script>

<template>
  <div>
    <h2 style="margin-bottom:20px">权限管理</h2>
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
      </tbody>
    </table>
  </div>
</template>
