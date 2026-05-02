<template>
  <div>
    <div class="page-header"><h2 class="page-title">Model Registry ({{ items.length }})</h2><button @click="showForm = !showForm" class="btn btn-purple">{{ showForm ? 'Cancel' : '+ Add Model' }}</button></div>
    <div v-if="showForm" class="form-card">
      <div class="form-grid">
        <select v-model="form.category" required class="input"><option value="">Category</option><option v-for="c in categories" :key="c" :value="c">{{ c }}</option></select>
        <input v-model="form.model_name" placeholder="model_name" class="input" />
        <input v-model="form.display_name" placeholder="display_name" class="input" />
        <input v-model="form.provider" placeholder="provider" class="input" />
        <select v-model="form.api_type" class="input"><option value="">api_type</option><option v-for="a in apiTypes" :key="a" :value="a">{{ a }}</option></select>
        <input v-model="form.base_url" placeholder="base_url" class="input" />
        <input v-model="form.request_path" placeholder="request_path (e.g. /v1/chat/completions)" class="input" />
        <input v-model="form.description" placeholder="description" class="input" />
      </div>
      <div class="form-actions"><button @click="save" class="btn btn-purple">Save</button></div>
    </div>
    <table class="data-table"><thead><tr><th>ID</th><th>Category</th><th>Name</th><th>Provider</th><th>API</th><th>Status</th><th></th></tr></thead><tbody><tr v-for="m in items" :key="m.id"><td>{{ m.id }}</td><td><span class="badge">{{ m.category }}</span></td><td>{{ m.display_name || m.model_name }}</td><td>{{ m.provider }}</td><td>{{ m.api_type }}</td><td><span :class="['badge', m.status === 'active' ? 'badge-green' : 'badge-red']">{{ m.status }}</span></td><td><button @click="toggleStatus(m)" class="btn-sm">{{ m.status === 'active' ? 'Disable' : 'Enable' }}</button></td></tr></tbody></table>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import client from '../api/client';
const items = ref([]);
const showForm = ref(false);
const categories = ['llm','image_gen','consistency','image_enhance','image2video','video_enhance','tts','music','asr','moderation'];
const apiTypes = ['openai','anthropic','gemini','kling','elevenlabs','stability','replicate','bfl','azure','custom'];
const form = ref({ category:'', model_name:'', display_name:'', provider:'', api_type:'', base_url:'', request_path:'', description:'' });

onMounted(async () => { try { const r = await client.get('/models'); items.value = r.data.data; } catch {} });
const save = async () => { try { await client.post('/models', form.value); showForm.value = false; form.value = { category:'', model_name:'', display_name:'', provider:'', api_type:'', base_url:'', request_path:'', description:'' }; const r = await client.get('/models'); items.value = r.data.data; } catch {} };
const toggleStatus = async (m) => { try { await client.put(`/models/${m.id}/status`, { status: m.status === 'active' ? 'inactive' : 'active' }); m.status = m.status === 'active' ? 'inactive' : 'active'; } catch {} };
</script>

<style scoped>
.page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; }
.page-title { font-size: 1.75rem; font-weight: 700; }
.btn { padding: 0.6rem 1.25rem; border-radius: 0.5rem; font-weight: 600; cursor: pointer; border: none; color: #fff; font-size: 0.875rem; }
.btn-purple { background: #7c3aed; }
.btn-sm { padding: 0.3rem 0.75rem; border-radius: 0.35rem; border: 1px solid rgba(255,255,255,0.2); background: none; color: rgba(255,255,255,0.7); cursor: pointer; font-size: 0.75rem; }
.form-card { padding: 1.5rem; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); border-radius: 0.75rem; margin-bottom: 1.5rem; }
.form-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 0.75rem; }
.form-actions { margin-top: 1rem; }
.input { padding: 0.5rem 0.75rem; background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.2); border-radius: 0.35rem; color: #fff; font-size: 0.8rem; }
.input:focus { outline: none; border-color: #a855f7; }
.data-table { width: 100%; border-collapse: collapse; font-size: 0.875rem; }
.data-table th { text-align: left; padding: 0.75rem; border-bottom: 1px solid rgba(255,255,255,0.1); color: rgba(255,255,255,0.4); font-weight: 500; }
.data-table td { padding: 0.75rem; border-bottom: 1px solid rgba(255,255,255,0.05); }
.badge { padding: 0.15rem 0.5rem; border-radius: 9999px; font-size: 0.7rem; background: rgba(255,255,255,0.1); color: rgba(255,255,255,0.6); }
.badge-green { background: rgba(34,197,94,0.15); color: #4ade80; }
.badge-red { background: rgba(239,68,68,0.15); color: #f87171; }
</style>
