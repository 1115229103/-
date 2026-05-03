import { useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import api from '../api';

const STYLES = ['写实', '动漫', '水墨', '3D', '像素', '赛博朋克', '油画', '素描', '扁平', '其他'];
const DURATIONS = [
  { label: '30秒', value: 30 }, { label: '1分钟', value: 60 }, { label: '2分钟', value: 120 },
  { label: '3分钟', value: 180 }, { label: '5分钟', value: 300 },
];

export default function CreateWork() {
  const [title, setTitle] = useState('');
  const [style, setStyle] = useState('写实');
  const [targetDuration, setTargetDuration] = useState(60);
  const [errors, setErrors] = useState({});
  const [loading, setLoading] = useState(false);
  const navigate = useNavigate();

  const handleSubmit = async (e) => {
    e.preventDefault();
    setErrors({});
    setLoading(true);
    try {
      const { data } = await api.post('/works', {
        title,
        style,
        target_duration_sec: targetDuration,
      });
      const workId = data.data?.id;
      if (workId) {
        navigate(`/works/${workId}`);
      } else {
        navigate('/dashboard');
      }
    } catch (err) {
      if (err.response?.data?.errors) {
        setErrors(err.response.data.errors);
      } else {
        setErrors({ general: err.response?.data?.message || err.response?.data?.error || '创建失败' });
      }
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="auth-page">
      <form onSubmit={handleSubmit} className="auth-form" style={{maxWidth:500}}>
        <h2>新建作品</h2>
        {errors.general && <div className="alert error">{errors.general}</div>}

        <label htmlFor="work-title">作品名称</label>
        <input id="work-title" type="text" value={title} onChange={(e) => setTitle(e.target.value)} required placeholder="输入作品名称" />
        {errors.title && <span className="field-error">{errors.title[0]}</span>}

        <label>风格</label>
        <div className="style-picker">
          {STYLES.map((s) => (
            <button key={s} type="button" className={`style-chip ${style === s ? 'active' : ''}`} onClick={() => setStyle(s)}>
              {s}
            </button>
          ))}
        </div>
        {errors.style && <span className="field-error">{errors.style[0]}</span>}

        <label>目标时长</label>
        <div className="style-picker">
          {DURATIONS.map((d) => (
            <button key={d.value} type="button" className={`style-chip ${targetDuration === d.value ? 'active' : ''}`} onClick={() => setTargetDuration(d.value)}>
              {d.label}
            </button>
          ))}
        </div>
        {errors.target_duration_sec && <span className="field-error">{errors.target_duration_sec[0]}</span>}

        <div style={{display:'flex',gap:12,marginTop:24}}>
          <Link to="/dashboard" className="btn secondary" style={{flex:1}}>取消</Link>
          <button type="submit" disabled={loading} className="btn primary" style={{flex:1}}>
            {loading ? '创建中...' : '创建作品'}
          </button>
        </div>
      </form>
    </div>
  );
}
