import { useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import client from '../api/client';

export default function NewWork() {
  const [title, setTitle] = useState('');
  const [scriptContent, setScriptContent] = useState('');
  const [error, setError] = useState('');
  const [loading, setLoading] = useState(false);
  const navigate = useNavigate();

  const handleSubmit = async (e) => {
    e.preventDefault(); setError(''); setLoading(true);
    try {
      const r = await client.post('/works', { title, script_content: scriptContent });
      navigate(`/works/${r.data.data.id}`);
    } catch (err) { setError(err.response?.data?.error || 'Failed to create project'); }
    finally { setLoading(false); }
  };

  return (
    <div className="min-h-screen bg-slate-900 text-white">
      <nav className="flex items-center justify-between px-6 py-3 bg-white/5 border-b border-white/10">
        <Link to="/dashboard" className="text-xl font-bold bg-gradient-to-r from-purple-400 to-pink-400 bg-clip-text text-transparent">AIStory</Link>
        <Link to="/dashboard" className="text-white/60 hover:text-white text-sm">Cancel</Link>
      </nav>
      <main className="max-w-3xl mx-auto p-6">
        <h1 className="text-3xl font-bold mb-8">New Project</h1>
        {error && <div className="mb-4 p-3 bg-red-500/10 border border-red-500/30 rounded-lg text-red-400 text-sm">{error}</div>}
        <form onSubmit={handleSubmit} className="space-y-6">
          <div><label className="block text-sm text-white/60 mb-1">Project Title</label><input type="text" value={title} onChange={e => setTitle(e.target.value)} required placeholder="My First Video" className="w-full px-4 py-3 bg-white/10 border border-white/20 rounded-lg text-white focus:outline-none focus:border-purple-500" /></div>
          <div><label className="block text-sm text-white/60 mb-1">Script / Story Content</label><textarea value={scriptContent} onChange={e => setScriptContent(e.target.value)} required rows={12} placeholder="Paste your story, script, or narrative here..." className="w-full px-4 py-3 bg-white/10 border border-white/20 rounded-lg text-white focus:outline-none focus:border-purple-500 resize-y" /></div>
          <button type="submit" disabled={loading} className="w-full py-4 bg-purple-600 rounded-xl font-semibold hover:bg-purple-700 transition disabled:opacity-50">{loading ? 'Creating...' : 'Create Project & Start Pipeline'}</button>
        </form>
      </main>
    </div>
  );
}
