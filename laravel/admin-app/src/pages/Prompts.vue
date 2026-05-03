<script setup>
import { ref, onMounted } from 'vue';
import api from '../api.js';

const templates = ref([]);
const loading = ref(true);
const loadError = ref('');
const expanded = ref(null);
const editing = ref(null); // stage being edited
const saving = ref(false);

const editForm = ref({ system_prompt: '', user_prompt_template: '', output_schema: '' });

function safeJsonParse(s) {
  if (!s || !s.trim()) return null;
  try { return JSON.parse(s); } catch {
    alert('输出 Schema JSON格式错误，请检查');
    throw new Error('Invalid JSON');
  }
}

const stageLabels = {
  script_analysis: '环节1 · 文案解析',
  storyboard: '环节2 · 分镜规划',
  continuation: '环节3 · 文案续写',
  image_gen: '环节4 · 画面生成',
  consistency: '环节5 · 角色一致性',
  image_enhance: '环节6 · 图像后处理',
  image2video: '环节7 · 图生视频',
  video_enhance: '环节8 · 视频增强',
  tts: '环节9 · AI配音',
  music: '环节10 · 背景音乐',
  asr: '环节11 · 字幕生成',
  moderation: '环节12 · 敏感词检测',
};

onMounted(async () => {
  try {
    const { data } = await api.get('/admin/prompt-templates');
    templates.value = data.data || [];
  } catch { templates.value = []; loadError.value = '加载失败，请检查网络后重试'; }
  loading.value = false;
});

function toggleExpand(stage) {
  expanded.value = expanded.value === stage ? null : stage;
  if (editing.value && editing.value !== stage) editing.value = null;
}

function startEdit(t) {
  editing.value = t.stage;
  editForm.value = {
    system_prompt: t.system_prompt || '',
    user_prompt_template: t.user_prompt_template || '',
    output_schema: t.output_schema ? JSON.stringify(t.output_schema, null, 2) : '',
  };
}

function cancelEdit() { editing.value = null; }

async function saveEdit(stage) {
  saving.value = true;
  try {
    const payload = {
      system_prompt: editForm.value.system_prompt,
      user_prompt_template: editForm.value.user_prompt_template,
      output_schema: safeJsonParse(editForm.value.output_schema),
    };
    const { data } = await api.put(`/admin/prompt-templates/${stage}`, payload);
    // Update local cache
    const idx = templates.value.findIndex(t => t.stage === stage);
    if (idx >= 0) templates.value[idx] = data.data;
    editing.value = null;
  } catch (e) {
    alert('保存失败: ' + (e.response?.data?.errors ? Object.values(e.response.data.errors).flat().join('; ') : e.message));
  }
  saving.value = false;
}
</script>

<template>
  <div>
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px">
      <h2>提示词模板管理</h2>
      <span style="color:var(--text-muted);font-size:0.85rem">{{ templates.length }} 个环节</span>
    </div>

    <div v-if="loading" style="color:var(--text-muted)">加载中...</div>
    <div v-if="loadError" class="error-banner">{{ loadError }}</div>

    <div v-else class="configs-list">
      <div
        v-for="t in templates" :key="t.id || t.stage"
        class="config-card" style="flex-direction:column;align-items:flex-start"
      >
        <!-- Header (clickable expand) -->
        <div @click="toggleExpand(t.stage)" style="display:flex;align-items:center;justify-content:space-between;width:100%;cursor:pointer">
          <div>
            <strong style="font-family:var(--mono);font-size:0.9rem">{{ t.stage }}</strong>
            <span v-if="stageLabels[t.stage]" style="color:var(--text-muted);margin-left:8px;font-size:0.85rem">{{ stageLabels[t.stage] }}</span>
          </div>
          <span style="color:var(--text-muted);font-size:0.8rem">{{ expanded === t.stage ? '收起 ▲' : '展开 ▼' }}</span>
        </div>

        <!-- Expanded Content -->
        <div v-if="expanded === t.stage" style="margin-top:12px;width:100%">
          <div v-if="editing !== t.stage">
            <!-- View mode -->
            <label style="font-weight:600">System Prompt</label>
            <pre style="background:var(--bg-input);padding:12px;border-radius:4px;font-size:0.8rem;white-space:pre-wrap;max-height:160px;overflow-y:auto">{{ t.system_prompt || '(未设置)' }}</pre>

            <label style="font-weight:600;margin-top:10px;display:block">User Prompt 模板</label>
            <pre style="background:var(--bg-input);padding:12px;border-radius:4px;font-size:0.8rem;white-space:pre-wrap;max-height:160px;overflow-y:auto">{{ t.user_prompt_template || '(未设置)' }}</pre>

            <label v-if="t.output_schema" style="font-weight:600;margin-top:10px;display:block">期望输出 Schema</label>
            <pre v-if="t.output_schema" style="background:var(--bg-input);padding:12px;border-radius:4px;font-size:0.8rem;white-space:pre-wrap;max-height:120px;overflow-y:auto;font-family:var(--mono)">{{ JSON.stringify(t.output_schema, null, 2) }}</pre>

            <button class="btn small" style="margin-top:10px" @click.stop="startEdit(t)">✎ 编辑</button>
          </div>

          <!-- Edit mode -->
          <div v-else style="display:flex;flex-direction:column;gap:10px">
            <div class="form-group">
              <label>System Prompt</label>
              <textarea v-model="editForm.system_prompt" class="form-input" rows="6" style="font-family:var(--mono);font-size:0.8rem"></textarea>
            </div>
            <div class="form-group">
              <label>User Prompt 模板</label>
              <textarea v-model="editForm.user_prompt_template" class="form-input" rows="6" style="font-family:var(--mono);font-size:0.8rem"></textarea>
            </div>
            <div class="form-group">
              <label>输出 JSON Schema (可选)</label>
              <textarea v-model="editForm.output_schema" class="form-input" rows="4" style="font-family:var(--mono);font-size:0.8rem" placeholder='{"type": "object", "properties": {...}}'></textarea>
            </div>
            <div style="display:flex;gap:8px">
              <button class="btn primary small" @click.stop="saveEdit(t.stage)" :disabled="saving">{{ saving ? '保存中...' : '保存' }}</button>
              <button class="btn small" @click.stop="cancelEdit">取消</button>
            </div>
          </div>
        </div>
      </div>

      <p v-if="templates.length === 0" style="color:var(--text-muted);text-align:center;padding:32px">暂无模板 — 请运行 prompt_templates seeder</p>
    </div>
  </div>
</template>

<style scoped>
.config-card { margin-bottom: 10px; }
.form-group { display: flex; flex-direction: column; gap: 4px; width: 100%; }
.form-group label { font-size: 0.85rem; color: var(--text-muted); }
.form-input {
  padding: 8px 10px;
  border: 1px solid var(--border);
  border-radius: 4px;
  font-size: 0.9rem;
  background: var(--bg);
  color: var(--text);
  resize: vertical;
}
.form-input:focus { border-color: var(--primary); outline: none; }
</style>
