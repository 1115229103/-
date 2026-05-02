<script setup>
import { ref, onMounted } from 'vue';
import api from '../api.js';

const watermark = ref(null);
const system = ref({});
const loading = ref(true);

onMounted(async () => {
  try {
    const [w, s] = await Promise.all([
      api.get('/admin/watermark-config'),
      api.get('/admin/system/settings'),
    ]);
    watermark.value = w.data.data || w.data;
    system.value = s.data.data || s.data;
  } catch {}
  loading.value = false;
});
</script>

<template>
  <div>
    <h2 style="margin-bottom:20px">系统设置</h2>

    <div v-if="loading" style="color:var(--text-muted)">加载中...</div>

    <div v-else>
      <div class="card" style="margin-bottom:20px">
        <h3>水印配置</h3>
        <div v-if="watermark" style="margin-top:12px;display:flex;flex-direction:column;gap:8px">
          <div style="display:flex;gap:16px;flex-wrap:wrap">
            <div><label>类型</label><p style="color:var(--text-h)">{{ watermark.type === 'text' ? '文字水印' : watermark.type === 'image' ? '图片水印' : watermark.type || '未配置' }}</p></div>
            <div><label>位置</label><p style="color:var(--text-h)">{{ watermark.position || '—' }}</p></div>
            <div><label>透明度</label><p style="color:var(--text-h)">{{ watermark.opacity ?? '—' }}</p></div>
            <div><label>状态</label><span class="badge" :class="watermark.enabled ? 'success' : 'error'" style="margin-top:4px">{{ watermark.enabled ? '启用' : '禁用' }}</span></div>
          </div>
          <div v-if="watermark.text"><label>文字内容</label><p style="color:var(--text-h)">{{ watermark.text }}</p></div>
        </div>
        <p v-else style="color:var(--text-muted);margin-top:12px">未配置水印</p>
      </div>

      <div class="card">
        <h3>系统信息</h3>
        <div v-if="system && Object.keys(system).length" style="margin-top:12px;display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:12px">
          <div v-for="(val, key) in system" :key="key">
            <label>{{ key }}</label>
            <p style="color:var(--text-h);font-size:0.85rem;word-break:break-all">{{ typeof val === 'object' ? JSON.stringify(val) : val }}</p>
          </div>
        </div>
        <p v-else style="color:var(--text-muted);margin-top:12px">暂无系统设置</p>
      </div>
    </div>
  </div>
</template>
