<script setup>
import { ref, onMounted } from 'vue';
import api from '../api.js';

const users = ref([]);
const loading = ref(true);

onMounted(async () => {
  try {
    const { data } = await api.get('/admin/users');
    users.value = data.data?.data || data.data || [];
  } catch { users.value = []; }
  loading.value = false;
});
</script>

<template>
  <div>
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px">
      <h2>用户管理</h2>
      <span style="color:var(--text-muted);font-size:0.85rem">{{ users.length }} 个用户</span>
    </div>

    <div v-if="loading" style="color:var(--text-muted)">加载中...</div>
    <table v-else class="data-table">
      <thead>
        <tr>
          <th>ID</th>
          <th>名称</th>
          <th>邮箱</th>
          <th>套餐</th>
          <th>注册时间</th>
          <th>状态</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="u in users" :key="u.id">
          <td>{{ u.id }}</td>
          <td>{{ u.name }}</td>
          <td style="font-size:0.85rem">{{ u.email }}</td>
          <td>
            <span class="badge info" v-if="u.membership?.plan">
              {{ u.membership.plan.name || '免费版' }}
            </span>
            <span class="badge" v-else style="background:rgba(156,163,175,0.1);color:#9ca3af">免费版</span>
          </td>
          <td style="font-size:0.85rem">{{ u.created_at?.substring(0, 10) }}</td>
          <td>
            <span class="badge" :class="u.deleted_at ? 'error' : 'success'">
              {{ u.deleted_at ? '已禁用' : '正常' }}
            </span>
          </td>
        </tr>
        <tr v-if="users.length === 0">
          <td colspan="6" style="text-align:center;color:var(--text-muted);padding:32px">暂无用户</td>
        </tr>
      </tbody>
    </table>
  </div>
</template>
