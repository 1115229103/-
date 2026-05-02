<script setup>
import { ref, onMounted } from 'vue';
import api from '../api.js';

const models = ref([]);
const loading = ref(true);
const selectedCat = ref('');

const categories = [
  { value: '', label: '全部' },
  { value: 'llm', label: '大语言模型' },
  { value: 'image_gen', label: '图像生成' },
  { value: 'consistency', label: '角色一致性' },
  { value: 'image_enhance', label: '图像增强' },
  { value: 'image2video', label: '图生视频' },
  { value: 'video_enhance', label: '视频增强' },
  { value: 'tts', label: '语音合成' },
  { value: 'music', label: '音乐生成' },
  { value: 'asr', label: '语音识别' },
  { value: 'moderation', label: '内容审核' },
];

async function load() {
  loading.value = true;
  try {
    const params = selectedCat.value ? `?category=${selectedCat.value}` : '';
    const { data } = await api.get(`/admin/models${params}`);
    models.value = data.data || [];
  } catch { models.value = []; }
  loading.value = false;
}

onMounted(load);

function catFilter(cat) { selectedCat.value = cat; load(); }
</script>

<template>
  <div>
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px">
      <h2>模型注册管理</h2>
      <span style="color:var(--text-muted);font-size:0.85rem">{{ models.length }} 个模型</span>
    </div>

    <div style="display:flex;flex-wrap:wrap;gap:6px;margin-bottom:20px">
      <button
        v-for="c in categories" :key="c.value"
        class="btn small" :class="selectedCat === c.value ? 'primary' : ''"
        @click="catFilter(c.value)"
      >{{ c.label }}</button>
    </div>

    <div v-if="loading" style="color:var(--text-muted)">加载中...</div>
    <table v-else class="data-table">
      <thead>
        <tr>
          <th>ID</th>
          <th>类别</th>
          <th>模型名称</th>
          <th>显示名称</th>
          <th>提供商</th>
          <th>API类型</th>
          <th>状态</th>
          <th>排序</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="m in models" :key="m.id">
          <td>{{ m.id }}</td>
          <td><span class="badge info">{{ m.category }}</span></td>
          <td style="font-family:var(--mono);font-size:0.85rem">{{ m.model_name }}</td>
          <td>{{ m.display_name }}</td>
          <td>{{ m.provider }}</td>
          <td style="font-family:var(--mono);font-size:0.8rem">{{ m.api_type }}</td>
          <td>
            <span class="badge" :class="m.status === 'active' ? 'success' : 'error'">
              {{ m.status === 'active' ? '启用' : '禁用' }}
            </span>
          </td>
          <td>{{ m.sort_order }}</td>
        </tr>
        <tr v-if="models.length === 0">
          <td colspan="8" style="text-align:center;color:var(--text-muted);padding:32px">暂无数据</td>
        </tr>
      </tbody>
    </table>
  </div>
</template>
