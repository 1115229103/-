import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import api from '../api';

const CATEGORY_LABELS = {
  llm: '大语言模型', image_gen: '图像生成', consistency: '角色一致性',
  image_enhance: '图像增强', image2video: '图生视频', video_enhance: '视频增强',
  tts: '语音合成', music: '音乐生成', asr: '语音识别', moderation: '内容审核',
};

export default function ModelsConfig() {
  const [categories, setCategories] = useState([]);
  const [selectedCat, setSelectedCat] = useState('');
  const [models, setModels] = useState([]);
  const [configs, setConfigs] = useState([]);
  const [loading, setLoading] = useState(true);
  const [loadError, setLoadError] = useState('');
  const [modal, setModal] = useState(null);
  const [apiKey, setApiKey] = useState('');
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState('');
  const [verifyMsg, setVerifyMsg] = useState({});

  useEffect(() => {
    Promise.all([
      api.get('/models/categories'),
      api.get('/user/model-configs'),
    ]).then(([catRes, cfgRes]) => {
      const cats = Object.keys(catRes.data.data || {});
      setCategories(cats);
      if (cats.length > 0) setSelectedCat(cats[0]);
      setConfigs(cfgRes.data.data || []);
    }).catch(() => {
      setLoadError('加载失败，请检查网络后刷新页面');
    }).finally(() => {
      setLoading(false);
    });
  }, []);

  useEffect(() => {
    if (!selectedCat) return;
    setLoading(true);
    setLoadError('');
    api.get(`/models?category=${selectedCat}`).then(({ data }) => {
      setModels(data.data || []);
    }).catch(() => {
      setModels([]);
      setLoadError('模型列表加载失败，请刷新页面重试');
    }).finally(() => setLoading(false));
  }, [selectedCat]);

  const configured = (modelId) => configs.find((c) => c.model_registry_id === modelId);

  const handleSave = async () => {
    if (!apiKey.trim()) return setError('请填写 API Key');
    setSaving(true); setError('');
    try {
      await api.post('/user/model-configs', {
        model_registry_id: modal.id,
        stage: modal.stages[0],
        api_key: apiKey.trim(),
      });
      const { data } = await api.get('/user/model-configs');
      setConfigs(data.data || []);
      setModal(null); setApiKey('');
    } catch (err) {
      setError(err.response?.data?.errors?.api_key?.[0] || err.response?.data?.message || err.response?.data?.error || '保存失败');
    } finally {
      setSaving(false);
    }
  };

  useEffect(() => {
    if (!modal) return;
    const onKey = (e) => { if (e.key === 'Escape') { setModal(null); setError(''); setApiKey(''); } };
    document.addEventListener('keydown', onKey);
    return () => document.removeEventListener('keydown', onKey);
  }, [modal]);

  const handleDelete = async (configId) => {
    try {
      await api.delete(`/user/model-configs/${configId}`);
      const { data } = await api.get('/user/model-configs');
      setConfigs(data.data || []);
    } catch (err) {
      setError(err.response?.data?.message || '删除失败，请重试');
    }
  };

  const handleVerify = async (configId) => {
    setVerifyMsg((p) => ({ ...p, [configId]: '校验中...' }));
    try {
      const { data } = await api.post(`/user/model-configs/${configId}/verify`);
      const valid = data.data?.valid;
      setVerifyMsg((p) => ({ ...p, [configId]: valid ? '可用 ✓' : '不可用 ✗' }));
    } catch {
      setVerifyMsg((p) => ({ ...p, [configId]: '校验失败' }));
    }
  };

  return (
    <div className="models-config-page">
      <header className="mc-header">
        <Link to="/dashboard" className="mc-back">← 返回</Link>
        <h2>模型与 API Key 配置</h2>
        <p style={{color:'var(--text-muted)',fontSize:'0.9rem'}}>选择各环节想用的AI模型，填写自己的API Key</p>
      </header>

      {loadError && <div style={{color:'var(--error)',padding:'12px 16px',background:'rgba(220,53,69,0.08)',borderRadius:'8px',marginBottom:16}}>{loadError}</div>}

      <div className="mc-categories">
        {categories.map((cat) => (
          <button
            key={cat}
            className={`mc-cat-btn ${selectedCat === cat ? 'active' : ''}`}
            onClick={() => setSelectedCat(cat)}
          >
            {CATEGORY_LABELS[cat] || cat}
          </button>
        ))}
      </div>

      <div className="mc-content">
        {/* Configured keys summary */}
        <section className="mc-section">
          <h3>已配置的 Key ({configs.length})</h3>
          {configs.length === 0 ? (
            <p style={{color:'var(--text-muted)',fontSize:'0.9rem'}}>还没有配置任何模型，请从下方选择</p>
          ) : (
            <div className="configs-list">
              {configs.map((c) => (
                <div key={c.id} className="config-card">
                  <div className="config-info">
                    <strong>{c.model_display_name || c.stage}</strong>
                    <span style={{color:'var(--text-muted)',fontSize:'0.8rem'}}>{c.provider} · {c.api_type}</span>
                    {c.api_key_masked && <code style={{fontSize:'0.75rem'}}>{c.api_key_masked}</code>}
                  </div>
                  <div className="config-actions">
                    {c.status === 'active' && <span className="badge ok">已激活</span>}
                    {c.status === 'error' && <span className="badge err">异常</span>}
                    <button className="btn small" onClick={() => handleVerify(c.id)}>
                      {verifyMsg[c.id] || '校验'}
                    </button>
                    <button className="btn small" onClick={() => handleDelete(c.id)} style={{color:'var(--error)'}}>
                      删除
                    </button>
                  </div>
                </div>
              ))}
            </div>
          )}
        </section>

        {/* Available models for selected category */}
        <section className="mc-section">
          <h3>{CATEGORY_LABELS[selectedCat] || selectedCat} — 可选模型</h3>
          {loading ? (
            <p style={{color:'var(--text-muted)'}}>加载中...</p>
          ) : models.length === 0 ? (
            <p style={{color:'var(--text-muted)'}}>该类别暂无可选模型</p>
          ) : (
            <div className="models-grid">
              {models.map((m) => {
                const cfg = configured(m.id);
                return (
                  <div key={m.id} className={`model-card ${cfg ? 'configured' : ''}`}>
                    <div className="model-head">
                      <h4>{m.display_name}</h4>
                      {cfg && <span className="badge ok">已配置</span>}
                    </div>
                    <p style={{color:'var(--text-muted)',fontSize:'0.85rem',marginBottom:8}}>
                      {m.provider} · {m.api_type}
                    </p>
                    {m.description && <p style={{fontSize:'0.85rem',marginBottom:12}}>{m.description}</p>}
                    {cfg ? (
                      <p style={{fontSize:'0.8rem',color:'var(--text-muted)'}}>
                        Key: {cfg.api_key_masked} · {cfg.status}
                      </p>
                    ) : (
                      <button
                        className="btn primary small"
                        onClick={() => setModal({ id: m.id, name: m.display_name, stages: m.category ? [m.category] : [] })}
                      >
                        配置 Key
                      </button>
                    )}
                  </div>
                );
              })}
            </div>
          )}
        </section>
      </div>

      {/* Key input modal */}
      {modal && (
        <div className="modal-overlay" onClick={() => { setModal(null); setError(''); setApiKey(''); }}>
          <div className="modal-box" onClick={(e) => e.stopPropagation()}>
            <h3>配置 {modal.name}</h3>
            <label htmlFor="apikey-input" style={{marginTop:12}}>API Key</label>
            <input
              id="apikey-input"
              type="password"
              value={apiKey}
              onChange={(e) => setApiKey(e.target.value)}
              placeholder="输入你的 API Key"
              autoFocus
            />
            {error && <span className="field-error">{error}</span>}
            <p style={{fontSize:'0.8rem',color:'var(--text-muted)',marginTop:12}}>
              Key 将加密存储，平台无法查看明文。仅你发起的AI调用会使用此Key。
            </p>
            <div style={{display:'flex',gap:8,marginTop:16}}>
              <button className="btn secondary" onClick={() => { setModal(null); setError(''); setApiKey(''); }}>
                取消
              </button>
              <button className="btn primary" onClick={handleSave} disabled={saving}>
                {saving ? '保存中...' : '保存'}
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
