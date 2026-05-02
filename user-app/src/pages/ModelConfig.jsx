import { useState, useEffect } from 'react';
import { Link } from 'react-router-dom';
import client from '../api/client';

export default function ModelConfig() {
  const [configs, setConfigs] = useState([]);
  const [models, setModels] = useState([]);
  const [categories, setCategories] = useState([]);
  const [showForm, setShowForm] = useState(false);
  const [form, setForm] = useState({ model_registry_id: '', api_key: '', params: '' });
  const [message, setMessage] = useState('');
  const [filterCat, setFilterCat] = useState('');

  const loadData = () => {
    client.get('/user/model-configs').then(r => setConfigs(r.data.data)).catch(() => {});
    client.get('/models').then(r => setModels(r.data.data)).catch(() => {});
    client.get('/models/categories').then(r => setCategories(r.data.data || [])).catch(() => {});
  };

  useEffect(() => { loadData(); }, []);

  const handleAdd = async (e) => {
    e.preventDefault();
    try {
      const payload = { model_registry_id: parseInt(form.model_registry_id), api_key: form.api_key };
      if (form.params) { try { payload.custom_params = JSON.parse(form.params); } catch { setMessage('Invalid JSON params'); return; } }
      await client.post('/user/model-configs', payload);
      setMessage('Model added!'); setShowForm(false); setForm({ model_registry_id: '', api_key: '', params: '' }); loadData();
    } catch (err) { setMessage(err.response?.data?.error || 'Failed to add'); }
  };

  const handleDelete = async (id) => { await client.delete(`/user/model-configs/${id}`); loadData(); };
  const handleVerify = async (id) => { const r = await client.post(`/user/model-configs/${id}/verify`); setMessage(r.data?.data?.valid ? 'Key verified!' : 'Key invalid'); };

  const filtered = filterCat ? models.filter(m => m.category === filterCat) : models;

  return (
    <div className="min-h-screen bg-slate-900 text-white">
      <nav className="flex items-center justify-between px-6 py-3 bg-white/5 border-b border-white/10">
        <Link to="/dashboard" className="text-xl font-bold bg-gradient-to-r from-purple-400 to-pink-400 bg-clip-text text-transparent">AIStory</Link>
        <Link to="/dashboard" className="text-white/60 hover:text-white text-sm">Back to Dashboard</Link>
      </nav>
      <main className="max-w-5xl mx-auto p-6">
        <div className="flex items-center justify-between mb-6">
          <h1 className="text-2xl font-bold">My Model Configurations</h1>
          <button onClick={() => setShowForm(!showForm)} className="px-4 py-2 bg-purple-600 rounded-lg hover:bg-purple-700 transition text-sm">{showForm ? 'Cancel' : '+ Add Model'}</button>
        </div>
        {message && <div className="mb-4 p-3 bg-purple-500/10 border border-purple-500/30 rounded-lg text-purple-300 text-sm">{message}</div>}
        {showForm && (
          <form onSubmit={handleAdd} className="mb-8 p-6 bg-white/5 rounded-xl border border-white/10 space-y-4">
            <div><label className="block text-sm text-white/60 mb-1">Filter by Category</label><select value={filterCat} onChange={e => { setFilterCat(e.target.value); setForm(f => ({...f, model_registry_id: ''})); }} className="w-full px-4 py-2 bg-white/10 border border-white/20 rounded-lg text-white"><option value="">All Categories</option>{categories.map(c => <option key={c.code} value={c.code}>{c.name}</option>)}</select></div>
            <div><label className="block text-sm text-white/60 mb-1">Model</label><select value={form.model_registry_id} onChange={e => setForm({...form, model_registry_id: e.target.value})} required className="w-full px-4 py-2 bg-white/10 border border-white/20 rounded-lg text-white"><option value="">Select model...</option>{filtered.map(m => <option key={m.id} value={m.id}>{m.display_name} ({m.provider})</option>)}</select></div>
            <div><label className="block text-sm text-white/60 mb-1">API Key</label><input type="password" value={form.api_key} onChange={e => setForm({...form, api_key: e.target.value})} required className="w-full px-4 py-2 bg-white/10 border border-white/20 rounded-lg text-white" /></div>
            <div><label className="block text-sm text-white/60 mb-1">Custom Params (JSON, optional)</label><input type="text" value={form.params} onChange={e => setForm({...form, params: e.target.value})} placeholder='{"temperature": 0.7}' className="w-full px-4 py-2 bg-white/10 border border-white/20 rounded-lg text-white" /></div>
            <button type="submit" className="px-6 py-2 bg-purple-600 rounded-lg hover:bg-purple-700 transition text-sm">Add Model</button>
          </form>
        )}
        {configs.length === 0 ? <p className="text-white/40">No models configured yet. Add your first model to start creating.</p> : (
          <div className="space-y-3">
            {configs.map(c => (
              <div key={c.id} className="p-4 rounded-xl bg-white/5 border border-white/10 flex items-center justify-between">
                <div>
                  <span className="font-semibold">{c.model?.display_name || 'Model #'+c.model_registry_id}</span>
                  <span className="ml-3 text-xs px-2 py-1 rounded-full bg-white/10 text-white/60">{c.category || 'unknown'}</span>
                  <span className="ml-2 text-white/40 text-sm">Key: {c.masked_key || '***'}</span>
                </div>
                <div className="flex gap-2">
                  <button onClick={() => handleVerify(c.id)} className="px-3 py-1 text-xs border border-green-500/50 text-green-400 rounded-lg hover:bg-green-500/10">Verify</button>
                  <button onClick={() => handleDelete(c.id)} className="px-3 py-1 text-xs border border-red-500/50 text-red-400 rounded-lg hover:bg-red-500/10">Delete</button>
                </div>
              </div>
            ))}
          </div>
        )}
      </main>
    </div>
  );
}
