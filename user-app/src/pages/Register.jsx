import { useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { useAuth } from '../contexts/AuthContext';

export default function Register() {
  const [name, setName] = useState('');
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [error, setError] = useState('');
  const { register } = useAuth();
  const navigate = useNavigate();

  const handleSubmit = async (e) => {
    e.preventDefault(); setError('');
    try { await register(name, email, password); navigate('/dashboard'); } catch (err) { setError(err.response?.data?.error || 'Registration failed'); }
  };

  return (
    <div className="min-h-screen bg-slate-900 flex items-center justify-center px-4">
      <div className="w-full max-w-md p-8 bg-white/5 rounded-2xl border border-white/10">
        <h1 className="text-3xl font-bold text-white text-center mb-8">Create Account</h1>
        {error && <div className="mb-4 p-3 bg-red-500/10 border border-red-500/30 rounded-lg text-red-400 text-sm">{error}</div>}
        <form onSubmit={handleSubmit} className="space-y-5">
          <div><label className="block text-sm text-white/60 mb-1">Name</label><input type="text" value={name} onChange={e => setName(e.target.value)} required className="w-full px-4 py-3 bg-white/10 border border-white/20 rounded-lg text-white focus:outline-none focus:border-purple-500" /></div>
          <div><label className="block text-sm text-white/60 mb-1">Email</label><input type="email" value={email} onChange={e => setEmail(e.target.value)} required className="w-full px-4 py-3 bg-white/10 border border-white/20 rounded-lg text-white focus:outline-none focus:border-purple-500" /></div>
          <div><label className="block text-sm text-white/60 mb-1">Password</label><input type="password" value={password} onChange={e => setPassword(e.target.value)} required minLength={8} className="w-full px-4 py-3 bg-white/10 border border-white/20 rounded-lg text-white focus:outline-none focus:border-purple-500" /></div>
          <button type="submit" className="w-full py-3 bg-purple-600 rounded-lg font-semibold hover:bg-purple-700 transition">Create Account</button>
        </form>
        <p className="text-center text-white/40 text-sm mt-6">Already have an account? <Link to="/login" className="text-purple-400 hover:underline">Log In</Link></p>
      </div>
    </div>
  );
}
