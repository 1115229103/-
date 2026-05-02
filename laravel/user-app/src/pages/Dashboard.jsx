import { useEffect, useState } from 'react';
import { useNavigate, Link } from 'react-router-dom';
import api from '../api';

export default function Dashboard() {
  const [user, setUser] = useState(null);
  const [works, setWorks] = useState([]);
  const [membership, setMembership] = useState(null);
  const navigate = useNavigate();

  useEffect(() => {
    const stored = localStorage.getItem('user');
    if (stored) setUser(JSON.parse(stored));

    api.get('/auth/me').then(({ data }) => setUser(data.data)).catch(() => {});
    api.get('/works').then(({ data }) => setWorks(data.data?.data || [])).catch(() => {});
    api.get('/membership').then(({ data }) => setMembership(data.data)).catch(() => {});
  }, []);

  const handleLogout = async () => {
    await api.post('/auth/logout').catch(() => {});
    localStorage.removeItem('token');
    localStorage.removeItem('user');
    navigate('/login');
  };

  return (
    <div className="dashboard">
      <header className="dash-header">
        <h1>AIStory</h1>
        <div className="user-info">
          {membership && <span className="plan-badge">{membership.plan?.name || '免费版'}</span>}
          <Link to="/account" style={{color:'inherit',textDecoration:'none'}}>{user?.name}</Link>
          <Link to="/models-config" className="btn small">模型配置</Link>
          <button onClick={handleLogout} className="btn small">退出</button>
        </div>
      </header>
      <main>
        <section className="works-section">
          <div className="section-header">
            <h2>我的作品</h2>
            <Link to="/works/new" className="btn primary">+ 新建作品</Link>
          </div>
          {works.length === 0 ? (
            <div className="empty-state">
              <p>还没有作品，点击上方按钮开始创作</p>
            </div>
          ) : (
            <div className="works-grid">
              {works.map((w) => (
                <Link to={`/works/${w.id}`} key={w.id} className="work-card" style={{textDecoration:'none',color:'inherit'}}>
                  <h3>{w.title}</h3>
                  <span className={`status ${w.status}`}>{w.status}</span>
                  <p>{w.style}</p>
                </Link>
              ))}
            </div>
          )}
        </section>
      </main>
    </div>
  );
}
