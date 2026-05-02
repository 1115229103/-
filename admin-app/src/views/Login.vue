<template>
  <div class="login-page">
    <div class="login-card">
      <h1>AIStory Admin</h1>
      <form @submit.prevent="handleLogin">
        <input v-model="email" type="email" placeholder="Email" required class="input" />
        <input v-model="password" type="password" placeholder="Password" required class="input" />
        <button type="submit" class="btn-primary" :disabled="loading">{{ loading ? 'Logging in...' : 'Log In' }}</button>
        <p v-if="error" class="error">{{ error }}</p>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';

const router = useRouter();
const email = ref('');
const password = ref('');
const loading = ref(false);
const error = ref('');

const handleLogin = async () => {
  loading.value = true; error.value = '';
  try {
    const r = await axios.post('http://127.0.0.1:8000/api/v1/auth/login', { email: email.value, password: password.value });
    localStorage.setItem('admin_token', r.data.data.token);
    router.push('/dashboard');
  } catch (e) { error.value = e.response?.data?.error || 'Login failed'; }
  finally { loading.value = false; }
};
</script>

<style scoped>
.login-page { min-height: 100vh; display: flex; align-items: center; justify-content: center; background: #0f172a; }
.login-card { width: 100%; max-width: 400px; padding: 2.5rem; background: rgba(255,255,255,0.05); border-radius: 1rem; border: 1px solid rgba(255,255,255,0.1); }
h1 { font-size: 1.5rem; font-weight: 700; margin-bottom: 1.5rem; text-align: center; }
.input { width: 100%; padding: 0.75rem; margin-bottom: 1rem; background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.2); border-radius: 0.5rem; color: #fff; font-size: 0.875rem; }
.input:focus { outline: none; border-color: #a855f7; }
.btn-primary { width: 100%; padding: 0.75rem; background: #7c3aed; border: none; border-radius: 0.5rem; color: #fff; font-weight: 600; cursor: pointer; }
.btn-primary:disabled { opacity: 0.5; cursor: not-allowed; }
.error { margin-top: 1rem; padding: 0.75rem; background: rgba(239,68,68,0.1); border: 1px solid rgba(239,68,68,0.3); border-radius: 0.5rem; color: #f87171; font-size: 0.875rem; }
</style>
