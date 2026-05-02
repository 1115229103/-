import { useState, useEffect } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { useAuth } from '../contexts/AuthContext';
import client from '../api/client';

export default function Dashboard() {
  const { user, logout } = useAuth();
  const [works, setWorks] = useState([]);
  const [membership, setMembership] = useState(null);
  const navigate = useNavigate();

  useEffect(() => {
    client.get('/works').then(r => setWorks(r.data.data)).catch(() => {});
    client.get('/membership').then(r => setMembership(r.data.data)).catch(() => {});
  }, []);

  const handleLogout = async () => { await logout(); navigate('/'); };

  return (
    <div className="min-h-screen bg-slate-900 text-white">
      <nav className="flex items-center justify-between px-6 py-3 bg-white/5 border-b border-white/10">
        <Link to="/" className="text-xl font-bold bg-gradient-to-r from-purple-400 to-pink-400 bg-clip-text text-transparent">AIStory</Link>
        <div className="flex items-center gap-4">
          <Link to="/models" className="text-white/60 hover:text-white text-sm">My Models</Link>
          <Link to="/pricing" className="text-white/60 hover:text-white text-sm">Pricing</Link>
          <span className="text-white/40 text-sm">{user?.name} · {membership?.plan?.name || 'Free'}</span>
          <button onClick={handleLogout} className="text-sm text-red-400 hover:underline">Logout</button>
        </div>
      </nav>
      <main className="max-w-6xl mx-auto p-6">
        <div className="flex items-center justify-between mb-8">
          <h1 className="text-3xl font-bold">My Projects</h1>
          <Link to="/works/new" className="px-6 py-3 bg-purple-600 rounded-lg hover:bg-purple-700 transition font-semibold">+ New Project</Link>
        </div>
        {works.length === 0 ? (
          <div className="text-center py-20">
            <p className="text-white/40 text-lg mb-4">No projects yet</p>
            <Link to="/works/new" className="text-purple-400 hover:underline">Create your first video project</Link>
          </div>
        ) : (
          <div className="grid grid-cols-3 gap-6">
            {works.map(w => (
              <Link key={w.id} to={`/works/${w.id}`} className="p-6 rounded-xl bg-white/5 border border-white/10 hover:border-purple-500/50 transition">
                <h3 className="font-semibold mb-2 truncate">{w.title || 'Untitled'}</h3>
                <div className="flex items-center justify-between">
                  <span className={`text-xs px-2 py-1 rounded-full ${w.status === 'completed' ? 'bg-green-500/20 text-green-400' : w.status === 'failed' ? 'bg-red-500/20 text-red-400' : 'bg-yellow-500/20 text-yellow-400'}`}>{w.status || 'draft'}</span>
                  <span className="text-white/30 text-xs">{new Date(w.created_at).toLocaleDateString()}</span>
                </div>
              </Link>
            ))}
          </div>
        )}
      </main>
    </div>
  );
}
