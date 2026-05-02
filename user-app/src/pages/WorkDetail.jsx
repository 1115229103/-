import { useState, useEffect } from 'react';
import { Link, useParams } from 'react-router-dom';
import client from '../api/client';

export default function WorkDetail() {
  const { id } = useParams();
  const [work, setWork] = useState(null);
  const [loading, setLoading] = useState(true);

  const load = () => { client.get(`/works/${id}`).then(r => setWork(r.data.data)).catch(() => {}).finally(() => setLoading(false)); };

  useEffect(() => { load(); }, [id]);

  const handleStartPipeline = async () => {
    await client.post(`/works/${id}/pipeline/start`);
    load();
  };

  if (loading) return <div className="min-h-screen bg-slate-900 text-white flex items-center justify-center">Loading...</div>;
  if (!work) return <div className="min-h-screen bg-slate-900 text-white flex items-center justify-center">Project not found. <Link to="/dashboard" className="text-purple-400 ml-2">Back</Link></div>;

  return (
    <div className="min-h-screen bg-slate-900 text-white">
      <nav className="flex items-center justify-between px-6 py-3 bg-white/5 border-b border-white/10">
        <Link to="/dashboard" className="text-xl font-bold bg-gradient-to-r from-purple-400 to-pink-400 bg-clip-text text-transparent">AIStory</Link>
        <Link to="/dashboard" className="text-white/60 hover:text-white text-sm">Back to Dashboard</Link>
      </nav>
      <main className="max-w-4xl mx-auto p-6">
        <div className="flex items-center justify-between mb-6">
          <div>
            <h1 className="text-3xl font-bold">{work.title || 'Untitled'}</h1>
            <p className="text-white/40 text-sm mt-1">Created {new Date(work.created_at).toLocaleString()}</p>
          </div>
          <span className={`px-4 py-2 rounded-lg text-sm font-semibold ${work.status === 'completed' ? 'bg-green-500/20 text-green-400' : work.status === 'failed' ? 'bg-red-500/20 text-red-400' : 'bg-yellow-500/20 text-yellow-400'}`}>{work.status || 'draft'}</span>
        </div>

        {work.status === 'draft' && (
          <button onClick={handleStartPipeline} className="w-full py-4 mb-8 bg-gradient-to-r from-purple-500 to-pink-500 rounded-xl font-semibold hover:opacity-90 transition text-lg">Start AI Pipeline</button>
        )}

        {work.pipeline_progress && Object.keys(work.pipeline_progress).length > 0 && (
          <div className="mb-8 p-6 bg-white/5 rounded-xl border border-white/10">
            <h2 className="text-lg font-semibold mb-4">Pipeline Progress</h2>
            <div className="space-y-3">
              {Object.entries(work.pipeline_progress).map(([stage, info]) => (
                <div key={stage} className="flex items-center gap-3">
                  <span className={`w-3 h-3 rounded-full ${info.status === 'completed' ? 'bg-green-400' : info.status === 'running' ? 'bg-yellow-400 animate-pulse' : info.status === 'failed' ? 'bg-red-400' : 'bg-white/20'}`}></span>
                  <span className="text-sm text-white/80">{stage}</span>
                  <span className="text-xs text-white/40 ml-auto">{info.status}</span>
                </div>
              ))}
            </div>
          </div>
        )}

        <div className="p-6 bg-white/5 rounded-xl border border-white/10">
          <h2 className="text-lg font-semibold mb-3">Script Content</h2>
          <pre className="text-white/60 text-sm whitespace-pre-wrap font-sans">{work.script?.content || work.script_content || 'No content'}</pre>
        </div>

        {work.error_message && (
          <div className="mt-6 p-4 bg-red-500/10 border border-red-500/30 rounded-xl text-red-400 text-sm">{work.error_message}</div>
        )}
      </main>
    </div>
  );
}
