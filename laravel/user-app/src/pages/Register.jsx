import { useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import api from '../api';

export default function Register() {
  const [name, setName] = useState('');
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [errors, setErrors] = useState({});
  const [loading, setLoading] = useState(false);
  const navigate = useNavigate();

  const handleSubmit = async (e) => {
    e.preventDefault();
    setErrors({});
    setLoading(true);
    try {
      const { data } = await api.post('/auth/register', { name, email, password });
      localStorage.setItem('token', data.data.token);
      localStorage.setItem('user', JSON.stringify(data.data.user));
      navigate('/dashboard');
    } catch (err) {
      if (err.response?.data?.errors) {
        setErrors(err.response.data.errors);
      } else {
        setErrors({ general: err.response?.data?.message || err.response?.data?.error || '注册失败' });
      }
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="auth-page">
      <form onSubmit={handleSubmit} className="auth-form">
        <h2>注册 AIStory</h2>
        {errors.general && <div className="alert error">{errors.general}</div>}
        <label htmlFor="reg-name">昵称</label>
        <input id="reg-name" type="text" value={name} onChange={(e) => setName(e.target.value)} required />
        {errors.name && <span className="field-error">{errors.name[0]}</span>}
        <label htmlFor="reg-email">邮箱</label>
        <input id="reg-email" type="email" value={email} onChange={(e) => setEmail(e.target.value)} required />
        {errors.email && <span className="field-error">{errors.email[0]}</span>}
        <label htmlFor="reg-password">密码</label>
        <input id="reg-password" type="password" value={password} onChange={(e) => setPassword(e.target.value)} required minLength={8} />
        {errors.password && <span className="field-error">{errors.password[0]}</span>}
        <button type="submit" disabled={loading} className="btn primary full">
          {loading ? '注册中...' : '注册'}
        </button>
        <p className="link">已有账号？<Link to="/login">立即登录</Link></p>
      </form>
    </div>
  );
}
