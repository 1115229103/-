<script setup>
import { ref, reactive, onMounted, onUnmounted, computed, watch } from 'vue';
import api from '../api.js';

const models = ref([]);
const loading = ref(true);
const loadError = ref('');
const selectedCat = ref('');
const showModal = ref(false);
const editing = ref(null); // null = create, object = edit
const saving = ref(false);

const categories = [
  { value: '', label: '全部' },
  { value: 'llm', label: 'LLM 大语言模型' },
  { value: 'image_gen', label: '图像生成' },
  { value: 'consistency', label: '角色一致性' },
  { value: 'image_enhance', label: '图像增强' },
  { value: 'image2video', label: '图生视频' },
  { value: 'video_enhance', label: '视频增强' },
  { value: 'tts', label: 'TTS 语音合成' },
  { value: 'music', label: '音乐生成' },
  { value: 'asr', label: 'ASR 语音识别' },
  { value: 'moderation', label: '内容审核' },
];

const apiTypes = [
  'openai', 'anthropic', 'gemini', 'kling', 'elevenlabs',
  'stability', 'replicate', 'bfl', 'azure', 'custom',
];

const emptyForm = () => ({
  category: 'llm',
  model_name: '',
  display_name: '',
  provider: '',
  api_type: 'openai',
  base_url: '',
  request_path: '',
  default_params: '',
  required_fields: '',
  description: '',
  docs_url: '',
  logo_url: '',
  sort_order: 0,
  status: 'active',
});

const form = reactive(emptyForm());

async function load() {
  loading.value = true;
  try {
    const params = selectedCat.value ? `?category=${selectedCat.value}` : '';
    const { data } = await api.get(`/admin/models${params}`);
    models.value = data.data || [];
  } catch { models.value = []; loadError.value = '加载失败，请检查网络后重试'; }
  loading.value = false;
}

onMounted(load);

function onKeydown(e) {
  if (e.key === 'Escape' && showModal.value) {
    showModal.value = false;
  }
}
watch(showModal, (v) => {
  if (v) document.addEventListener('keydown', onKeydown);
  else document.removeEventListener('keydown', onKeydown);
});
onUnmounted(() => document.removeEventListener('keydown', onKeydown));

function catFilter(cat) { selectedCat.value = cat; load(); }

function openCreate() {
  editing.value = null;
  Object.assign(form, emptyForm());
  showModal.value = true;
}

function openEdit(m) {
  editing.value = m;
  form.category = m.category;
  form.model_name = m.model_name;
  form.display_name = m.display_name;
  form.provider = m.provider;
  form.api_type = m.api_type;
  form.base_url = m.base_url || '';
  form.request_path = m.request_path || '';
  form.default_params = m.default_params ? JSON.stringify(m.default_params, null, 2) : '';
  form.required_fields = m.required_fields ? JSON.stringify(m.required_fields, null, 2) : '';
  form.description = m.description || '';
  form.docs_url = m.docs_url || '';
  form.logo_url = m.logo_url || '';
  form.sort_order = m.sort_order || 0;
  form.status = m.status || 'active';
  showModal.value = true;
}

function safeJsonParse(s, label) {
  if (!s || !s.trim()) return null;
  try { return JSON.parse(s); } catch {
    throw new Error(label + ' JSON格式错误，请检查');
  }
}

async function saveModel() {
  if (!form.model_name.trim() || !form.display_name.trim() || !form.provider.trim() || !form.base_url.trim()) {
    alert('请填写所有必填字段（模型标识、显示名称、提供商、Base URL）');
    return;
  }
  saving.value = true;
  try {
    const payload = {
      category: form.category,
      model_name: form.model_name,
      display_name: form.display_name,
      provider: form.provider,
      api_type: form.api_type,
      base_url: form.base_url,
      request_path: form.request_path || null,
      default_params: safeJsonParse(form.default_params, '默认参数'),
      required_fields: safeJsonParse(form.required_fields, '必填字段'),
      description: form.description || null,
      docs_url: form.docs_url || null,
      logo_url: form.logo_url || null,
      sort_order: form.sort_order,
      status: form.status,
    };
    if (editing.value) {
      await api.put(`/admin/models/${editing.value.id}`, payload);
    } else {
      await api.post('/admin/models', payload);
    }
    showModal.value = false;
    load();
  } catch (e) {
    const msg = e.response?.data?.errors
      ? Object.values(e.response.data.errors).flat().join('; ')
      : e.message;
    alert('保存失败: ' + msg);
  }
  saving.value = false;
}

const toggling = ref(null);

async function toggleStatus(m) {
  if (toggling.value) return;
  toggling.value = m.id;
  try {
    const { data } = await api.put(`/admin/models/${m.id}/status`);
    m.status = data.data.status;
  } catch (e) {
    alert('操作失败: ' + (e.response?.data?.message || '请重试'));
  } finally {
    toggling.value = null;
  }
}

async function deleteModel(m) {
  if (!confirm(`确认删除模型 "${m.display_name}"？此操作不可恢复。`)) return;
  try {
    await api.delete(`/admin/models/${m.id}`);
    models.value = models.value.filter(x => x.id !== m.id);
  } catch (e) {
    alert('删除失败: ' + (e.response?.data?.message || '请重试'));
  }
}

const catLabel = (v) => categories.find(c => c.value === v)?.label || v;
</script>

<template>
  <div>
    <!-- Header -->
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px">
      <h2>模型注册管理</h2>
      <div style="display:flex;align-items:center;gap:12px">
        <span style="color:var(--text-muted);font-size:0.85rem">{{ models.length }} 个模型</span>
        <button class="btn primary" @click="openCreate">+ 添加模型</button>
      </div>
    </div>

    <!-- Category filter -->
    <div style="display:flex;flex-wrap:wrap;gap:6px;margin-bottom:20px">
      <button
        v-for="c in categories" :key="c.value"
        class="btn small" :class="selectedCat === c.value ? 'primary' : ''"
        @click="catFilter(c.value)"
      >{{ c.label }}</button>
    </div>

    <!-- Table -->
    <div v-if="loading" style="color:var(--text-muted)">加载中...</div>
    <div v-if="loadError" class="error-banner">{{ loadError }}</div>
    <table v-else class="data-table">
      <thead>
        <tr>
          <th>ID</th>
          <th>类别</th>
          <th>模型名称</th>
          <th>显示名称</th>
          <th>提供商</th>
          <th>API</th>
          <th>状态</th>
          <th>操作</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="m in models" :key="m.id">
          <td>{{ m.id }}</td>
          <td><span class="badge info">{{ m.category }}</span></td>
          <td style="font-family:var(--mono);font-size:0.85rem;max-width:180px;overflow:hidden;text-overflow:ellipsis" :title="m.model_name">{{ m.model_name }}</td>
          <td>{{ m.display_name }}</td>
          <td>{{ m.provider }}</td>
          <td style="font-family:var(--mono);font-size:0.8rem">{{ m.api_type }}</td>
          <td>
            <span class="badge" :class="m.status === 'active' ? 'success' : 'error'">
              {{ m.status === 'active' ? '启用' : '禁用' }}
            </span>
          </td>
          <td style="display:flex;gap:4px">
            <button class="btn small" @click="openEdit(m)" title="编辑">✎</button>
            <button class="btn small" @click="toggleStatus(m)" :disabled="toggling !== null" :title="m.status === 'active' ? '禁用' : '启用'">
              {{ toggling === m.id ? '...' : (m.status === 'active' ? '⊘' : '✓') }}
            </button>
            <button class="btn small danger" @click="deleteModel(m)" title="删除">✕</button>
          </td>
        </tr>
        <tr v-if="models.length === 0">
          <td colspan="8" style="text-align:center;color:var(--text-muted);padding:32px">暂无数据 — 点击「添加模型」开始</td>
        </tr>
      </tbody>
    </table>

    <!-- Create/Edit Modal -->
    <div v-if="showModal" class="modal-overlay" @click.self="showModal = false">
      <div class="modal" style="max-width:640px;max-height:85vh;overflow-y:auto">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px">
          <h3>{{ editing ? '编辑模型' : '添加模型' }}</h3>
          <button class="btn small" @click="showModal = false">✕</button>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
          <!-- Category -->
          <div class="form-group">
            <label>类别 *</label>
            <select v-model="form.category" class="form-input">
              <option v-for="c in categories.slice(1)" :key="c.value" :value="c.value">{{ c.label }}</option>
            </select>
          </div>
          <!-- API Type -->
          <div class="form-group">
            <label>API 协议 *</label>
            <select v-model="form.api_type" class="form-input">
              <option v-for="t in apiTypes" :key="t" :value="t">{{ t }}</option>
            </select>
          </div>
          <!-- Model Name -->
          <div class="form-group">
            <label>模型标识 *</label>
            <input v-model="form.model_name" class="form-input" placeholder="gpt-4o / claude-sonnet-4-6" />
          </div>
          <!-- Display Name -->
          <div class="form-group">
            <label>显示名称 *</label>
            <input v-model="form.display_name" class="form-input" placeholder="GPT-4o / Claude Sonnet 4.6" />
          </div>
          <!-- Provider -->
          <div class="form-group">
            <label>提供商 *</label>
            <input v-model="form.provider" class="form-input" placeholder="OpenAI / Anthropic" />
          </div>
          <!-- Status -->
          <div class="form-group">
            <label>状态</label>
            <select v-model="form.status" class="form-input">
              <option value="active">启用</option>
              <option value="inactive">禁用</option>
            </select>
          </div>
          <!-- Base URL -->
          <div class="form-group" style="grid-column:1/-1">
            <label>Base URL *</label>
            <input v-model="form.base_url" class="form-input" placeholder="https://api.openai.com" />
          </div>
          <!-- Request Path -->
          <div class="form-group">
            <label>请求路径</label>
            <input v-model="form.request_path" class="form-input" placeholder="/v1/chat/completions" />
          </div>
          <!-- Sort Order -->
          <div class="form-group">
            <label>排序</label>
            <input v-model.number="form.sort_order" type="number" class="form-input" min="0" />
          </div>
          <!-- Docs URL -->
          <div class="form-group" style="grid-column:1/-1">
            <label>文档链接</label>
            <input v-model="form.docs_url" class="form-input" placeholder="https://platform.openai.com/docs" />
          </div>
          <!-- Description -->
          <div class="form-group" style="grid-column:1/-1">
            <label>描述</label>
            <textarea v-model="form.description" class="form-input" rows="2" placeholder="模型适用场景说明"></textarea>
          </div>
          <!-- Default Params (JSON) -->
          <div class="form-group" style="grid-column:1/-1">
            <label>默认参数 (JSON)</label>
            <textarea v-model="form.default_params" class="form-input mono" rows="3" placeholder='{"temperature": 0.7, "max_tokens": 4096}' style="font-family:var(--mono);font-size:0.8rem"></textarea>
          </div>
          <!-- Required Fields (JSON) -->
          <div class="form-group" style="grid-column:1/-1">
            <label>用户必填字段 (JSON)</label>
            <textarea v-model="form.required_fields" class="form-input mono" rows="3" placeholder='[{"key": "api_key", "label": "API Key", "type": "password"}]' style="font-family:var(--mono);font-size:0.8rem"></textarea>
          </div>
        </div>

        <div style="display:flex;justify-content:flex-end;gap:8px;margin-top:20px;padding-top:16px;border-top:1px solid var(--border)">
          <button class="btn" @click="showModal = false">取消</button>
          <button class="btn primary" @click="saveModel" :disabled="saving">
            {{ saving ? '保存中...' : '保存' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.modal-overlay {
  position: fixed; inset: 0;
  background: rgba(0,0,0,0.45);
  display: flex; align-items: center; justify-content: center;
  z-index: 1000;
}
.modal {
  background: var(--bg);
  border-radius: 8px;
  padding: 24px;
  width: 100%;
  box-shadow: 0 8px 32px rgba(0,0,0,0.2);
}
.form-group { display: flex; flex-direction: column; gap: 4px; }
.form-group label { font-size: 0.85rem; color: var(--text-muted); }
.form-input {
  padding: 8px 10px;
  border: 1px solid var(--border);
  border-radius: 4px;
  font-size: 0.9rem;
  background: var(--bg);
  color: var(--text);
}
.form-input:focus { border-color: var(--primary); outline: none; }
.mono { font-family: var(--mono); font-size: 0.8rem; }
.btn.danger { color: #e74c3c; border-color: #e74c3c; }
.btn.danger:hover { background: #e74c3c; color: #fff; }
</style>
