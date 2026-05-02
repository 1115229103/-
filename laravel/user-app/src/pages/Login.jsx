import { useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import api from '../api';

export default function Login() {
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [error, setError] = useState('');
  const [loading, setLoading] = useState(false);
  const navigate = useNavigate();

  const handleSubmit = async (e) => {
    e.preventDefault();
    setError('');
    setLoading(true);
    try {
      const { data } = await api.post('/auth/login', { email, password });
      localStorage.setItem('token', data.data.token);
      localStorage.setItem('user', JSON.stringify(data.data.user));
      navigate('/dashboard');
    } catch (err) {
      setError(err.response?.data?.error || '登录失败');
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="auth-page">
      <form onSubmit={handleSubmit} className="auth-form">
        <h2>登录 AIStory</h2>
        {error && <div className="alert error">{error}</div>}
        <label>邮箱</label>
        <input type="email" value={email} onChange={(e) => setEmail(e.target.value)} required />
        <label>密码</label>
        <input type="password" value={password} onChange={(e) => setPassword(e.target.value)} required minLength={8} />
        <button type="submit" disabled={loading} className="btn primary full">
          {loading ? '登录中...' : '登录'}
        </button>
        <p className="link">还没有账号？<Link to="/register">立即注册</Link></p>
      </form>
    </div>
  );
}
