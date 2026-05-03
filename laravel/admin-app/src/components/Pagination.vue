<script setup>
defineProps({
  currentPage: { type: Number, required: true },
  lastPage: { type: Number, required: true },
  total: { type: Number, required: true },
  perPage: { type: Number, required: true },
  loading: { type: Boolean, default: false },
});

const emit = defineEmits(['page-change']);

function pages(current, last) {
  const pgs = [];
  const start = Math.max(1, current - 2);
  const end = Math.min(last, current + 2);
  for (let i = start; i <= end; i++) pgs.push(i);
  if (start > 1) { pgs.unshift(1); if (start > 2) pgs.splice(1, 0, '...'); }
  if (end < last) { if (end < last - 1) pgs.push('...'); pgs.push(last); }
  return pgs;
}
</script>

<template>
  <div class="pagination-bar" v-if="lastPage > 0">
    <span class="pagination-info">共 {{ total }} 条，第 {{ currentPage }}/{{ lastPage }} 页</span>
    <div class="pagination-btns">
      <button
        class="btn small"
        :disabled="currentPage <= 1 || loading"
        @click="emit('page-change', currentPage - 1)"
      >&laquo; 上一页</button>
      <template v-for="p in pages(currentPage, lastPage)" :key="p">
        <span v-if="p === '...'" class="pagination-ellipsis">...</span>
        <button
          v-else
          class="btn small"
          :class="{ primary: p === currentPage }"
          :disabled="loading"
          @click="emit('page-change', p)"
        >{{ p }}</button>
      </template>
      <button
        class="btn small"
        :disabled="currentPage >= lastPage || loading"
        @click="emit('page-change', currentPage + 1)"
      >下一页 &raquo;</button>
    </div>
  </div>
</template>

<style scoped>
.pagination-bar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 10px;
  margin-top: 16px;
  padding-top: 12px;
  border-top: 1px solid var(--border);
}
.pagination-info {
  font-size: 0.8rem;
  color: var(--text-muted);
}
.pagination-btns {
  display: flex;
  align-items: center;
  gap: 4px;
}
.pagination-ellipsis {
  padding: 0 4px;
  color: var(--text-muted);
  font-size: 0.8rem;
}
</style>
