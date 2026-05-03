import { useEffect, useState, useCallback } from 'react';
import { useParams, Link, useNavigate } from 'react-router-dom';
import api from '../api';

const STAGE_NAMES = {
  script_analysis: '文案解析', storyboard: '分镜规划', continuation: '文案续写',
  image_gen: '画面生成', consistency: '角色一致', image_enhance: '图像后处理',
  image2video: '图生视频', video_enhance: '视频增强', tts: 'AI配音',
  music: '背景音乐', asr: '字幕生成', moderation: '敏感词检测',
};

export default function WorkDetail() {
  const { id } = useParams();
  const navigate = useNavigate();
  const [work, setWork] = useState(null);
  const [progress, setProgress] = useState(null);
  const [loading, setLoading] = useState(true);
  const [starting, setStarting] = useState(false);
  const [error, setError] = useState('');

  const load = useCallback(async () => {
    try {
      const { data } = await api.get(`/works/${id}`);
      setWork(data.data);
      if (data.data.status === 'processing') {
        const p = await api.get(`/works/${id}/pipeline/progress`);
        setProgress(p.data.data);
      }
    } catch (e) {
      if (e.response?.status === 404) {
        navigate('/dashboard');
      } else {
        setError('加载失败，请检查网络后刷新页面');
      }
    } finally {
      setLoading(false);
    }
  }, [id, navigate]);

  useEffect(() => { load(); }, [load]);

  useEffect(() => {
    if (!work || work.status !== 'processing') return;
    let count = 0;
    const maxPolls = 120;
    const t = setInterval(async () => {
      try {
        const { data } = await api.get(`/works/${id}/pipeline/progress`);
        setProgress(data.data);
        if (++count >= maxPolls || data.data?.status !== 'processing') clearInterval(t);
      } catch { clearInterval(t); }
    }, 3000);
    return () => clearInterval(t);
  }, [work?.status, id]);

  const startPipeline = async () => {
    setStarting(true); setError('');
    try {
      await api.post(`/works/${id}/pipeline/start`);
      setWork((w) => ({ ...(w || {}), status: 'processing' }));
      setProgress({ status: 'processing', state: 'script_analysis', progress: 0 });
    } catch (err) {
      setError(err.response?.data?.error || '启动失败');
    } finally {
      setStarting(false);
    }
  };

  const handleDelete = async () => {
    if (!confirm('确定删除此作品？')) return;
    try {
      await api.delete(`/works/${id}`);
      navigate('/dashboard');
    } catch (err) {
      setError(err.response?.data?.error || err.response?.data?.message || '删除失败，请重试');
    }
  };

  if (loading) return <div className="dashboard"><main><p style={{color:'var(--text-muted)'}}>加载中...</p></main></div>;
  if (!work) return <div className="dashboard"><main><p style={{color:'var(--error)',padding:'12px 0'}}>{error || '加载失败，请刷新页面'}</p><Link to="/dashboard" className="btn small">返回首页</Link></main></div>;

  const stageCount = Object.keys(STAGE_NAMES).length;

  return (
    <div className="dashboard">
      <header className="dash-header">
        <h1>AIStory</h1>
        <div className="user-info">
          <Link to="/dashboard" className="btn small">返回</Link>
        </div>
      </header>
      <main>
        <div style={{display:'flex',alignItems:'center',justifyContent:'space-between',marginBottom:24}}>
          <div>
            <h2 style={{margin:0}}>{work.title}</h2>
            <span className={`status ${work.status}`} style={{marginTop:8}}>{work.status}</span>
            {work.style && <span style={{marginLeft:12,color:'var(--text-muted)',fontSize:'0.9rem'}}>{work.style}</span>}
          </div>
          <div style={{display:'flex',gap:8}}>
            {['draft', 'failed'].includes(work.status) && (
              <button className="btn primary" onClick={startPipeline} disabled={starting}>
                {starting ? '启动中...' : '开始生成'}
              </button>
            )}
            <button className="btn secondary small" onClick={handleDelete}>删除</button>
          </div>
        </div>

        {error && <div className="alert error" style={{marginBottom:16}}>{error}</div>}

        {/* Pipeline progress */}
        {(progress || work.status === 'processing') && (
          <div className="card" style={{marginBottom:24}}>
            <h3>生成进度</h3>
            <div className="progress-bar" style={{marginTop:12}}>
              <div
                className="progress-fill"
                style={{ width: `${progress?.progress || 0}%` }}
              />
            </div>
            <p style={{marginTop:8,fontSize:'0.85rem',color:'var(--text-muted)'}}>
              {progress?.progress || 0}% · {STAGE_NAMES[progress?.state] || progress?.state || '等待开始'}
            </p>
            {progress?.error && (
              <div className="alert error" style={{marginTop:8}}>{progress.error}</div>
            )}
          </div>
        )}

        {/* Related data */}
        {work.script && (
          <div className="card" style={{marginBottom:16}}>
            <h3>文案</h3>
            <p style={{marginTop:8,fontSize:'0.9rem'}}>{work.script.content || work.script.title}</p>
          </div>
        )}

        {work.characters?.length > 0 && (
          <div className="card" style={{marginBottom:16}}>
            <h3>角色 ({work.characters.length})</h3>
            <div style={{display:'flex',flexWrap:'wrap',gap:8,marginTop:8}}>
              {work.characters.map((c) => (
                <span key={c.id} className="status draft" style={{fontSize:'0.8rem'}}>{c.name}</span>
              ))}
            </div>
          </div>
        )}

        {work.scenes?.length > 0 && (
          <div className="card" style={{marginBottom:16}}>
            <h3>场景 ({work.scenes.length})</h3>
            <div className="works-grid" style={{marginTop:8}}>
              {work.scenes.map((s) => (
                <div key={s.id} className="work-card">
                  <h3>{s.name}</h3>
                  <p>{s.location} · {s.time} · {s.indoor ? '室内' : '室外'}</p>
                  <p>{s.atmosphere}</p>
                </div>
              ))}
            </div>
          </div>
        )}

        {work.storyboards?.length > 0 && (
          <div className="card" style={{marginBottom:16}}>
            <h3>分镜 ({work.storyboards.length})</h3>
            <div className="works-grid" style={{marginTop:8}}>
              {work.storyboards.map((s) => (
                <div key={s.id} className="work-card">
                  <h3>镜头 {s.shot_number}</h3>
                  <p>{s.shot_type} · {s.camera_movement} · {s.duration_sec}秒</p>
                  <p>{s.action_description}</p>
                </div>
              ))}
            </div>
          </div>
        )}

        {work.export_tasks?.length > 0 && (
          <div className="card" style={{marginBottom:16}}>
            <h3>导出任务</h3>
            {work.export_tasks.map((e) => (
              <div key={e.id} className="work-card" style={{marginTop:8}}>
                <h3>{e.resolution} · {e.format}</h3>
                <p style={{fontSize:'0.85rem'}}>
                  <span className={`status ${e.status}`}>{e.status}</span>
                </p>
                {e.download_url && (
                  <a href={e.download_url} className="btn primary small" style={{marginTop:8}}>下载</a>
                )}
              </div>
            ))}
          </div>
        )}

        {!work.script && !work.characters?.length && !work.storyboards?.length && (
          <div className="empty-state" style={{marginTop:24}}>
            <p>{work.status === 'draft' ? '点击"开始生成"启动AI创作流水线' : '暂无关联数据'}</p>
          </div>
        )}
      </main>
    </div>
  );
}
