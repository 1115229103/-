import { createContext, useContext, useState, useEffect } from 'react';
import client from '../api/client';

const AuthContext = createContext(null);

export function AuthProvider({ children }) {
  const [user, setUser] = useState(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const token = localStorage.getItem('auth_token');
    if (token) {
      client.get('/auth/me').then(r => setUser(r.data.data)).catch(() => localStorage.removeItem('auth_token')).finally(() => setLoading(false));
    } else {
      setLoading(false);
    }
  }, []);

  const login = async (email, password) => {
    const r = await client.post('/auth/login', { email, password });
    localStorage.setItem('auth_token', r.data.data.token);
    setUser(r.data.data.user);
    return r.data;
  };

  const register = async (name, email, password) => {
    const r = await client.post('/auth/register', { name, email, password });
    localStorage.setItem('auth_token', r.data.data.token);
    setUser(r.data.data.user);
    return r.data;
  };

  const logout = async () => {
    await client.post('/auth/logout').catch(() => {});
    localStorage.removeItem('auth_token');
    setUser(null);
  };

  return (
    <AuthContext.Provider value={{ user, loading, login, register, logout }}>
      {children}
    </AuthContext.Provider>
  );
}

export const useAuth = () => useContext(AuthContext);
