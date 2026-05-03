<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import api from '../api.js';

const router = useRouter();
const form = ref({ email: '', password: '' });
const error = ref('');
const loading = ref(false);

async function login() {
  error.value = '';
  if (!form.value.email || !form.value.password) {
    error.value = '请填写邮箱和密码';
    return;
  }
  loading.value = true;
  try {
    const { data } = await api.post('/auth/login', form.value);
    localStorage.setItem('admin_token', data.data.token);
    localStorage.setItem('admin_user', JSON.stringify(data.data.user));
    router.replace('/');
  } catch (e) {
    const msg = e.response?.data?.errors
      ? Object.values(e.response.data.errors).flat().join('; ')
      : (e.response?.data?.message || '登录失败，请检查邮箱和密码');
    error.value = msg;
  }
  loading.value = false;
}
</script>

<template>
  <div class="login-page">
    <form class="login-card" @submit.prevent="login">
      <h1>AIStory 管理后台</h1>
      <p style="color:var(--text-muted);margin-bottom:24px">管理员登录</p>
      <div v-if="error" class="form-error">{{ error }}</div>
      <label for="login-email">邮箱</label>
      <input id="login-email" v-model="form.email" type="email" class="form-input" placeholder="admin@example.com" />
      <label for="login-password" style="margin-top:16px">密码</label>
      <input id="login-password" v-model="form.password" type="password" class="form-input" placeholder="请输入密码" />
      <button type="submit" class="btn" :disabled="loading" style="width:100%;margin-top:24px">
        {{ loading ? '登录中...' : '登录' }}
      </button>
    </form>
  </div>
</template>

<style scoped>
.login-page {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background: var(--bg);
}
.login-card {
  background: var(--bg-card);
  border: 1px solid var(--border);
  border-radius: 8px;
  padding: 32px;
  width: 100%;
  max-width: 400px;
}
.login-card h1 {
  font-size: 1.5rem;
  margin: 0 0 8px;
}
.form-error {
  background: rgba(239,68,68,0.1);
  color: #ef4444;
  border: 1px solid rgba(239,68,68,0.3);
  border-radius: 4px;
  padding: 8px 12px;
  margin-bottom: 16px;
  font-size: 0.875rem;
}
</style>
