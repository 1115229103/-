<script setup>
import { ref, onMounted } from 'vue';
import api from '../api.js';

const watermark = ref(null);
const system = ref({});
const loading = ref(true);
const saving = ref(false);
const loadError = ref('');
const saveMsg = ref('');

const EDITABLE_KEYS = {
  app_name: { label: '应用名称', type: 'string' },
  app_description: { label: '应用描述', type: 'string' },
  footer_text: { label: '页脚文本', type: 'string' },
  max_upload_size_mb: { label: '最大上传(MB)', type: 'integer' },
  default_video_resolution: { label: '默认视频分辨率', type: 'string' },
  maintenance_mode: { label: '维护模式', type: 'boolean' },
  registration_enabled: { label: '开放注册', type: 'boolean' },
  guest_browse_enabled: { label: '游客浏览', type: 'boolean' },
  site_keywords: { label: '站点关键词', type: 'string' },
  site_icp: { label: 'ICP备案号', type: 'string' },
  contact_email: { label: '联系邮箱', type: 'string' },
  verify_code_length: { label: '验证码长度', type: 'integer' },
  verify_code_ttl: { label: '验证码有效期(秒)', type: 'integer' },
  login_attempt_limit: { label: '登录尝试限制', type: 'integer' },
};

onMounted(async () => {
  try {
    const [w, s] = await Promise.all([
      api.get('/admin/watermark-config'),
      api.get('/admin/system/settings'),
    ]);
    watermark.value = w.data.data || w.data;
    system.value = s.data.data || s.data;
  } catch { loadError.value = '加载失败，请检查网络后重试'; }
  loading.value = false;
});

const saveSettings = async () => {
  saving.value = true; saveMsg.value = '';
  try {
    const payload = {};
    for (const key of Object.keys(EDITABLE_KEYS)) {
      if (system.value[key] !== undefined && system.value[key] !== '') {
        const type = EDITABLE_KEYS[key].type;
        payload[key] = type === 'integer' ? parseInt(system.value[key]) || 0
                     : type === 'boolean' ? !!system.value[key]
                     : system.value[key];
      }
    }
    await api.put('/admin/system/settings', payload);
    saveMsg.value = '保存成功';
  } catch (e) {
    saveMsg.value = '保存失败: ' + (e.response?.data?.errors ? JSON.stringify(e.response.data.errors) : (e.response?.data?.message || e.message));
  }
  saving.value = false;
};
</script>

<template>
  <div>
    <h2 style="margin-bottom:20px">系统设置</h2>

    <div v-if="loading" style="color:var(--text-muted)">加载中...</div>
    <div v-if="loadError" class="error-banner">{{ loadError }}</div>

    <div v-else>
      <div class="card" style="margin-bottom:20px">
        <h3>水印配置</h3>
        <div v-if="watermark" style="margin-top:12px;display:flex;flex-direction:column;gap:8px">
          <div style="display:flex;gap:16px;flex-wrap:wrap">
            <div><label>类型</label><p style="color:var(--text-h)">{{ watermark.type === 'text' ? '文字水印' : watermark.type === 'image' ? '图片水印' : watermark.type || '未配置' }}</p></div>
            <div><label>位置</label><p style="color:var(--text-h)">{{ watermark.position || '—' }}</p></div>
            <div><label>透明度</label><p style="color:var(--text-h)">{{ watermark.opacity ?? '—' }}</p></div>
            <div><label>盲水印</label><span class="badge" :class="watermark.blind_enabled ? 'success' : 'error'" style="margin-top:4px">{{ watermark.blind_enabled ? '启用' : '禁用' }}</span></div>
          </div>
          <div v-if="watermark.text"><label>文字内容</label><p style="color:var(--text-h)">{{ watermark.text }}</p></div>
        </div>
        <p v-else style="color:var(--text-muted);margin-top:12px">未配置水印</p>
      </div>

      <div class="card">
        <h3>系统信息</h3>
        <div v-if="Object.keys(EDITABLE_KEYS).length" style="margin-top:12px;display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:12px">
          <div v-for="(meta, key) in EDITABLE_KEYS" :key="key" class="form-group">
            <label :for="'setting-' + key">{{ meta.label }} <span style="font-size:0.7rem;color:var(--text-muted)">({{ key }})</span></label>
            <input
              v-if="meta.type === 'boolean'"
              :id="'setting-' + key"
              type="checkbox"
              :checked="system[key] === '1' || system[key] === true"
              @change="system[key] = $event.target.checked ? '1' : '0'"
              style="width:auto;margin-top:4px"
            />
            <input
              v-else
              :id="'setting-' + key"
              v-model="system[key]"
              class="form-input"
              :type="meta.type === 'integer' ? 'number' : 'text'"
              style="margin-top:2px"
            />
          </div>
        </div>
        <p v-else style="color:var(--text-muted);margin-top:12px">暂无系统设置</p>

        <div style="margin-top:16px;display:flex;align-items:center;gap:12px">
          <button class="btn primary" @click="saveSettings" :disabled="saving">
            {{ saving ? '保存中...' : '保存设置' }}
          </button>
          <span v-if="saveMsg" style="font-size:0.85rem" :style="{color: saveMsg.includes('失败') ? 'var(--error)' : 'var(--success)'}">{{ saveMsg }}</span>
        </div>
      </div>
    </div>
  </div>
</template>
