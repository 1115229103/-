<script setup>
import { ref, reactive, onMounted, onUnmounted, watch } from 'vue';
import api from '../api.js';

const plans = ref([]);
const loading = ref(true);
const loadError = ref('');
const showModal = ref(false);
const editing = ref(null);
const saving = ref(false);

const tiers = ['free', 'basic', 'pro', 'enterprise'];
const tierLabels = { free: '免费版', basic: '基础版', pro: '专业版', enterprise: '企业版' };

const emptyForm = () => ({
  name: '', slug: '', tier: 'basic',
  price_monthly_cny: '', price_yearly_cny: '',
  features: '', is_active: true, sort_order: 0,
});

const form = reactive(emptyForm());

onMounted(async () => {
  try {
    const { data } = await api.get('/admin/plans');
    plans.value = data.data || [];
  } catch { plans.value = []; loadError.value = '加载失败，请检查网络后重试'; }
  loading.value = false;
});

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

function openCreate() {
  editing.value = null;
  Object.assign(form, emptyForm());
  showModal.value = true;
}

function openEdit(p) {
  editing.value = p;
  form.name = p.name;
  form.slug = p.slug;
  form.tier = p.tier;
  form.price_monthly_cny = p.price_monthly_cny ?? '';
  form.price_yearly_cny = p.price_yearly_cny ?? '';
  form.features = p.features ? (typeof p.features === 'string' ? p.features : p.features.join('\n')) : '';
  form.is_active = p.is_active;
  form.sort_order = p.sort_order || 0;
  showModal.value = true;
}

async function savePlan() {
  if (!form.name.trim() || !form.slug.trim() || !form.tier) {
    alert('请填写所有必填字段（名称、标识、级别）');
    return;
  }
  saving.value = true;
  try {
    const payload = {
      name: form.name,
      slug: form.slug,
      tier: form.tier,
      price_monthly_cny: form.price_monthly_cny ? Number(form.price_monthly_cny) : null,
      price_yearly_cny: form.price_yearly_cny ? Number(form.price_yearly_cny) : null,
      features: form.features ? form.features.split('\n').map(s => s.trim()).filter(Boolean) : [],
      is_active: form.is_active,
      sort_order: form.sort_order,
    };
    if (editing.value) {
      await api.put(`/admin/plans/${editing.value.id}`, payload);
    } else {
      await api.post('/admin/plans', payload);
    }
    showModal.value = false;
    // Reload
    const { data } = await api.get('/admin/plans');
    plans.value = data.data || [];
  } catch (e) {
    const msg = e.response?.data?.errors
      ? Object.values(e.response.data.errors).flat().join('; ')
      : (e.response?.data?.message || e.message);
    alert('保存失败: ' + msg);
  }
  saving.value = false;
}

async function toggleStatus(p) {
  try {
    const { data } = await api.put(`/admin/plans/${p.id}/status`);
    p.is_active = data.data.is_active;
  } catch (e) {
    alert('操作失败: ' + (e.response?.data?.message || '请重试'));
  }
}

async function deletePlan(p) {
  if (!confirm(`确认删除套餐 "${p.name}"？`)) return;
  try {
    await api.delete(`/admin/plans/${p.id}`);
    plans.value = plans.value.filter(x => x.id !== p.id);
  } catch (e) {
    alert('删除失败: ' + (e.response?.data?.message || '请重试'));
  }
}
</script>

<template>
  <div>
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px">
      <h2>套餐管理</h2>
      <div style="display:flex;align-items:center;gap:12px">
        <span style="color:var(--text-muted);font-size:0.85rem">{{ plans.length }} 个套餐</span>
        <button class="btn primary" @click="openCreate">+ 添加套餐</button>
      </div>
    </div>

    <div v-if="loading" style="color:var(--text-muted)">加载中...</div>
    <div v-if="loadError" class="error-banner">{{ loadError }}</div>

    <table v-else class="data-table">
      <thead>
        <tr>
          <th>ID</th><th>名称</th><th>标识</th><th>级别</th>
          <th>月费</th><th>年费</th><th>权益</th><th>状态</th><th>操作</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="p in plans" :key="p.id">
          <td>{{ p.id }}</td>
          <td><strong>{{ p.name }}</strong></td>
          <td style="font-family:var(--mono);font-size:0.85rem">{{ p.slug }}</td>
          <td>
            <span class="badge" :class="p.tier === 'enterprise' ? 'info' : p.tier === 'pro' ? 'success' : ''">
              {{ tierLabels[p.tier] || p.tier }}
            </span>
          </td>
          <td>{{ p.price_monthly_cny ? '¥' + Number(p.price_monthly_cny).toFixed(0) : '—' }}</td>
          <td>{{ p.price_yearly_cny ? '¥' + Number(p.price_yearly_cny).toFixed(0) : '—' }}</td>
          <td style="font-size:0.8rem;max-width:220px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
            {{ Array.isArray(p.features) ? p.features.join('、') : (p.features || '—') }}
          </td>
          <td>
            <span class="badge" :class="p.is_active ? 'success' : 'error'">{{ p.is_active ? '启用' : '禁用' }}</span>
          </td>
          <td style="display:flex;gap:4px">
            <button class="btn small" @click="openEdit(p)" title="编辑">✎</button>
            <button class="btn small" @click="toggleStatus(p)" :title="p.is_active ? '禁用' : '启用'">
              {{ p.is_active ? '⊘' : '✓' }}
            </button>
            <button class="btn small danger" @click="deletePlan(p)" title="删除">✕</button>
          </td>
        </tr>
        <tr v-if="plans.length === 0">
          <td colspan="9" style="text-align:center;color:var(--text-muted);padding:32px">暂无套餐</td>
        </tr>
      </tbody>
    </table>

    <!-- Create/Edit Modal -->
    <div v-if="showModal" class="modal-overlay" @click.self="showModal = false">
      <div class="modal" style="max-width:520px">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px">
          <h3>{{ editing ? '编辑套餐' : '添加套餐' }}</h3>
          <button class="btn small" @click="showModal = false">✕</button>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
          <div class="form-group">
            <label>名称 *</label>
            <input v-model="form.name" class="form-input" placeholder="基础版" />
          </div>
          <div class="form-group">
            <label>标识 (slug) *</label>
            <input v-model="form.slug" class="form-input" placeholder="basic" />
          </div>
          <div class="form-group">
            <label>级别 *</label>
            <select v-model="form.tier" class="form-input">
              <option v-for="t in tiers" :key="t" :value="t">{{ tierLabels[t] }} ({{ t }})</option>
            </select>
          </div>
          <div class="form-group">
            <label>状态</label>
            <select v-model="form.is_active" class="form-input">
              <option :value="true">启用</option>
              <option :value="false">禁用</option>
            </select>
          </div>
          <div class="form-group">
            <label>月费 (¥)</label>
            <input v-model="form.price_monthly_cny" type="number" class="form-input" placeholder="39" />
          </div>
          <div class="form-group">
            <label>年费 (¥)</label>
            <input v-model="form.price_yearly_cny" type="number" class="form-input" placeholder="399" />
          </div>
          <div class="form-group">
            <label>排序</label>
            <input v-model.number="form.sort_order" type="number" class="form-input" min="0" />
          </div>
        </div>

        <div class="form-group" style="margin-top:12px">
          <label>权益列表 (一行一项)</label>
          <textarea v-model="form.features" class="form-input" rows="5" placeholder="720P 分辨率&#10;平台水印&#10;3 个项目"></textarea>
        </div>

        <div style="display:flex;justify-content:flex-end;gap:8px;margin-top:20px;padding-top:16px;border-top:1px solid var(--border)">
          <button class="btn" @click="showModal = false">取消</button>
          <button class="btn primary" @click="savePlan" :disabled="saving">
            {{ saving ? '保存中...' : '保存' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

