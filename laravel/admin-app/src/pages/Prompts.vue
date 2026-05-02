<script setup>
import { ref, onMounted } from 'vue';
import api from '../api.js';

const templates = ref([]);
const loading = ref(true);
const expanded = ref(null);

onMounted(async () => {
  try {
    const { data } = await api.get('/admin/prompt-templates');
    templates.value = data.data || [];
  } catch { templates.value = []; }
  loading.value = false;
});
</script>

<template>
  <div>
    <h2 style="margin-bottom:20px">提示词模板管理</h2>
    <div v-if="loading" style="color:var(--text-muted)">加载中...</div>
    <div v-else class="configs-list">
      <div v-for="t in templates" :key="t.id || t.stage" class="config-card" style="flex-direction:column;align-items:flex-start;cursor:pointer" @click="expanded = expanded === t.stage ? null : t.stage">
        <div style="display:flex;align-items:center;justify-content:space-between;width:100%">
          <div>
            <strong style="font-family:var(--mono);font-size:0.9rem">{{ t.stage }}</strong>
          </div>
          <span style="color:var(--text-muted);font-size:0.8rem">{{ expanded === t.stage ? '收起 ▲' : '展开 ▼' }}</span>
        </div>
        <div v-if="expanded === t.stage" style="margin-top:12px;width:100%">
          <label>System Prompt</label>
          <pre style="background:var(--bg-input);padding:12px;border-radius:var(--radius);font-size:0.8rem;white-space:pre-wrap;max-height:200px;overflow-y:auto">{{ t.system_prompt }}</pre>
          <label style="margin-top:12px">User Prompt Template</label>
          <pre style="background:var(--bg-input);padding:12px;border-radius:var(--radius);font-size:0.8rem;white-space:pre-wrap;max-height:200px;overflow-y:auto">{{ t.user_prompt_template }}</pre>
        </div>
      </div>
      <p v-if="templates.length === 0" style="color:var(--text-muted);text-align:center;padding:32px">暂无模板</p>
    </div>
  </div>
</template>
