<script setup>
import { ref, onMounted } from 'vue';
import api from '../api.js';

const stages = ref([]);
const loading = ref(true);

onMounted(async () => {
  try {
    const { data } = await api.get('/admin/pipeline-stages');
    stages.value = data.data || [];
  } catch { stages.value = []; }
  loading.value = false;
});

const catLabels = {
  llm: '大语言模型', image_gen: '图像生成', consistency: '角色一致性',
  image_enhance: '图像增强', image2video: '图生视频', video_enhance: '视频增强',
  tts: '语音合成', music: '音乐生成', asr: '语音识别', moderation: '内容审核',
};
</script>

<template>
  <div>
    <h2 style="margin-bottom:20px">环节配置</h2>
    <div v-if="loading" style="color:var(--text-muted)">加载中...</div>
    <table v-else class="data-table">
      <thead>
        <tr><th>#</th><th>环节标识</th><th>名称</th><th>类别</th><th>必选</th><th>启用</th><th>描述</th></tr>
      </thead>
      <tbody>
        <tr v-for="(s, i) in stages" :key="s.id || s.stage">
          <td>{{ s.sort_order ?? i + 1 }}</td>
          <td style="font-family:var(--mono);font-size:0.8rem">{{ s.stage }}</td>
          <td>{{ s.name }}</td>
          <td><span class="badge info">{{ catLabels[s.category] || s.category }}</span></td>
          <td><span class="badge" :class="s.is_required ? 'warning' : ''" :style="!s.is_required ? 'background:rgba(156,163,175,0.1);color:#9ca3af' : ''">{{ s.is_required ? '必选' : '可选' }}</span></td>
          <td><span class="badge" :class="s.is_enabled ? 'success' : 'error'">{{ s.is_enabled ? '启用' : '禁用' }}</span></td>
          <td style="font-size:0.85rem;color:var(--text-muted)">{{ s.description }}</td>
        </tr>
      </tbody>
    </table>
  </div>
</template>
