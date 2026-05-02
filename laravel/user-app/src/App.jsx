import { BrowserRouter, Routes, Route, Navigate } from 'react-router-dom';
import Landing from './pages/Landing';
import Login from './pages/Login';
import Register from './pages/Register';
import Dashboard from './pages/Dashboard';
import ModelsConfig from './pages/ModelsConfig';
import CreateWork from './pages/CreateWork';
import WorkDetail from './pages/WorkDetail';
import Account from './pages/Account';

function RequireAuth({ children }) {
  const token = localStorage.getItem('token');
  if (!token) return <Navigate to="/login" replace />;
  return children;
}

function RedirectIfAuth({ children }) {
  const token = localStorage.getItem('token');
  if (token) return <Navigate to="/dashboard" replace />;
  return children;
}

export default function App() {
  return (
    <BrowserRouter>
      <Routes>
        <Route path="/" element={<Landing />} />
        <Route path="/login" element={<RedirectIfAuth><Login /></RedirectIfAuth>} />
        <Route path="/register" element={<RedirectIfAuth><Register /></RedirectIfAuth>} />
        <Route path="/dashboard" element={<RequireAuth><Dashboard /></RequireAuth>} />
        <Route path="/models-config" element={<RequireAuth><ModelsConfig /></RequireAuth>} />
        <Route path="/works/new" element={<RequireAuth><CreateWork /></RequireAuth>} />
        <Route path="/works/:id" element={<RequireAuth><WorkDetail /></RequireAuth>} />
        <Route path="/account" element={<RequireAuth><Account /></RequireAuth>} />
      </Routes>
    </BrowserRouter>
  );
}
