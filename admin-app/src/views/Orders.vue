<template>
  <div><h2 class="pg-t">Orders ({{ items.length }})</h2>
    <table class="tbl"><thead><tr><th>ID</th><th>Order No</th><th>User</th><th>Amount</th><th>Method</th><th>Status</th><th>Date</th></tr></thead><tbody><tr v-for="o in items" :key="o.id"><td>{{ o.id }}</td><td>{{ o.order_no }}</td><td>{{ o.user?.name || '#'+o.user_id }}</td><td>¥{{ o.amount_cny }}</td><td>{{ o.payment_method }}</td><td>{{ o.status }}</td><td>{{ new Date(o.created_at).toLocaleDateString() }}</td></tr></tbody></table></div>
</template>
<script setup>
import { ref, onMounted } from 'vue'; import client from '../api/client';
const items = ref([]);
onMounted(async () => { try { const r = await client.get('/orders'); items.value = r.data.data; } catch {} });
</script>
<style scoped>.pg-t { font-size:1.75rem; font-weight:700; margin-bottom:1.5rem; } .tbl { width:100%; border-collapse:collapse; font-size:.875rem; } .tbl th { text-align:left; padding:.75rem; border-bottom:1px solid rgba(255,255,255,.1); color:rgba(255,255,255,.4); } .tbl td { padding:.75rem; border-bottom:1px solid rgba(255,255,255,.05); }</style>
