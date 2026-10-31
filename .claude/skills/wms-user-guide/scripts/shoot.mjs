// Chụp màn hình thật cho Hướng Dẫn Sử Dụng qua Chrome DevTools Protocol (Node >= 22, không cần thư viện).
//
//   node shoot.mjs <plan.json> <thư mục ảnh> [baseUrl=http://127.0.0.1:8000]
//
// Mỗi dòng của plan (xem plan.php): name, url, user ('-' = chưa đăng nhập), dept, flash, width/height,
// tab (id vùng nội dung cần mở), js (chạy sau khi mở tab), highlight (selector viền đỏ), clip (selector
// vùng cắt, gộp mọi phần tử khớp), pad, maxHeight. Phiên đăng nhập tạo bằng mint.php.
import { spawn, execFileSync } from 'node:child_process';
import { readFileSync, writeFileSync, mkdirSync, rmSync } from 'node:fs';
import { tmpdir } from 'node:os';
import { join, dirname } from 'node:path';
import { fileURLToPath } from 'node:url';

const here = dirname(fileURLToPath(import.meta.url));
const [planFile, outDir, baseUrl = 'http://127.0.0.1:8000'] = process.argv.slice(2);
const plan = JSON.parse(readFileSync(planFile, 'utf8'));
mkdirSync(outDir, { recursive: true });

const CHROME = ['C:/Program Files/Google/Chrome/Application/chrome.exe',
    'C:/Program Files (x86)/Google/Chrome/Application/chrome.exe']
    .find(p => { try { readFileSync(p, { flag: 'r' }); return true; } catch { return false; } });
const profile = join(tmpdir(), 'wms-ug-chrome');
const chrome = spawn(CHROME, ['--headless=new', '--remote-debugging-port=9333', '--user-data-dir=' + profile,
    '--hide-scrollbars', '--force-device-scale-factor=1', 'about:blank'], { stdio: 'ignore' });

const sleep = ms => new Promise(r => setTimeout(r, ms));
let target;
for (let i = 0; i < 60 && !target; i++) {
    await sleep(300);
    try { target = (await (await fetch('http://127.0.0.1:9333/json')).json()).find(t => t.type === 'page'); } catch {}
}
const ws = new WebSocket(target.webSocketDebuggerUrl);
await new Promise(r => ws.onopen = r);
let seq = 0;
const pending = new Map();
const waiters = [];
ws.onmessage = e => {
    const m = JSON.parse(e.data);
    if (m.id && pending.has(m.id)) { pending.get(m.id)(m); pending.delete(m.id); }
    if (m.method) waiters.filter(w => w.method === m.method).forEach(w => { w.fn(m); waiters.splice(waiters.indexOf(w), 1); });
};
const send = (method, params = {}) => new Promise(r => { const i = ++seq; pending.set(i, r); ws.send(JSON.stringify({ id: i, method, params })); });
const once = method => new Promise(fn => waiters.push({ method, fn }));
const evaluate = async expr => (await Promise.race([
    send('Runtime.evaluate', { expression: expr, returnByValue: true, awaitPromise: true }),
    new Promise(r => setTimeout(() => r({}), 20000)),   // JS lỗi / Promise treo thì bỏ qua sau 20s
])).result?.result?.value;

// Hàm phụ dùng trong 'js' của plan
const PRELUDE = `window.__ug = {
  visible: e => !!e && e.getClientRects().length > 0,
  tab(ids) {
    for (const p of ids) {
      const a = document.querySelector('[href="#' + p + '"],[data-target="#' + p + '"],[aria-controls="' + p + '"],[data-tab="' + p + '"],[data-pane="' + p + '"]');
      if (a) { a.click(); return true; }
    }
    return false;
  },
  click(sel) {
    // Ưu tiên nút đang hiện; nút nằm trong cột thu gọn / menu thao tác vẫn bấm được qua handler gắn ở document
    const all = [...document.querySelectorAll(sel)];
    const el = all.find(e => this.visible(e)) || all[0];
    if (el) el.click();
    return !!el;
  },
  clickCreate() {
    const panes = [...document.querySelectorAll('.tab-pane.active, .tab-pane.show')].filter(this.visible);
    const scope = panes.length ? panes[panes.length - 1] : document;
    const btn = [...scope.querySelectorAll('button, a.btn')].filter(this.visible)
      .find(b => /^\\s*\\+?\\s*Thêm/i.test(b.textContent) || (b.querySelector('.fa-plus') && b.textContent.trim().length < 40));
    if (btn) btn.click();
    return !!btn;
  },
};
const s = document.createElement('style');
s.textContent = '*{animation:none!important;transition:none!important} .modal.fade{opacity:1} .ug-hl{outline:3px solid #e53935!important;outline-offset:3px;border-radius:6px}';
document.head.appendChild(s);`;

await send('Page.enable');
// Giữ menu trái ở trạng thái ghim (mặc định của người dùng): bỏ ghim thì DataTables responsive ẩn hết cột
await send('Page.addScriptToEvaluateOnNewDocument', { source: "try { localStorage.setItem('wmsNavPinned', '1') } catch (e) {}" });
await send('Network.enable');
const result = { ok: [], missing: [] };

for (const shot of plan) {
    await send('Network.clearBrowserCookies');
    const minted = JSON.parse(execFileSync('php', [join(here, 'mint.php'), shot.user || '-', JSON.stringify(shot.flash || {}), String(shot.dept ?? '')]).toString());
    await send('Network.setCookie', { name: minted.name, value: minted.value, domain: new URL(baseUrl).hostname, path: '/' });
    await send('Emulation.setDeviceMetricsOverride', { width: shot.width || 1440, height: shot.height || 1800, deviceScaleFactor: 1, mobile: false });
    const loaded = once('Page.loadEventFired');
    await send('Page.navigate', { url: baseUrl + shot.url });
    await Promise.race([loaded, sleep(20000)]);
    await sleep(shot.wait || 1500);
    await evaluate(PRELUDE);
    // Chờ bảng tải ngầm (@deferredRows, DataTables AJAX) đổ xong dữ liệu - trước khi bấm nút trên dòng và trước khi chụp
    const settle = async () => {
        for (let i = 0; i < 40 && await evaluate(`/Đang tải dữ liệu|Loading\.\.\.|Processing/.test(document.body.innerText)`); i++) await sleep(500);
        await sleep(300);
    };
    if (shot.tab?.length) { await evaluate(`__ug.tab(${JSON.stringify(shot.tab)})`); await sleep(1500); }
    await settle();
    if (shot.js) { await evaluate(shot.js); await sleep(shot.afterJs || 1200); await settle(); }
    if (shot.probe) console.log('probe', shot.name, JSON.stringify(await evaluate(shot.probe)));
    for (const sel of shot.highlight || []) await evaluate(`document.querySelectorAll(${JSON.stringify(sel)}).forEach(e => e.classList.add('ug-hl'))`);

    // Không chụp ngoài khung nhìn được (xem ghi chú captureBeyondViewport) nên cuộn vùng cần chụp lên đầu
    await evaluate(`(() => { const e = [...document.querySelectorAll(${JSON.stringify(shot.clip || 'body')})].find(x => x.getClientRects().length);
        if (e && e.getBoundingClientRect().bottom > innerHeight) e.scrollIntoView({ block: 'start' }); })()`);
    await sleep(400);
    const rect = await evaluate(`(() => {
        const els = [...document.querySelectorAll(${JSON.stringify(shot.clip || 'body')})].filter(e => e.getClientRects().length);
        if (!els.length) return null;
        const r = els.map(e => e.getBoundingClientRect());
        return { x: Math.min(...r.map(a => a.left)) + scrollX, y: Math.min(...r.map(a => a.top)) + scrollY,
                 r: Math.max(...r.map(a => a.right)) + scrollX, b: Math.max(...r.map(a => a.bottom)) + scrollY };
    })()`);
    if (!rect || rect.r - rect.x < 20 || rect.b - rect.y < 20) { result.missing.push(shot.name); console.log('MISSING', shot.name, shot.clip); continue; }

    const pad = shot.pad ?? 12;
    const clip = { x: Math.max(0, rect.x - pad), y: Math.max(0, rect.y - pad), width: rect.r - rect.x + 2 * pad,
        height: Math.min(shot.maxHeight || 1400, rect.b - rect.y + 2 * pad), scale: 1 };
    // Khung nhìn đặt cao sẵn (1800px): KHÔNG dùng captureBeyondViewport vì nới khung nhìn lúc chụp
    // phát sự kiện resize làm DataTables responsive thu hết cột
    const png = await send('Page.captureScreenshot', { format: 'png', clip, captureBeyondViewport: false });
    writeFileSync(join(outDir, shot.name + '.png'), Buffer.from(png.result.data, 'base64'));
    result.ok.push(shot.name);
    console.log('ok', shot.name, Math.round(clip.width) + 'x' + Math.round(clip.height));
}

ws.close();
chrome.kill();
await sleep(500);
try { rmSync(profile, { recursive: true, force: true }); } catch {}
console.log(`Xong: ${result.ok.length} ảnh, thiếu ${result.missing.length}${result.missing.length ? ' (' + result.missing.join(', ') + ')' : ''}`);
