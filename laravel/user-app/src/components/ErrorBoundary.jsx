import { Component } from 'react';

export default class ErrorBoundary extends Component {
  constructor(props) {
    super(props);
    this.state = { hasError: false, error: null };
  }

  static getDerivedStateFromError(error) {
    return { hasError: true, error };
  }

  render() {
    if (this.state.hasError) {
      return (
        <div className="dashboard">
          <header className="dash-header"><h1>AIStory</h1></header>
          <main style={{textAlign:'center',paddingTop:80}}>
            <h2 style={{color:'var(--error)',marginBottom:16}}>应用出错了</h2>
            <p style={{color:'var(--text-muted)',marginBottom:24}}>
              {this.state.error?.message || '未知错误'}
            </p>
            <button className="btn primary" onClick={() => { this.setState({ hasError: false }); window.location.href = '/'; }}>
              返回首页
            </button>
          </main>
        </div>
      );
    }
    return this.props.children;
  }
}
