import { Link } from 'react-router-dom';

export default function NotFound() {
  return (
    <div className="dashboard">
      <header className="dash-header">
        <h1>AIStory</h1>
      </header>
      <main style={{textAlign:'center',paddingTop:80}}>
        <h2 style={{fontSize:'3rem',color:'var(--text-muted)',marginBottom:8}}>404</h2>
        <p style={{color:'var(--text-muted)',marginBottom:24}}>页面不存在</p>
        <Link to="/" className="btn primary">返回首页</Link>
      </main>
    </div>
  );
}
