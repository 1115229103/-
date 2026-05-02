import { Link } from 'react-router-dom';
import { useAuth } from '../contexts/AuthContext';

export default function Home() {
  const { user } = useAuth();
  return (
    <div className="min-h-screen bg-gradient-to-br from-slate-900 via-purple-900 to-slate-900 text-white">
      <nav className="flex items-center justify-between px-8 py-4">
        <h1 className="text-2xl font-bold bg-gradient-to-r from-purple-400 to-pink-400 bg-clip-text text-transparent">AIStory</h1>
        <div className="flex gap-4">
          {user ? (
            <Link to="/dashboard" className="px-6 py-2 bg-purple-600 rounded-lg hover:bg-purple-700 transition">Dashboard</Link>
          ) : (
            <>
              <Link to="/login" className="px-6 py-2 border border-white/20 rounded-lg hover:bg-white/10 transition">Login</Link>
              <Link to="/register" className="px-6 py-2 bg-purple-600 rounded-lg hover:bg-purple-700 transition">Get Started</Link>
            </>
          )}
        </div>
      </nav>
      <main className="max-w-5xl mx-auto text-center pt-32 px-4">
        <h2 className="text-6xl font-bold mb-6">Turn Your Story Into Video</h2>
        <p className="text-xl text-white/60 mb-10 max-w-2xl mx-auto">AI-powered text-to-video platform. Bring your own API keys, choose from 180+ AI models, create stunning videos with 12-stage pipeline.</p>
        <Link to={user ? '/dashboard' : '/register'} className="px-10 py-4 bg-gradient-to-r from-purple-500 to-pink-500 rounded-xl text-lg font-semibold hover:opacity-90 transition shadow-2xl shadow-purple-500/25">
          {user ? 'Go to Dashboard' : 'Start Creating Free'}
        </Link>
        <div className="mt-20 grid grid-cols-3 gap-8">
          {[
            { title: '12-Stage Pipeline', desc: 'Script analysis → storyboard → image gen → video → audio → export' },
            { title: '180+ AI Models', desc: 'Claude, GPT, Kling, Runway, ElevenLabs — bring your own keys' },
            { title: 'BYOK Security', desc: 'Envelope encryption. Your keys never touch our servers in plaintext.' },
          ].map((f, i) => (
            <div key={i} className="p-6 rounded-xl bg-white/5 border border-white/10 text-left">
              <h3 className="text-lg font-semibold mb-2">{f.title}</h3>
              <p className="text-white/50 text-sm">{f.desc}</p>
            </div>
          ))}
        </div>
      </main>
    </div>
  );
}
