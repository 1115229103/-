const { firefox } = require('playwright');
const BASE = 'http://localhost:8085';
const USER_APP = `${BASE}/user-app`;
const ADMIN_APP = `${BASE}/admin`;
const API = `${BASE}/api/v1`;

let passed = 0, failed = 0;

function check(name, condition, detail = '') {
  if (condition) { passed++; console.log(`  PASS: ${name}`); }
  else { failed++; console.log(`  FAIL: ${name}${detail ? ' — ' + detail : ''}`); }
}

(async () => {
  const browser = await firefox.launch({ headless: true });
  const context = await browser.newContext({ ignoreHTTPSErrors: true });
  const page = await context.newPage();

  const testEmail = `e2e-browser-${Date.now()}@test.com`;
  const testPassword = 'Test123456';
  let testToken = '';

  // ═══════════════════════════════════════════
  // SECTION 1: User App — Auth Pages
  // ═══════════════════════════════════════════
  console.log('\n--- User App: Auth Pages ---');

  await page.goto(`${USER_APP}/login`, { waitUntil: 'networkidle' }).catch(() => {});
  const loginForm = await page.$('.auth-form');
  check('Login form renders', !!loginForm);
  const loginH2 = await page.textContent('.auth-form h2').catch(() => '');
  check('Login title is correct', loginH2.includes('登录'));

  await page.fill('#login-email', 'wrong@test.com');
  await page.fill('#login-password', 'wrongpassword123');
  await page.click('.auth-form button[type="submit"]');
  await page.waitForTimeout(2000);
  const loginError = await page.$('.alert.error');
  check('Login with bad credentials shows error', !!loginError);

  await page.goto(`${USER_APP}/register`, { waitUntil: 'networkidle' }).catch(() => {});
  const regForm = await page.$('.auth-form');
  check('Register form renders', !!regForm);
  const regH2 = await page.textContent('.auth-form h2').catch(() => '');
  check('Register title is correct', regH2.includes('注册'));

  // Register a new user
  await page.fill('#reg-name', 'BrowserTest');
  await page.fill('#reg-email', testEmail);
  await page.fill('#reg-password', testPassword);
  await page.click('.auth-form button[type="submit"]');
  await page.waitForTimeout(4000);
  const onDashboard = page.url().includes('/dashboard');
  check('Register redirects to dashboard', onDashboard);

  // ═══════════════════════════════════════════
  // SECTION 2: User App — Dashboard
  // ═══════════════════════════════════════════
  console.log('--- User App: Dashboard ---');

  testToken = await page.evaluate(() => localStorage.getItem('token'));
  check('Token stored after register', !!testToken);

  if (testToken) {
    const headerVisible = await page.$('.dash-header');
    check('Dashboard header renders', !!headerVisible);

    const heading = await page.textContent('.dash-header h1').catch(() => '');
    check('Dashboard shows AIStory branding', heading.includes('AIStory'));

    const userInfo = await page.$('.user-info');
    check('Dashboard shows user info section', !!userInfo);
  }

  // ═══════════════════════════════════════════
  // SECTION 3: User App — Models Config
  // ═══════════════════════════════════════════
  console.log('--- User App: Models Config ---');

  await page.goto(`${USER_APP}/models-config`, { waitUntil: 'networkidle' }).catch(() => {});
  const mcHeader = await page.$('.mc-header');
  check('Models config page renders', !!mcHeader);

  const categoryBtns = await page.$$('.mc-cat-btn');
  check('Model categories are displayed', categoryBtns.length > 0);
  console.log(`  INFO: ${categoryBtns.length} model categories`);

  if (categoryBtns.length > 0) {
    await categoryBtns[0].click();
    await page.waitForTimeout(1500);
    const modelCards = await page.$$('.model-card');
    console.log(`  INFO: ${modelCards.length} models in first category`);
  }

  // ═══════════════════════════════════════════
  // SECTION 4: User App — Create Work & Account
  // ═══════════════════════════════════════════
  console.log('--- User App: Pages ---');

  await page.goto(`${USER_APP}/works/new`, { waitUntil: 'networkidle' }).catch(() => {});
  const newWorkBody = await page.textContent('body').catch(() => '');
  check('Create Work page loads', newWorkBody.length > 20);

  await page.goto(`${USER_APP}/account`, { waitUntil: 'networkidle' }).catch(() => {});
  const accountBody = await page.textContent('body').catch(() => '');
  check('Account page loads', accountBody.length > 20);

  // ═══════════════════════════════════════════
  // SECTION 5: Logout
  // ═══════════════════════════════════════════
  console.log('--- User App: Logout ---');

  await page.goto(`${USER_APP}/dashboard`, { waitUntil: 'networkidle' }).catch(() => {});
  const logoutBtn = await page.$('button:has-text("退出")');
  if (logoutBtn) {
    await logoutBtn.click();
    await page.waitForTimeout(1500);
    const loggedOut = page.url().includes('/login');
    check('Logout redirects to login', loggedOut);
    const tokenCleared = await page.evaluate(() => !localStorage.getItem('token'));
    check('Token cleared after logout', tokenCleared);
  } else {
    check('Logout button present', false, 'logout button not found');
  }

  // ═══════════════════════════════════════════
  // SECTION 6: Re-login
  // ═══════════════════════════════════════════
  console.log('--- User App: Re-login ---');

  await page.goto(`${USER_APP}/login`, { waitUntil: 'networkidle' }).catch(() => {});
  await page.fill('#login-email', testEmail);
  await page.fill('#login-password', testPassword);
  await page.click('.auth-form button[type="submit"]');
  await page.waitForTimeout(3000);
  check('Re-login successful', page.url().includes('/dashboard'));

  testToken = await page.evaluate(() => localStorage.getItem('token'));

  // ═══════════════════════════════════════════
  // SECTION 7: Admin App
  // ═══════════════════════════════════════════
  console.log('--- Admin App ---');

  await page.goto(`${ADMIN_APP}/login`, { waitUntil: 'networkidle' }).catch(() => {});
  // Admin page should render the Vue app with login form
  const adminBodyText = await page.textContent('body').catch(() => '');
  const adminHasContent = adminBodyText.length > 20;
  check('Admin login page renders (SPA routing works)', adminHasContent);

  // ═══════════════════════════════════════════
  // SECTION 8: API Contract Verification
  // ═══════════════════════════════════════════
  console.log('--- API Contract ---');

  const healthRes = await page.request.get(`${API}/health`);
  check('API health returns 200', healthRes.status() === 200);
  const healthData = await healthRes.json().catch(() => ({}));
  check('API health status is ok', healthData.status === 'ok');

  const modelsRes = await page.request.get(`${API}/models`);
  check('GET /models returns 200', modelsRes.status() === 200);

  const catRes = await page.request.get(`${API}/models/categories`);
  check('GET /models/categories returns 200', catRes.status() === 200);

  const plansRes = await page.request.get(`${API}/plans`);
  check('GET /plans returns 200', plansRes.status() === 200);

  // Auth-protected without token
  const unauthRes = await page.request.get(`${API}/auth/me`);
  check('GET /auth/me without token returns 401', unauthRes.status() === 401);

  // Auth-protected with token
  if (testToken) {
    const authRes = await page.request.get(`${API}/auth/me`, {
      headers: { Authorization: `Bearer ${testToken}` }
    });
    check('GET /auth/me with token returns 200', authRes.status() === 200);
    const meData = await authRes.json().catch(() => ({}));
    check('Auth/me returns user data', !!meData.data?.id);
  } else {
    check('GET /auth/me with token returns 200', false, 'no token available');
    check('Auth/me returns user data', false, 'no token available');
  }

  // ═══════════════════════════════════════════
  // SECTION 9: Security Headers
  // ═══════════════════════════════════════════
  console.log('--- Security Headers ---');

  const spaRes = await page.request.get(`${USER_APP}/`);
  const frameOpt = spaRes.headers()['x-frame-options'];
  const contentOpt = spaRes.headers()['x-content-type-options'];
  check('X-Frame-Options present', !!frameOpt);
  check('X-Content-Type-Options present', !!contentOpt);

  const adminSpaRes = await page.request.get(`${ADMIN_APP}/`);
  const adminFrameOpt = adminSpaRes.headers()['x-frame-options'];
  check('Admin SPA also has security headers', !!adminFrameOpt);

  // ═══════════════════════════════════════════
  // SECTION 10: SPA routing verification
  // ═══════════════════════════════════════════
  console.log('--- SPA Routing ---');

  // User-app SPA via route
  const userIndex = await page.request.get(`${USER_APP}/`);
  const userHtml = await userIndex.text();
  check('User SPA serves React root div', userHtml.includes('<div id="root">'));

  // Admin login via SPA routing (was broken before server.php fix)
  const adminLogin = await page.request.get(`${ADMIN_APP}/login`);
  const adminLoginHtml = await adminLogin.text();
  check('Admin /login serves admin SPA (not user SPA)', adminLoginHtml.includes('<div id="app">'));

  // Admin works page also serves admin SPA
  const adminWorks = await page.request.get(`${ADMIN_APP}/works`);
  const adminWorksHtml = await adminWorks.text();
  check('Admin /works serves admin SPA', adminWorksHtml.includes('<div id="app">'));

  // ═══════════════════════════════════════════
  // SUMMARY
  // ═══════════════════════════════════════════
  const total = passed + failed;
  console.log(`\n${'═'.repeat(50)}`);
  console.log(`Results: ${passed} passed, ${failed} failed, ${total} total`);
  console.log(`${'═'.repeat(50)}\n`);

  await browser.close();
  process.exit(failed > 0 ? 1 : 0);
})();
