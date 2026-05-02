import { Link } from 'react-router-dom';

export default function Landing() {
  return (
    <div className="landing">
      <header className="hero">
        <h1>AIStory</h1>
        <p className="subtitle">一站式 AI 视频生成平台 · 自带 Key，自由组合模型</p>
        <p className="desc">支持文案解析 → 分镜规划 → 画面生成 → 图生视频 → AI配音 → 字幕生成 全流程</p>
        <div className="actions">
          <Link to="/register" className="btn primary">免费开始</Link>
          <Link to="/login" className="btn secondary">登录</Link>
        </div>
      </header>
      <section className="features">
        <div className="card"><h3>12 环节管线</h3><p>从文案到成片，全自动 AI 工作流</p></div>
        <div className="card"><h3>100+ AI 模型</h3><p>支持 Claude、GPT、可灵、豆包、Runway 等</p></div>
        <div className="card"><h3>自带 Key</h3><p>你的 Key 加密存储，平台不接触明文</p></div>
        <div className="card"><h3>自由组合</h3><p>每个环节独立选择模型，按需搭配</p></div>
      </section>
    </div>
  );
}
