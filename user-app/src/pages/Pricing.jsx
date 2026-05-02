import { useState, useEffect } from 'react';
import { Link } from 'react-router-dom';
import client from '../api/client';
import { useAuth } from '../contexts/AuthContext';

export default function Pricing() {
  const [plans, setPlans] = useState([]);
  const { user } = useAuth();

  useEffect(() => { client.get('/plans').then(r => setPlans(r.data.data)).catch(() => {}); }, []);

  const handleSubscribe = async (planId, cycle) => {
    try { const r = await client.post('/orders', { plan_id: planId, billing_cycle: cycle, payment_method: 'wechat' }); alert(`Order created: ${r.data.data.order_no}`); } catch (err) { alert(err.response?.data?.error || 'Failed'); }
  };

  return (
    <div className="min-h-screen bg-slate-900 text-white">
      <nav className="flex items-center justify-between px-6 py-3 bg-white/5 border-b border-white/10">
        <Link to={user ? '/dashboard' : '/'} className="text-xl font-bold bg-gradient-to-r from-purple-400 to-pink-400 bg-clip-text text-transparent">AIStory</Link>
        <Link to={user ? '/dashboard' : '/'} className="text-white/60 hover:text-white text-sm">Back</Link>
      </nav>
      <main className="max-w-6xl mx-auto p-6 text-center">
        <h1 className="text-4xl font-bold mb-4">Choose Your Plan</h1>
        <p className="text-white/40 mb-12">Bring your own API keys. Pay for platform features, not AI usage.</p>
        <div className="grid grid-cols-4 gap-6">
          {plans.map(p => (
            <div key={p.id} className={`p-6 rounded-xl border ${p.tier === 'pro' ? 'border-purple-500 bg-purple-500/5' : 'bg-white/5 border-white/10'}`}>
              <h3 className="text-xl font-bold mb-1">{p.name}</h3>
              {p.price_monthly_cny > 0 ? (
                <div className="mb-4"><span className="text-3xl font-bold">¥{p.price_monthly_cny}</span><span className="text-white/40 text-sm">/mo</span></div>
              ) : <div className="mb-4"><span className="text-3xl font-bold">Free</span></div>}
              {p.price_yearly_cny > 0 && <p className="text-white/40 text-xs mb-4">¥{p.price_yearly_cny}/year (save 15%)</p>}
              {p.features && typeof p.features === 'object' && (
                <ul className="text-left text-sm text-white/60 space-y-2 mb-6">
                  {Object.entries(p.features).slice(0, 5).map(([k, v]) => <li key={k} className="flex items-center gap-2"><span className="text-green-400">✓</span> {k}: {v}</li>)}
                </ul>
              )}
              {user && p.tier !== 'free' && (
                <div className="space-y-2">
                  <button onClick={() => handleSubscribe(p.id, 'monthly')} className="w-full py-2 bg-purple-600 rounded-lg text-sm hover:bg-purple-700">Monthly</button>
                  <button onClick={() => handleSubscribe(p.id, 'yearly')} className="w-full py-2 border border-purple-500/50 rounded-lg text-sm hover:bg-purple-500/10">Yearly</button>
                </div>
              )}
            </div>
          ))}
        </div>
      </main>
    </div>
  );
}
