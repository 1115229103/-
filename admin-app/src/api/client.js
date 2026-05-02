import axios from 'axios';

const client = axios.create({
  baseURL: 'http://127.0.0.1:8000/api/v1/admin',
  headers: { Accept: 'application/json', 'Content-Type': 'application/json' },
});

client.interceptors.request.use((config) => {
  const token = localStorage.getItem('admin_token');
  if (token) config.headers.Authorization = `Bearer ${token}`;
  return config;
});

client.interceptors.response.use(
  (r) => r,
  (err) => {
    if (err.response?.status === 401) { localStorage.removeItem('admin_token'); window.location.href = '/login'; }
    return Promise.reject(err);
  }
);

export default client;
