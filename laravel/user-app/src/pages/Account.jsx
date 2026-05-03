import { useEffect, useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import api from '../api';

export default function Account() {
  const [user, setUser] = useState(null);
  const [membership, setMembership] = useState(null);
  const [plans, setPlans] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const navigate = useNavigate();

  useEffect(() => {
    const stored = localStorage.getItem('user');
    if (stored) setUser(JSON.parse(stored));
    Promise.all([
      api.get('/auth/me'),
      api.get('/membership'),
      api.get('/plans'),
    ]).then(([u, m, p]) => {
      setUser(u.data.data || u.data);
      setMembership(m.data.data || m.data);
      setPlans(p.data.data || []);
    }).catch(() => {
      setError('加载失败，请检查网络后刷新页面');
    }).finally(() => setLoading(false));
  }, []);

  const handleLogout = async () => {
    await api.post('/auth/logout').catch(() => {});
    localStorage.removeItem('token');
    localStorage.removeItem('user');
    navigate('/login');
  };

  if (loading) return <div className="dashboard"><main><p style={{color:'var(--text-muted)'}}>加载中...</p></main></div>;
  if (error) return <div className="dashboard"><main><p style={{color:'var(--error)',padding:'12px 0'}}>{error}</p><Link to="/dashboard" className="btn small">返回首页</Link></main></div>;

  const currentPlan = membership?.plan || { name: '免费版', tier: 'free' };
  const currentTier = currentPlan.tier || 'free';

  return (
    <div className="dashboard">
      <header className="dash-header">
        <h1>AIStory</h1>
        <div className="user-info">
          <Link to="/dashboard" className="btn small">返回</Link>
        </div>
      </header>
      <main>
        <h2 style={{marginBottom:24}}>账户设置</h2>

        <div className="card" style={{marginBottom:20}}>
          <h3>个人信息</h3>
          <div style={{marginTop:12,display:'flex',flexDirection:'column',gap:8}}>
            <div><label>昵称</label><p style={{color:'var(--text-h)'}}>{user?.name}</p></div>
            <div><label>邮箱</label><p style={{color:'var(--text-h)'}}>{user?.email}</p></div>
            <div><label>注册时间</label><p style={{color:'var(--text-muted)',fontSize:'0.9rem'}}>{user?.created_at?.substring(0, 10)}</p></div>
          </div>
        </div>

        <div className="card" style={{marginBottom:20}}>
          <h3>当前套餐</h3>
          <div style={{marginTop:12}}>
            <span className="plan-badge" style={{fontSize:'0.9rem',padding:'6px 16px'}}>{currentPlan.name}</span>
            {membership?.expires_at && (
              <p style={{marginTop:8,fontSize:'0.85rem',color:'var(--text-muted)'}}>
                到期时间: {membership.expires_at.substring(0, 10)}
              </p>
            )}
          </div>
        </div>

        <div className="card" style={{marginBottom:20}}>
          <h3>升级套餐</h3>
          <div className="works-grid" style={{marginTop:12}}>
            {plans.filter(p => p.tier !== currentTier).map(p => (
              <div key={p.id} className="work-card">
                <h3>{p.name}</h3>
                <p style={{color:'var(--primary)',fontSize:'1.1rem',fontWeight:600,marginTop:4}}>
                  ¥{Number(p.price_monthly || p.price_yearly/12 || 0).toFixed(0)}/月
                </p>
                {p.price_yearly && (
                  <p style={{fontSize:'0.8rem',color:'var(--text-muted)'}}>
                    ¥{Number(p.price_yearly).toFixed(0)}/年
                  </p>
                )}
                <button className="btn small secondary" style={{marginTop:8}} disabled>即将开放</button>
              </div>
            ))}
            {plans.filter(p => p.tier !== currentTier).length === 0 && (
              <p style={{color:'var(--text-muted)',fontSize:'0.9rem'}}>已是最高套餐</p>
            )}
          </div>
        </div>

        <div style={{marginTop:24,paddingTop:16,borderTop:'1px solid var(--border)'}}>
          <button onClick={handleLogout} className="btn secondary" style={{color:'var(--error)'}}>退出登录</button>
        </div>
      </main>
    </div>
  );
}
