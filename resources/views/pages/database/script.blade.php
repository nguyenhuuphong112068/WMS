@verbatim
<script>
document.addEventListener('DOMContentLoaded', function () {
    'use strict';

    var DATA = JSON.parse(document.getElementById('db-data').textContent);
    var CFG = JSON.parse(document.getElementById('db-config').textContent);

    /*
    |--------------------------------------------------------------------------
    | Tiện ích
    |--------------------------------------------------------------------------
    */

    function byId(id) {
        return document.getElementById(id);
    }

    function esc(s) {
        return String(s === null || s === undefined ? '' : s).replace(/[&<>"']/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
    }

    function fmtNum(n) {
        return Number(n || 0).toLocaleString('vi-VN');
    }

    function fmtBytes(b) {
        if (b >= 1048576) return (b / 1048576).toLocaleString('vi-VN', { maximumFractionDigits: 1 }) + ' MB';
        if (b >= 1024) return Math.round(b / 1024).toLocaleString('vi-VN') + ' KB';
        return fmtNum(b) + ' B';
    }

    /** 2026_04_18_072901_create_x -> 18/04/2026 */
    function migDate(name, withTime) {
        var m = /^(\d{4})_(\d{2})_(\d{2})_(\d{2})(\d{2})/.exec(name || '');
        if (!m) return '';
        return m[3] + '/' + m[2] + '/' + m[1] + (withTime ? ' ' + m[4] + ':' + m[5] : '');
    }

    function migLabel(name) {
        return String(name || '').replace(/^\d{4}_\d{2}_\d{2}_\d{6}_/, '');
    }

    function highlight(text, q) {
        if (!q) return esc(text);
        var i = text.toLowerCase().indexOf(q);
        if (i < 0) return esc(text);
        return esc(text.slice(0, i)) + '<mark>' + esc(text.slice(i, i + q.length)) + '</mark>' + esc(text.slice(i + q.length));
    }

    function copyText(text) {
        if (navigator.clipboard && window.isSecureContext) {
            return navigator.clipboard.writeText(text);
        }
        var ta = document.createElement('textarea');
        ta.value = text;
        ta.style.position = 'fixed';
        ta.style.opacity = '0';
        document.body.appendChild(ta);
        ta.select();
        try { document.execCommand('copy'); } catch (e) { /* trình duyệt chặn */ }
        document.body.removeChild(ta);
        return Promise.resolve();
    }

    function toast(text) {
        if (window.Swal) {
            Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: text, showConfirmButton: false, timer: 1600 });
        }
    }

    var SRC = {
        fk: { label: 'Khoá ngoại', icon: 'fa-key' },
        join: { label: 'Join', icon: 'fa-code' },
        model: { label: 'Model', icon: 'fa-cube' },
        name: { label: 'Theo tên trường', icon: 'fa-font' }
    };
    var SRC_ORDER = ['fk', 'join', 'model', 'name'];

    var CARD = { n1: 'Nhiều – một', '11': 'Một – một', nn: 'Join ngang' };

    var REF_KIND = {
        table: 'DB::table', join: 'join', from: 'from', schema: 'Schema', guard: 'hasTable',
        validate: 'validate', model: 'Model', sql: 'SQL'
    };

    var OP = { create: 'Tạo', alter: 'Sửa', rename: 'Đổi tên', drop: 'Xoá', data: 'Dữ liệu' };

    var GROUP = {};
    var GROUP_ORDER = {};
    DATA.groups.forEach(function (g, i) {
        GROUP[g.key] = g.label;
        GROUP_ORDER[g.key] = i;
    });

    /*
    |--------------------------------------------------------------------------
    | Chỉ mục dữ liệu
    |--------------------------------------------------------------------------
    */

    var T = {};
    DATA.tables.forEach(function (t) {
        T[t.name] = t;
        t.colIndex = {};
        t.columns.forEach(function (c, i) { t.colIndex[c.name] = i; });
    });

    var OUT = {};
    var IN = {};
    var FK_COLS = {};
    DATA.edges.forEach(function (e) {
        (OUT[e.from] = OUT[e.from] || []).push(e);
        (IN[e.to] = IN[e.to] || []).push(e);
        if (e.card !== 'nn') FK_COLS[e.from + '.' + e.fc] = true;
    });

    function degree(name) {
        return (OUT[name] || []).length + (IN[name] || []).length;
    }

    function sources(e) {
        return SRC_ORDER.filter(function (k) { return e.src[k] > 0; });
    }

    function primarySrc(e) {
        return sources(e)[0] || 'name';
    }

    // Cảnh báo gắn vào từng trường: hiện icon ngay ở dòng trường trong bảng chi tiết
    var COL_ISSUES = {};
    DATA.issues.forEach(function (i) {
        if (['missing_index', 'type_mismatch', 'missing_column', 'unresolved_id', 'no_migration_column'].indexOf(i.type) >= 0) {
            (COL_ISSUES[i.key] = COL_ISSUES[i.key] || []).push(i);
        }
    });

    function refHtml(ref, short) {
        // short: chỉ tên file; 'line': chỉ số dòng (khi đã đứng dưới tên file)
        var text = short === 'line' ? 'dòng ' + ref[1] : esc(short ? ref[0].split('/').pop() : ref[0]) + ':' + ref[1];
        return CFG.editor
            ? '<a class="db-ref db-mono" href="' + esc(CFG.editor + ref[0]) + ':' + ref[1] + '" title="' + esc(ref[0]) + ' - mở trong VS Code">' + text + '</a>'
            : '<span class="db-ref db-mono" title="' + esc(ref[0]) + '">' + text + '</span>';
    }

    function refsHtml(refs, limit, short) {
        if (!refs || !refs.length) return '<span class="db-ref">—</span>';
        var one = function (r) { return refHtml(r, short); };
        var shown = refs.slice(0, limit).map(one).join('');
        var rest = refs.length - limit;
        if (rest > 0) {
            shown += '<span class="db-refs-more" hidden>' + refs.slice(limit).map(one).join('') + '</span>'
                + '<button type="button" class="db-more" data-more>+' + rest + ' chỗ</button>';
        }
        return '<div class="db-refs">' + shown + '</div>';
    }

    function srcBadges(e) {
        return sources(e).map(function (k) {
            var n = k === 'join' || k === 'model' ? e.src[k] : 0;
            return '<span class="db-badge ' + (k === 'name' ? 'is-muted' : '') + '"><i class="fas ' + SRC[k].icon + '"></i>'
                + SRC[k].label + (n > 1 ? ' ×' + n : '') + '</span>';
        }).join(' ');
    }

    function rowsText(t) {
        return t.rows ? '≈' + fmtNum(t.rows) : '0';
    }

    /*
    |--------------------------------------------------------------------------
    | Trạng thái + đường dẫn (#tab=...&t=...) để tải lại trang vẫn đứng đúng chỗ
    |--------------------------------------------------------------------------
    */

    var mostOut = DATA.tables.slice().sort(function (a, b) {
        return (OUT[b.name] || []).length - (OUT[a.name] || []).length;
    })[0];

    var state = { tab: 'tables', table: null, mode: 'overview', focus: mostOut ? mostOut.name : null, depth: 1 };

    (function readHash() {
        var p = new URLSearchParams(location.hash.slice(1));
        if (p.get('tab')) state.tab = p.get('tab');
        if (p.get('t') && T[p.get('t')]) state.table = p.get('t');
        if (p.get('m') === 'focus' || p.get('m') === 'overview') state.mode = p.get('m');
        if (p.get('f') && T[p.get('f')]) state.focus = p.get('f');
        if (p.get('d') === '2') state.depth = 2;
    })();

    function writeHash() {
        var p = new URLSearchParams();
        p.set('tab', state.tab);
        if (state.table) p.set('t', state.table);
        p.set('m', state.mode);
        if (state.focus) p.set('f', state.focus);
        p.set('d', String(state.depth));
        history.replaceState(null, '', '#' + p.toString());
    }

    /*
    |--------------------------------------------------------------------------
    | Tab
    |--------------------------------------------------------------------------
    */

    var tabs = document.querySelectorAll('.db-tab');
    var panes = document.querySelectorAll('.db-pane');

    function showTab(tab) {
        if (!document.querySelector('.db-pane[data-pane="' + tab + '"]')) tab = 'tables';
        state.tab = tab;

        tabs.forEach(function (b) { b.classList.toggle('is-active', b.dataset.tab === tab); });
        panes.forEach(function (p) { p.hidden = p.dataset.pane !== tab; });

        if (tab === 'diagram') renderDiagram();
        if (tab === 'relations' && relTable) relTable.columns.adjust();
        if (tab === 'migrations' && migTable) migTable.columns.adjust();

        writeHash();
    }

    tabs.forEach(function (b) {
        b.addEventListener('click', function () { showTab(b.dataset.tab); });
    });

    /*
    |--------------------------------------------------------------------------
    | Tab Bảng: danh sách bên trái
    |--------------------------------------------------------------------------
    */

    var listEl = byId('db-table-list');
    var searchEl = byId('db-search');
    var groupEl = byId('db-group');

    function renderList() {
        var q = searchEl.value.trim().toLowerCase();
        var g = groupEl.value;
        var html = '';

        DATA.groups.forEach(function (grp) {
            if (g && grp.key !== g) return;

            var rows = [];
            DATA.tables.forEach(function (t) {
                if (t.group !== grp.key) return;

                var nameHit = !q || t.name.toLowerCase().indexOf(q) >= 0;
                var colHits = q ? t.columns.filter(function (c) { return c.name.toLowerCase().indexOf(q) >= 0; }) : [];
                if (!nameHit && !colHits.length) return;

                var hits = colHits.slice(0, 4).map(function (c) { return highlight(c.name, q); }).join(', ');
                rows.push(
                    '<button type="button" class="db-list-item' + (state.table === t.name ? ' is-active' : '') + '" data-table="' + esc(t.name) + '">'
                    + '<div class="name db-mono">' + highlight(t.name, q) + '</div>'
                    + '<div class="meta">' + t.columns.length + ' trường · ' + rowsText(t) + ' dòng · ' + degree(t.name) + ' quan hệ</div>'
                    + (colHits.length ? '<div class="hit"><i class="fas fa-columns mr-1"></i>' + hits + (colHits.length > 4 ? ' …' : '') + '</div>' : '')
                    + '</button>'
                );
            });

            if (rows.length) {
                html += '<div class="db-list-group"><i class="db-dot db-g-' + grp.key + '"></i>' + esc(grp.label) + '</div>' + rows.join('');
            }
        });

        listEl.innerHTML = html || '<div class="db-empty">Không tìm thấy bảng phù hợp</div>';
    }

    var searchTimer = null;
    searchEl.addEventListener('input', function () {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(renderList, 120);
    });
    groupEl.addEventListener('change', renderList);

    listEl.addEventListener('click', function (ev) {
        var item = ev.target.closest('.db-list-item');
        if (!item) return;

        // Đang tìm theo tên trường: mở bảng và nhảy tới trường đầu tiên khớp
        var q = searchEl.value.trim().toLowerCase();
        var t = T[item.dataset.table];
        var col = null;
        if (q && t && t.name.toLowerCase().indexOf(q) < 0) {
            var hit = t.columns.filter(function (c) { return c.name.toLowerCase().indexOf(q) >= 0; })[0];
            col = hit ? hit.name : null;
        }
        openTable(item.dataset.table, col);
    });

    /*
    |--------------------------------------------------------------------------
    | Tab Bảng: chi tiết một bảng
    |--------------------------------------------------------------------------
    */

    var detailEl = byId('db-detail');
    var rowCache = {};

    function openTable(name, col) {
        if (!T[name]) return;
        if (state.tab !== 'tables') showTab('tables');
        renderDetail(name);

        listEl.querySelectorAll('.db-list-item').forEach(function (b) {
            b.classList.toggle('is-active', b.dataset.table === name);
        });

        if (col) {
            var row = detailEl.querySelector('tr[data-col="' + CSS.escape(col) + '"]');
            if (row) {
                row.scrollIntoView({ block: 'center', behavior: 'smooth' });
                row.classList.remove('is-flash');
                void row.offsetWidth;
                row.classList.add('is-flash');
            }
        } else if (detailEl.getBoundingClientRect().top < 0) {
            detailEl.scrollIntoView({ block: 'start', behavior: 'smooth' });
        }
    }

    function shortDefault(c) {
        if (c.default === null || c.default === undefined) return '';
        if (c.default === 'NULL') return '<span class="muted">NULL</span>';
        return esc(c.default);
    }

    function columnFlags(t, c) {
        var list = COL_ISSUES[t.name + '.' + c.name] || [];
        return list.map(function (i) {
            var meta = ISSUE[i.type];
            var cls = i.level === 'danger' ? 'is-danger' : (i.level === 'warning' ? 'is-warn' : 'muted');
            var title = meta.title + (i.detail ? ': ' + i.detail : (i.target ? ' → ' + i.target : ''));
            return '<i class="fas ' + meta.icon + ' db-flag ' + cls + '" title="' + esc(title) + '"></i>';
        }).join('');
    }

    function renderDetail(name) {
        var t = T[name];
        state.table = name;
        writeHash();

        var outE = (OUT[name] || []).filter(function (e) { return e.card !== 'nn'; });
        var inE = (IN[name] || []).filter(function (e) { return e.card !== 'nn'; });
        var peerE = (OUT[name] || []).concat(IN[name] || []).filter(function (e) { return e.card === 'nn'; });

        var colOut = {};
        outE.forEach(function (e) { (colOut[e.fc] = colOut[e.fc] || []).push(e); });
        peerE.forEach(function (e) {
            var mine = e.from === name ? e.fc : e.tc;
            (colOut[mine] = colOut[mine] || []).push(e);
        });
        var colIn = {};
        inE.forEach(function (e) { (colIn[e.tc] = colIn[e.tc] || []).push(e); });

        var idxCols = {};
        t.indexes.forEach(function (i) {
            i.columns.forEach(function (c, pos) {
                (idxCols[c] = idxCols[c] || []).push({ index: i, pos: pos });
            });
        });

        var html = '';

        // --- Đầu ---
        html += '<div class="db-detail-head">'
            + '<h2 class="db-mono">' + esc(t.name) + '</h2>'
            + '<span class="db-badge db-g-' + t.group + '"><i class="db-dot db-g-' + t.group + '"></i>' + esc(GROUP[t.group]) + '</span>'
            + (t.type === 'view' ? '<span class="db-badge is-muted">VIEW</span>' : '')
            + '<div class="actions">'
            + '<button type="button" class="btn btn-sm btn-outline-primary db-btn" data-copy="' + esc(t.name) + '"><i class="far fa-copy mr-1"></i> Sao chép tên</button>'
            + '<button type="button" class="btn btn-sm btn-primary db-btn" data-erd="' + esc(t.name) + '"><i class="fas fa-project-diagram mr-1"></i> Xem sơ đồ</button>'
            + '</div></div>';

        if (t.comment) {
            html += '<div class="db-section muted"><i class="far fa-comment-dots mr-1"></i>' + esc(t.comment) + '</div>';
        }

        // --- Thông số ---
        var codeFiles = t.usage.files.filter(function (f) { return !f.seed; }).length;
        html += '<div class="db-facts">'
            + fact('Trường', fmtNum(t.columns.length))
            + fact('Số dòng', '<span id="db-rowcount">' + rowsText(t) + '</span>')
            + fact('Dung lượng', fmtBytes(t.size))
            + fact('Engine', esc(t.engine || '—'))
            + fact('Tham chiếu tới / bởi', outE.length + ' / ' + inE.length)
            + fact('Code sử dụng', t.usage.code ? fmtNum(t.usage.code) + ' chỗ · ' + codeFiles + ' file' : '<span class="text-danger">Chưa thấy</span>')
            + fact('Migration tạo', t.created ? '<span title="' + esc(t.created) + '">' + migDate(t.created) + '</span>' : '—')
            + '</div>';

        // --- Trường ---
        html += '<div class="db-section"><h3><i class="fas fa-columns"></i>Trường dữ liệu<span class="db-count">' + t.columns.length + '</span></h3>'
            + '<div class="table-responsive"><table class="db-grid"><thead><tr>'
            + '<th>#</th><th>Trường</th><th>Kiểu</th><th>Null</th><th>Mặc định</th><th>Khoá</th><th>Liên kết</th><th>Migration</th>'
            + '</tr></thead><tbody>';

        t.columns.forEach(function (c, i) {
            var keys = [];
            if (c.key === 'PRI') keys.push('<span class="db-key pk" title="Khoá chính">PK</span>');
            (idxCols[c.name] || []).forEach(function (x) {
                if (x.index.primary) return;
                var single = x.index.columns.length === 1;
                if (x.index.unique && single) keys.push('<span class="db-key uq" title="' + esc(x.index.name) + '">UQ</span>');
                else keys.push('<span class="db-key" title="' + esc(x.index.name + ' (' + x.index.columns.join(', ') + ')') + '">'
                    + (x.index.unique ? 'UQ' : 'IDX') + (single ? '' : ' ' + (x.pos + 1)) + '</span>');
            });
            if (/auto_increment/i.test(c.extra)) keys.push('<span class="db-key" title="Tự tăng">AI</span>');
            if (FK_COLS[t.name + '.' + c.name]) keys.push('<span class="db-key uq" title="Giữ khoá liên kết bảng khác">FK</span>');

            var links = (colOut[c.name] || []).map(function (e) {
                var nn = e.card === 'nn';
                var otherT = nn ? (e.from === name ? e.to : e.from) : e.to;
                var otherC = nn ? (e.from === name ? e.tc : e.fc) : e.tc;
                return '<a class="db-link src-' + primarySrc(e) + '" data-goto="' + esc(otherT) + '" data-col="' + esc(otherC) + '" title="' + esc(sources(e).map(function (k) { return SRC[k].label; }).join(', ')) + '">'
                    + (nn ? '⇄ ' : '→ ') + '<span class="db-mono">' + esc(otherT + '.' + otherC) + '</span></a>';
            });
            if (colIn[c.name]) {
                links.push('<a class="db-link" data-scroll="db-sec-in" title="Các bảng tham chiếu tới trường này">← ' + colIn[c.name].length + ' bảng</a>');
            }

            var mig = c.added
                ? '<span title="' + esc(c.added + (c.changed.length ? '\nSửa: ' + c.changed.join('\n') : '')) + '">' + migDate(c.added)
                    + (c.changed.length ? ' <span class="db-badge is-muted">sửa ' + c.changed.length + '</span>' : '') + '</span>'
                : '<span class="muted">—</span>';

            html += '<tr data-col="' + esc(c.name) + '">'
                + '<td class="num">' + (i + 1) + '</td>'
                + '<td class="col-name db-mono">' + esc(c.name) + columnFlags(t, c)
                + (c.comment ? '<div class="db-colnote">' + esc(c.comment) + '</div>' : '') + '</td>'
                + '<td class="col-type db-mono">' + esc(c.type) + '</td>'
                + '<td>' + (c.nullable ? '<span class="muted">Có</span>' : '<b>Không</b>') + '</td>'
                + '<td class="db-mono">' + shortDefault(c) + '</td>'
                + '<td><div class="db-keys">' + keys.join('') + '</div></td>'
                + '<td><div class="db-links">' + links.join('') + '</div></td>'
                + '<td class="muted">' + mig + '</td>'
                + '</tr>';
        });
        html += '</tbody></table></div></div>';

        // --- Quan hệ ---
        html += relSection('db-sec-out', 'fa-sign-out-alt', 'Tham chiếu tới', outE, function (e) {
            return [e.fc, e.to, e.tc];
        });
        html += relSection('db-sec-in', 'fa-sign-in-alt', 'Được tham chiếu bởi', inE, function (e) {
            return [e.tc, e.from, e.fc];
        });
        if (peerE.length) {
            html += relSection('db-sec-peer', 'fa-exchange-alt', 'Join ngang', peerE, function (e) {
                return e.from === name ? [e.fc, e.to, e.tc] : [e.tc, e.from, e.fc];
            });
        }

        // --- Chỉ mục ---
        html += '<div class="db-section"><h3><i class="fas fa-bolt"></i>Chỉ mục<span class="db-count">' + t.indexes.length + '</span></h3>';
        if (t.indexes.length) {
            html += '<div class="table-responsive"><table class="db-grid"><thead><tr><th>Tên</th><th>Trường</th><th>Loại</th></tr></thead><tbody>'
                + t.indexes.map(function (i) {
                    var kind = i.primary ? '<span class="db-key pk">PRIMARY</span>' : (i.unique ? '<span class="db-key uq">UNIQUE</span>' : '<span class="db-key">INDEX</span>');
                    return '<tr><td class="db-mono">' + esc(i.name) + '</td><td class="db-mono">' + esc(i.columns.join(', ')) + '</td><td>' + kind + '</td></tr>';
                }).join('')
                + '</tbody></table></div>';
        } else {
            html += '<div class="muted">Bảng chưa có chỉ mục nào</div>';
        }
        html += '</div>';

        // --- Code sử dụng ---
        html += '<div class="db-section"><h3><i class="fas fa-file-code"></i>Sử dụng trong code<span class="db-count">' + t.usage.files.length + ' file</span></h3>';
        if (t.usage.files.length) {
            html += '<div class="db-files">' + t.usage.files.map(function (f) {
                var refs = f.lines.map(function (l) { return [f.file, l]; });
                return '<div class="db-file"><div class="db-file-head">'
                    + '<span class="db-mono">' + esc(f.file) + '</span>'
                    + (f.seed ? '<span class="db-badge is-muted">Seeder</span>' : '')
                    + f.kinds.map(function (k) { return '<span class="db-badge">' + esc(REF_KIND[k] || k) + '</span>'; }).join('')
                    + '</div>' + refsHtml(refs, 12, 'line') + '</div>';
            }).join('') + '</div>';
        } else {
            html += '<div class="muted">Không tìm thấy chỗ nào trong code dùng trực tiếp tên bảng này</div>';
        }
        html += '</div>';

        // --- Lịch sử migration ---
        html += '<div class="db-section"><h3><i class="fas fa-code-branch"></i>Lịch sử migration<span class="db-count">' + t.migrations.length + '</span></h3>';
        if (t.migrations.length) {
            html += '<div class="table-responsive"><table class="db-grid"><thead><tr><th>Ngày</th><th>Migration</th><th>Thao tác</th></tr></thead><tbody>'
                + t.migrations.map(function (m) {
                    return '<tr><td class="muted nowrap">' + migDate(m.name, true) + '</td>'
                        + '<td class="db-mono" title="' + esc(m.name) + '">' + esc(migLabel(m.name)) + '</td>'
                        + '<td><div class="db-ops">' + m.ops.map(function (op) {
                            return '<span class="db-op op-' + op + '">' + esc(OP[op] || op) + '</span>';
                        }).join('') + '</div></td></tr>';
                }).join('')
                + '</tbody></table></div>';
        } else {
            html += '<div class="muted">Không xác định được migration nào tác động bảng này</div>';
        }
        html += '</div>';

        detailEl.innerHTML = html;
        loadRows(name);
    }

    function fact(label, value) {
        return '<div class="db-fact"><span>' + label + '</span><b>' + value + '</b></div>';
    }

    function relSection(id, icon, title, edges, pick) {
        var html = '<div class="db-section" id="' + id + '"><h3><i class="fas ' + icon + '"></i>' + title + '<span class="db-count">' + edges.length + '</span></h3>';
        if (!edges.length) {
            return html + '<div class="muted">Không có</div></div>';
        }

        var sorted = edges.slice().sort(function (a, b) {
            var pa = pick(a), pb = pick(b);
            return (pa[0] + pa[1]).localeCompare(pb[0] + pb[1]);
        });

        html += '<div class="table-responsive"><table class="db-grid"><thead><tr><th>Trường</th><th>Liên kết</th><th>Nguồn</th><th>Vị trí trong code</th></tr></thead><tbody>';
        sorted.forEach(function (e) {
            var p = pick(e);
            html += '<tr><td class="col-name db-mono">' + esc(p[0]) + '</td>'
                + '<td><span class="db-goto db-mono" data-goto="' + esc(p[1]) + '" data-col="' + esc(p[2]) + '">' + esc(p[1] + '.' + p[2]) + '</span>'
                + ' <span class="db-dot db-g-' + (T[p[1]] ? T[p[1]].group : 'other') + '"></span></td>'
                + '<td>' + srcBadges(e) + '</td>'
                + '<td>' + refsHtml(e.refs, 3, true) + '</td></tr>';
        });
        return html + '</tbody></table></div></div>';
    }

    function loadRows(name) {
        function show(n) {
            var el = byId('db-rowcount');
            if (el && state.table === name) el.textContent = fmtNum(n);
        }

        if (rowCache[name] !== undefined) return show(rowCache[name]);

        fetch(CFG.rowsUrl + '?table=' + encodeURIComponent(name), {
            credentials: 'same-origin',
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        })
            .then(function (r) { return r.ok ? r.json() : Promise.reject(r.status); })
            .then(function (d) {
                rowCache[name] = d.rows;
                show(d.rows);
            })
            .catch(function () { /* giữ số ước lượng */ });
    }

    /*
    |--------------------------------------------------------------------------
    | Thao tác chung (uỷ quyền sự kiện)
    |--------------------------------------------------------------------------
    */

    document.getElementById('dbPage').addEventListener('click', function (ev) {
        var el;

        if ((el = ev.target.closest('[data-goto]'))) {
            ev.preventDefault();
            openTable(el.dataset.goto, el.dataset.col || null);
            return;
        }

        if ((el = ev.target.closest('[data-more]'))) {
            var box = el.previousElementSibling;
            if (box) box.hidden = false;
            el.remove();
            return;
        }

        if ((el = ev.target.closest('[data-scroll]'))) {
            var target = byId(el.dataset.scroll);
            if (target) target.scrollIntoView({ block: 'start', behavior: 'smooth' });
            return;
        }

        if ((el = ev.target.closest('[data-copy]'))) {
            copyText(el.dataset.copy).then(function () { toast('Đã sao chép ' + el.dataset.copy); });
            return;
        }

        if ((el = ev.target.closest('[data-erd]'))) {
            state.mode = 'focus';
            state.focus = el.dataset.erd;
            showTab('diagram');
        }
    });

    /*
    |--------------------------------------------------------------------------
    | Sơ đồ quan hệ
    |--------------------------------------------------------------------------
    */

    var svg = byId('erd-svg');
    var root = byId('erd-root');
    var board = byId('erd-board');
    var boardInner = byId('erd-board-inner');
    var stage = byId('erd-stage');
    var tip = byId('erd-tip');
    var tableSel = byId('erd-table');
    var allColsEl = byId('erd-all-cols');
    var srcBoxes = document.querySelectorAll('.db-erd-bar [data-src]');

    var view = { k: 1, x: 0, y: 0 };
    var layout = null;
    var renderedKey = null;

    var HEAD_H = 30;
    var ROW_H = 21;
    var PAD_B = 6;
    var GAP_X = 150;
    var GAP_Y = 18;
    var SUB_GAP = 36;
    var MAX_COL_H = 1500;

    function srcFilter() {
        var f = {};
        srcBoxes.forEach(function (b) { f[b.dataset.src] = b.checked; });
        return f;
    }

    function edgeVisible(e, f) {
        if (e.card === 'nn') return f.nn;
        if (e.src.fk) return true;
        return (e.src.join && f.join) || (e.src.model && f.model) || (e.src.name && f.name);
    }

    function shortType(c) {
        if (/int$/.test(c.dtype)) return c.dtype + (/unsigned/.test(c.type) ? ' ⁺' : '');
        return c.type.length > 16 ? c.dtype : c.type;
    }

    function syncControls() {
        document.querySelectorAll('#erd-mode button').forEach(function (b) {
            b.classList.toggle('is-active', b.dataset.mode === state.mode);
        });
        document.querySelectorAll('#erd-depth button').forEach(function (b) {
            b.classList.toggle('is-active', Number(b.dataset.depth) === state.depth);
        });
        document.querySelectorAll('[data-focus-only]').forEach(function (el) {
            el.style.display = state.mode === 'focus' ? '' : 'none';
        });
        if (state.focus) {
            tableSel.value = state.focus;
            if (window.jQuery && jQuery.fn.select2) jQuery(tableSel).trigger('change.select2');
        }
    }

    function renderDiagram(force) {
        syncControls();
        hideTip();

        var key = [state.mode, state.focus, state.depth, allColsEl.checked, JSON.stringify(srcFilter())].join('|');
        if (!force && key === renderedKey) return;
        renderedKey = key;

        if (state.mode === 'overview') {
            svg.style.display = 'none';
            board.hidden = false;
            renderOverview();
        } else {
            board.hidden = true;
            svg.style.display = '';
            renderFocus();
            fitView();
        }
        writeHash();
    }

    // --- Theo bảng: bảng đang xem ở giữa, bảng được tham chiếu bên trái, bảng tham chiếu tới bên phải ---
    function renderFocus() {
        var focus = state.focus;
        if (!T[focus]) {
            root.innerHTML = '';
            layout = null;
            return;
        }

        var f = srcFilter();
        var edges = DATA.edges.filter(function (e) { return edgeVisible(e, f); });

        var out = {}, inn = {}, peer = {};
        edges.forEach(function (e) {
            if (e.card === 'nn') {
                (peer[e.from] = peer[e.from] || []).push(e);
                (peer[e.to] = peer[e.to] || []).push(e);
                return;
            }
            (out[e.from] = out[e.from] || []).push(e);
            (inn[e.to] = inn[e.to] || []).push(e);
        });

        var level = {};
        level[focus] = 0;

        var frontier = [focus];
        var d, next;
        for (d = 1; d <= state.depth; d++) {
            next = [];
            frontier.forEach(function (t) {
                (out[t] || []).forEach(function (e) {
                    if (!(e.to in level)) { level[e.to] = -d; next.push(e.to); }
                });
            });
            frontier = next;
        }

        frontier = [focus];
        for (d = 1; d <= state.depth; d++) {
            next = [];
            frontier.forEach(function (t) {
                (inn[t] || []).forEach(function (e) {
                    if (!(e.from in level)) { level[e.from] = d; next.push(e.from); }
                });
                (peer[t] || []).forEach(function (e) {
                    var o = e.from === t ? e.to : e.from;
                    if (!(o in level)) { level[o] = d; next.push(o); }
                });
            });
            frontier = next;
        }

        var nodes = Object.keys(level);
        var shown = edges.filter(function (e) { return (e.from in level) && (e.to in level); });

        // Trường hiện trên từng thẻ: bảng đang xem hiện đủ, bảng khác chỉ hiện khoá + trường có nối
        var used = {};
        shown.forEach(function (e) {
            (used[e.from] = used[e.from] || {})[e.fc] = true;
            (used[e.to] = used[e.to] || {})[e.tc] = true;
        });

        var cols = {};
        var width = {};
        nodes.forEach(function (n) {
            var t = T[n];
            var list = (n === focus || allColsEl.checked)
                ? t.columns
                : t.columns.filter(function (c) { return c.key === 'PRI' || (used[n] && used[n][c.name]); });
            cols[n] = list;

            var w = n.length * 7.6 + 50;
            list.forEach(function (c) {
                w = Math.max(w, c.name.length * 6.9 + shortType(c).length * 6.2 + 52);
            });
            width[n] = Math.min(380, Math.max(210, Math.ceil(w)));
        });

        // Thứ tự trong từng cột: cấp 1 theo nhóm + tên, cấp 2 theo vị trí trung bình của bảng nối ở cấp 1
        var byLevel = {};
        nodes.forEach(function (n) { (byLevel[level[n]] = byLevel[level[n]] || []).push(n); });

        var order = {};
        var neighbors = {};
        shown.forEach(function (e) {
            (neighbors[e.from] = neighbors[e.from] || []).push(e.to);
            (neighbors[e.to] = neighbors[e.to] || []).push(e.from);
        });

        [0, -1, 1, -2, 2].forEach(function (L) {
            var arr = byLevel[L];
            if (!arr) return;

            if (Math.abs(L) <= 1) {
                arr.sort(function (a, b) {
                    return (GROUP_ORDER[T[a].group] - GROUP_ORDER[T[b].group]) || a.localeCompare(b);
                });
            } else {
                var inner = L > 0 ? L - 1 : L + 1;
                var bc = {};
                arr.forEach(function (n) {
                    var idx = (neighbors[n] || []).filter(function (m) { return level[m] === inner; }).map(function (m) { return order[m]; });
                    bc[n] = idx.length ? idx.reduce(function (s, v) { return s + v; }, 0) / idx.length : 1e9;
                });
                arr.sort(function (a, b) { return (bc[a] - bc[b]) || a.localeCompare(b); });
            }
            arr.forEach(function (n, i) { order[n] = i; });
        });

        var levels = Object.keys(byLevel).map(Number).sort(function (a, b) { return a - b; });
        var pos = {};
        var x = 0;

        levels.forEach(function (L) {
            var arr = byLevel[L];
            var heights = arr.map(function (n) { return HEAD_H + cols[n].length * ROW_H + PAD_B; });
            var total = heights.reduce(function (s, h) { return s + h + GAP_Y; }, -GAP_Y);
            var sub = Math.max(1, Math.ceil(total / MAX_COL_H));
            var per = Math.ceil(arr.length / sub);

            for (var s = 0; s < sub; s++) {
                var slice = arr.slice(s * per, (s + 1) * per);
                var hs = heights.slice(s * per, (s + 1) * per);
                var colW = slice.reduce(function (m, n) { return Math.max(m, width[n]); }, 0);
                var h = hs.reduce(function (sum, v) { return sum + v + GAP_Y; }, -GAP_Y);
                var y = -h / 2;

                slice.forEach(function (n, i) {
                    pos[n] = { x: x + (colW - width[n]) / 2, y: y, w: width[n], h: hs[i] };
                    y += hs[i] + GAP_Y;
                });
                x += colW + (s < sub - 1 ? SUB_GAP : GAP_X);
            }
        });

        function anchorY(t, col) {
            var p = pos[t];
            var idx = -1;
            cols[t].some(function (c, i) {
                if (c.name === col) { idx = i; return true; }
                return false;
            });
            return idx < 0 ? p.y + HEAD_H / 2 : p.y + HEAD_H + idx * ROW_H + ROW_H / 2;
        }

        var parts = [];

        shown.forEach(function (e) {
            var a = pos[e.from], b = pos[e.to];
            var ya = anchorY(e.from, e.fc), yb = anchorY(e.to, e.tc);
            var x1, x2, d1, d2, path;

            if (e.from === e.to || Math.abs(a.x - b.x) < 1) {
                x1 = a.x + a.w; x2 = b.x + b.w; d1 = 1; d2 = 1;
                var bulge = 46 + Math.abs(ya - yb) * 0.12;
                path = 'M' + x1 + ',' + ya + ' C' + (x1 + bulge) + ',' + ya + ' ' + (x2 + bulge) + ',' + yb + ' ' + x2 + ',' + yb;
            } else {
                if (a.x < b.x) { x1 = a.x + a.w; x2 = b.x; d1 = 1; d2 = -1; }
                else { x1 = a.x; x2 = b.x + b.w; d1 = -1; d2 = 1; }
                var dx = Math.max(50, Math.abs(x2 - x1) * 0.45);
                path = 'M' + x1 + ',' + ya + ' C' + (x1 + d1 * dx) + ',' + ya + ' ' + (x2 + d2 * dx) + ',' + yb + ' ' + x2 + ',' + yb;
            }

            // Chân gà ở bên "nhiều", vạch ở bên "một"
            var deco = '';
            if (e.card !== 'nn') {
                deco = ' M' + x1 + ',' + (ya - 5) + ' L' + (x1 + d1 * 10) + ',' + ya + ' L' + x1 + ',' + (ya + 5)
                    + ' M' + (x2 + d2 * 7) + ',' + (yb - 5) + ' L' + (x2 + d2 * 7) + ',' + (yb + 5);
                if (e.card === '11') {
                    deco = ' M' + (x1 + d1 * 7) + ',' + (ya - 5) + ' L' + (x1 + d1 * 7) + ',' + (ya + 5)
                        + ' M' + (x2 + d2 * 7) + ',' + (yb - 5) + ' L' + (x2 + d2 * 7) + ',' + (yb + 5);
                }
            }

            var cross = e.from !== focus && e.to !== focus && Math.abs(level[e.from] - level[e.to]) !== 1;
            var cls = 'erd-edge src-' + primarySrc(e) + (e.card === 'nn' ? ' card-nn' : '') + (cross ? ' is-cross' : '');

            parts.push('<g class="erd-e" data-e="' + e.id + '" data-from="' + esc(e.from) + '" data-to="' + esc(e.to) + '">'
                + '<path class="' + cls + '" d="' + path + deco + '"/>'
                + '<path class="erd-hit" d="' + path + '"/></g>');
        });

        nodes.forEach(function (n) {
            var p = pos[n];
            var t = T[n];
            var r = 8;
            var g = '<g class="erd-card db-g-' + t.group + (n === focus ? ' is-focus' : '') + '" data-t="' + esc(n) + '" transform="translate(' + p.x + ',' + p.y + ')">'
                + '<rect class="body" width="' + p.w + '" height="' + p.h + '" rx="' + r + '"/>'
                + '<path class="head" d="M0,' + r + ' a' + r + ',' + r + ' 0 0 1 ' + r + ',-' + r + ' h' + (p.w - 2 * r) + ' a' + r + ',' + r + ' 0 0 1 ' + r + ',' + r + ' v' + (HEAD_H - r) + ' h-' + p.w + ' z"/>'
                + '<text class="title db-mono" x="10" y="19.5" data-refocus="' + esc(n) + '">' + esc(n) + '<title>' + esc(n) + ' · ' + t.columns.length + ' trường · bấm để xem quan hệ của bảng này</title></text>'
                + '<g class="info" data-open="' + esc(n) + '"><title>Mở chi tiết bảng</title>'
                + '<circle cx="' + (p.w - 15) + '" cy="15" r="8" fill-opacity="0.25"/>'
                + '<text x="' + (p.w - 15) + '" y="19" text-anchor="middle" class="info-i">i</text></g>';

            cols[n].forEach(function (c, i) {
                var y = HEAD_H + i * ROW_H;
                var key = c.key === 'PRI' ? 'PK' : (FK_COLS[n + '.' + c.name] ? 'FK' : '');
                g += (i % 2 ? '<rect class="zebra" x="1" y="' + y + '" width="' + (p.w - 2) + '" height="' + ROW_H + '"/>' : '')
                    + (key ? '<text class="key" x="8" y="' + (y + 14) + '">' + key + '</text>' : '')
                    + '<text class="cname db-mono" x="30" y="' + (y + 14.5) + '">' + esc(c.name) + '</text>'
                    + '<text class="ctype db-mono" x="' + (p.w - 9) + '" y="' + (y + 14.5) + '" text-anchor="end">' + esc(shortType(c)) + '</text>';
            });

            parts.push(g + '</g>');
        });

        root.innerHTML = parts.join('');

        var minX = Infinity, minY = Infinity, maxX = -Infinity, maxY = -Infinity;
        nodes.forEach(function (n) {
            var p = pos[n];
            minX = Math.min(minX, p.x); minY = Math.min(minY, p.y);
            maxX = Math.max(maxX, p.x + p.w + 60); maxY = Math.max(maxY, p.y + p.h);
        });

        layout = { minX: minX, minY: minY, maxX: maxX, maxY: maxY, edges: shown, focus: focus };
    }

    function applyView() {
        root.setAttribute('transform', 'translate(' + view.x + ',' + view.y + ') scale(' + view.k + ')');
    }

    function fitView() {
        if (!layout) return;
        var W = stage.clientWidth, H = stage.clientHeight;
        var bw = layout.maxX - layout.minX, bh = layout.maxY - layout.minY;
        var k = Math.min((W - 60) / bw, (H - 60) / bh, 1);
        k = Math.max(0.12, k);
        view.k = k;
        view.x = (W - bw * k) / 2 - layout.minX * k;
        view.y = (H - bh * k) / 2 - layout.minY * k;
        applyView();
    }

    function zoomAt(factor, cx, cy) {
        var k2 = Math.max(0.08, Math.min(2.5, view.k * factor));
        view.x = cx - (cx - view.x) * (k2 / view.k);
        view.y = cy - (cy - view.y) * (k2 / view.k);
        view.k = k2;
        applyView();
    }

    svg.addEventListener('wheel', function (ev) {
        ev.preventDefault();
        var r = svg.getBoundingClientRect();
        zoomAt(ev.deltaY < 0 ? 1.12 : 1 / 1.12, ev.clientX - r.left, ev.clientY - r.top);
    }, { passive: false });

    document.querySelectorAll('[data-zoom]').forEach(function (b) {
        b.addEventListener('click', function () {
            if (b.dataset.zoom === 'fit') return fitView();
            zoomAt(b.dataset.zoom === 'in' ? 1.25 : 0.8, stage.clientWidth / 2, stage.clientHeight / 2);
        });
    });

    // Kéo để di chuyển; bấm (không kéo) vào tên bảng để đổi bảng trung tâm
    var drag = null;
    svg.addEventListener('pointerdown', function (ev) {
        if (ev.button !== 0) return;
        drag = { x: ev.clientX, y: ev.clientY, vx: view.x, vy: view.y, moved: false };
        svg.setPointerCapture(ev.pointerId);
    });
    svg.addEventListener('pointermove', function (ev) {
        if (!drag) return;
        var dx = ev.clientX - drag.x, dy = ev.clientY - drag.y;
        if (!drag.moved && Math.abs(dx) + Math.abs(dy) > 4) {
            drag.moved = true;
            svg.classList.add('is-panning');
            hideTip();
        }
        if (drag.moved) {
            view.x = drag.vx + dx;
            view.y = drag.vy + dy;
            applyView();
        }
    });
    svg.addEventListener('pointerup', function (ev) {
        var wasDrag = drag && drag.moved;
        drag = null;
        svg.classList.remove('is-panning');
        if (wasDrag) return;

        // Pointer capture đổi target về svg: tìm phần tử thật dưới con trỏ
        var target = document.elementFromPoint(ev.clientX, ev.clientY);
        if (!target) return;

        var open = target.closest('[data-open]');
        if (open) {
            openTable(open.dataset.open);
            return;
        }
        var refocus = target.closest('[data-refocus]');
        if (refocus && refocus.dataset.refocus !== state.focus) {
            state.focus = refocus.dataset.refocus;
            renderDiagram();
        }
    });
    svg.addEventListener('dblclick', function (ev) {
        var hit = document.elementFromPoint(ev.clientX, ev.clientY);
        var card = hit && hit.closest('.erd-card');
        if (card && !hit.closest('[data-refocus]')) openTable(card.dataset.t);
    });

    // Rê chuột: làm nổi bảng + các quan hệ của nó
    var hoverCard = null;
    svg.addEventListener('mouseover', function (ev) {
        if (drag && drag.moved) return;

        var edgeG = ev.target.closest('.erd-e');
        if (edgeG && ev.target.classList.contains('erd-hit')) {
            showEdgeTip(Number(edgeG.dataset.e), ev);
            erdHighlight([edgeG.dataset.from, edgeG.dataset.to], [edgeG]);
            return;
        }

        var card = ev.target.closest('.erd-card');
        if (card && card !== hoverCard) {
            hoverCard = card;
            var name = card.dataset.t;
            var groups = [];
            var tables = [name];
            root.querySelectorAll('.erd-e').forEach(function (g) {
                if (g.dataset.from === name || g.dataset.to === name) {
                    groups.push(g);
                    tables.push(g.dataset.from === name ? g.dataset.to : g.dataset.from);
                }
            });
            erdHighlight(tables, groups);
        }
    });
    svg.addEventListener('mouseout', function (ev) {
        var to = ev.relatedTarget;
        if (ev.target.classList.contains('erd-hit')) {
            hideTip();
            if (!to || !to.closest || !to.closest('.erd-card')) clearHighlight();
            return;
        }
        if (hoverCard && (!to || !to.closest || to.closest('.erd-card') !== hoverCard)) {
            hoverCard = null;
            clearHighlight();
        }
    });
    svg.addEventListener('mousemove', function (ev) {
        if (!tip.hidden) placeTip(ev);
    });

    function erdHighlight(tables, edgeGroups) {
        svg.classList.add('is-hover');
        root.querySelectorAll('.is-on').forEach(function (el) { el.classList.remove('is-on'); });
        tables.forEach(function (t) {
            var c = root.querySelector('.erd-card[data-t="' + CSS.escape(t) + '"]');
            if (c) c.classList.add('is-on');
        });
        edgeGroups.forEach(function (g) {
            g.querySelector('.erd-edge').classList.add('is-on');
        });
    }

    function clearHighlight() {
        svg.classList.remove('is-hover');
        root.querySelectorAll('.is-on').forEach(function (el) { el.classList.remove('is-on'); });
    }

    var EDGE_BY_ID = {};
    DATA.edges.forEach(function (e) { EDGE_BY_ID[e.id] = e; });

    function showEdgeTip(id, ev) {
        var e = EDGE_BY_ID[id];
        if (!e) return;
        var refs = e.refs.slice(0, 3).map(function (r) { return '<div class="r db-mono">' + esc(r[0]) + ':' + r[1] + '</div>'; }).join('');
        tip.innerHTML = '<div class="t db-mono">' + esc(e.from + '.' + e.fc) + (e.card === 'nn' ? ' ⇄ ' : ' → ') + esc(e.to + '.' + e.tc) + '</div>'
            + '<div>' + esc(CARD[e.card]) + ' · ' + sources(e).map(function (k) { return SRC[k].label + (e.src[k] > 1 ? ' ×' + e.src[k] : ''); }).join(', ') + '</div>'
            + refs + (e.refs.length > 3 ? '<div class="r">+' + (e.refs.length - 3) + ' chỗ khác</div>' : '');
        tip.hidden = false;
        placeTip(ev);
    }

    function placeTip(ev) {
        var r = stage.getBoundingClientRect();
        var x = ev.clientX - r.left + 14, y = ev.clientY - r.top + 14;
        if (x + tip.offsetWidth > r.width - 8) x = ev.clientX - r.left - tip.offsetWidth - 14;
        if (y + tip.offsetHeight > r.height - 8) y = ev.clientY - r.top - tip.offsetHeight - 14;
        tip.style.left = Math.max(4, x) + 'px';
        tip.style.top = Math.max(4, y) + 'px';
    }

    function hideTip() {
        tip.hidden = true;
    }

    // --- Toàn cảnh: các bảng xếp theo nhóm, rê chuột để thấy đường nối ---
    function renderOverview() {
        var f = srcFilter();
        var deg = {};
        DATA.edges.forEach(function (e) {
            if (!edgeVisible(e, f)) return;
            deg[e.from] = (deg[e.from] || 0) + 1;
            if (e.to !== e.from) deg[e.to] = (deg[e.to] || 0) + 1;
        });

        var html = DATA.groups.filter(function (g) { return g.count; }).map(function (g) {
            var chips = DATA.tables.filter(function (t) { return t.group === g.key; }).map(function (t) {
                return '<span class="db-chip db-g-' + g.key + '" data-t="' + esc(t.name) + '" title="' + t.columns.length + ' trường · ' + (deg[t.name] || 0) + ' quan hệ">'
                    + '<span class="db-mono">' + esc(t.name) + '</span><small>' + (deg[t.name] || 0) + '</small></span>';
            }).join('');
            return '<div class="db-cluster db-g-' + g.key + '"><div class="db-cluster-head"><i class="db-dot db-g-' + g.key + '"></i>'
                + esc(g.label) + '<span class="db-count">' + g.count + '</span></div><div class="db-chips">' + chips + '</div></div>';
        }).join('');

        boardInner.innerHTML = html + '<svg class="links" id="erd-links"></svg>';
    }

    boardInner.addEventListener('mouseover', function (ev) {
        var chip = ev.target.closest('.db-chip');
        if (!chip || chip.classList.contains('is-self')) return;
        drawOverviewLinks(chip);
    });
    boardInner.addEventListener('mouseout', function (ev) {
        var chip = ev.target.closest('.db-chip');
        if (!chip) return;
        var to = ev.relatedTarget;
        if (to && to.closest && to.closest('.db-chip') === chip) return;
        board.classList.remove('is-hover');
        boardInner.querySelectorAll('.db-chip.is-on, .db-chip.is-self').forEach(function (c) { c.classList.remove('is-on', 'is-self'); });
        var links = byId('erd-links');
        if (links) links.innerHTML = '';
    });
    boardInner.addEventListener('click', function (ev) {
        var chip = ev.target.closest('.db-chip');
        if (!chip) return;
        state.mode = 'focus';
        state.focus = chip.dataset.t;
        renderDiagram();
    });

    function drawOverviewLinks(chip) {
        var name = chip.dataset.t;
        var f = srcFilter();
        var links = byId('erd-links');
        var base = boardInner.getBoundingClientRect();

        board.classList.add('is-hover');
        boardInner.querySelectorAll('.db-chip.is-on, .db-chip.is-self').forEach(function (c) { c.classList.remove('is-on', 'is-self'); });
        chip.classList.add('is-self');

        function center(el) {
            var r = el.getBoundingClientRect();
            return { x: r.left - base.left + r.width / 2, y: r.top - base.top + r.height / 2 };
        }

        var a = center(chip);
        var paths = [];
        DATA.edges.forEach(function (e) {
            if (!edgeVisible(e, f) || (e.from !== name && e.to !== name) || e.from === e.to) return;
            var other = e.from === name ? e.to : e.from;
            var oc = boardInner.querySelector('.db-chip[data-t="' + CSS.escape(other) + '"]');
            if (!oc) return;
            oc.classList.add('is-on');
            var b = center(oc);
            var mx = (a.x + b.x) / 2, my = (a.y + b.y) / 2 - Math.min(80, Math.abs(a.x - b.x) * 0.25 + 20);
            var cls = (e.from === name ? 'out' : 'in') + (primarySrc(e) === 'name' ? ' src-name' : '');
            paths.push('<path class="' + cls + '" d="M' + a.x + ',' + a.y + ' Q' + mx + ',' + my + ' ' + b.x + ',' + b.y + '"/>');
        });

        links.setAttribute('width', boardInner.scrollWidth);
        links.setAttribute('height', boardInner.scrollHeight);
        links.innerHTML = paths.join('');
    }

    // --- Điều khiển sơ đồ ---
    document.querySelectorAll('#erd-mode button').forEach(function (b) {
        b.addEventListener('click', function () {
            state.mode = b.dataset.mode;
            renderDiagram();
        });
    });
    document.querySelectorAll('#erd-depth button').forEach(function (b) {
        b.addEventListener('click', function () {
            state.depth = Number(b.dataset.depth);
            renderDiagram();
        });
    });
    srcBoxes.forEach(function (b) { b.addEventListener('change', function () { renderDiagram(); }); });
    allColsEl.addEventListener('change', function () { renderDiagram(); });

    function onTablePicked() {
        if (!tableSel.value) return;
        state.focus = tableSel.value;
        state.mode = 'focus';
        renderDiagram();
    }

    if (window.jQuery && jQuery.fn.select2) {
        jQuery(tableSel).select2({ width: '100%', theme: 'bootstrap4' }).on('select2:select', onTablePicked);
    } else {
        tableSel.addEventListener('change', onTablePicked);
    }

    window.addEventListener('resize', function () {
        if (state.tab === 'diagram' && state.mode === 'focus') fitView();
    });

    /*
    |--------------------------------------------------------------------------
    | Danh sách quan hệ / Migration (DataTables)
    |--------------------------------------------------------------------------
    */

    var DT_LANG = {
        search: 'Tìm kiếm:',
        lengthMenu: 'Hiển thị _MENU_ dòng',
        info: 'Hiển thị _START_ đến _END_ của _TOTAL_ dòng',
        infoEmpty: 'Không có dữ liệu',
        infoFiltered: '(lọc từ _MAX_ dòng)',
        zeroRecords: 'Không tìm thấy dữ liệu phù hợp',
        emptyTable: 'Không có dữ liệu',
        paginate: { previous: 'Trước', next: 'Sau' }
    };

    var relTable = null;
    var migTable = null;

    function gotoCell(v, type) {
        return type === 'display' ? '<span class="db-goto" data-goto="' + esc(v) + '">' + esc(v) + '</span>' : v;
    }

    /** STT đánh lại theo thứ tự đang lọc / sắp xếp */
    function numberRows(dt) {
        function paint() {
            dt.column(0, { search: 'applied', order: 'applied' }).nodes().each(function (cell, i) {
                cell.textContent = i + 1;
            });
        }
        dt.on('order.dt search.dt draw.dt', paint);
        paint();
    }

    function monoCell(v, type) {
        return type === 'display' ? esc(v) : v;
    }

    if (window.jQuery && jQuery.fn.DataTable) {
        var relFilter = byId('db-rel-filter');
        var migFilter = byId('db-mig-filter');

        jQuery.fn.dataTable.ext.search.push(function (settings, row, idx) {
            if (settings.nTable.id === 'db-rel-table') {
                var v = relFilter.value;
                if (!v) return true;
                var e = DATA.edges[idx];
                return v === 'nn' ? e.card === 'nn' : e.src[v] > 0;
            }
            if (settings.nTable.id === 'db-mig-table') {
                var m = migFilter.value;
                if (!m) return true;
                return (m === 'ran') === DATA.migrations[idx].ran;
            }
            return true;
        });

        relTable = jQuery('#db-rel-table').DataTable({
            data: DATA.edges,
            autoWidth: false,
            pageLength: 25,
            lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, 'Tất cả']],
            order: [[1, 'asc'], [2, 'asc']],
            language: DT_LANG,
            columns: [
                { data: 'id', orderable: false },
                { data: 'from', render: gotoCell },
                { data: 'fc', render: monoCell },
                { data: 'to', render: gotoCell },
                { data: 'tc', render: monoCell },
                { data: 'card', render: function (v) { return CARD[v] || v; } },
                {
                    data: null,
                    render: function (d, type, e) {
                        return type === 'display' ? srcBadges(e) : sources(e).map(function (k) { return SRC[k].label; }).join(' ');
                    }
                },
                { data: 'count', className: 'text-center' },
                {
                    data: null,
                    orderable: false,
                    render: function (d, type, e) {
                        return type === 'display' ? refsHtml(e.refs, 2, true) : e.refs.map(function (r) { return r[0]; }).join(' ');
                    }
                }
            ]
        });

        relFilter.addEventListener('change', function () { relTable.draw(); });

        numberRows(relTable);

        migTable = jQuery('#db-mig-table').DataTable({
            data: DATA.migrations,
            autoWidth: false,
            pageLength: 25,
            lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, 'Tất cả']],
            order: [[2, 'desc']],
            language: DT_LANG,
            columns: [
                {
                    data: null,
                    orderable: false,
                    render: function (d, type, m, meta) { return meta.row + 1; }
                },
                {
                    data: 'date',
                    render: function (v, type, m) { return type === 'display' ? migDate(m.name, true) : (v || ''); }
                },
                {
                    data: 'name',
                    render: function (v, type) {
                        return type === 'display' ? '<span class="db-mono" title="' + esc(v) + '">' + esc(migLabel(v)) + '</span>' : v;
                    }
                },
                {
                    data: 'tables',
                    orderable: false,
                    render: function (v, type) {
                        var names = Object.keys(v || {});
                        if (type !== 'display') return names.join(' ');
                        return '<div class="db-ops">' + names.map(function (t) {
                            var ops = v[t];
                            var main = ops.indexOf('create') >= 0 ? 'create' : (ops.indexOf('drop') >= 0 ? 'drop' : (ops.indexOf('rename') >= 0 ? 'rename' : (ops.indexOf('alter') >= 0 ? 'alter' : 'data')));
                            return '<span class="db-op op-' + main + '"' + (T[t] ? ' data-goto="' + esc(t) + '"' : '') + ' title="' + esc(ops.map(function (o) { return OP[o] || o; }).join(', ')) + '">'
                                + '<span class="db-mono">' + esc(t) + '</span></span>';
                        }).join('') + '</div>';
                    }
                },
                { data: 'batch', className: 'text-center', render: function (v) { return v === null ? '—' : v; } },
                {
                    data: 'ran',
                    render: function (v, type) {
                        if (type !== 'display') return v ? 'Đã chạy' : 'Chờ chạy';
                        return v ? '<span class="db-badge is-ok"><i class="fas fa-check"></i>Đã chạy</span>'
                            : '<span class="db-badge is-danger"><i class="fas fa-hourglass-half"></i>Chờ chạy</span>';
                    }
                }
            ]
        });

        numberRows(migTable);

        migFilter.addEventListener('change', function () { migTable.draw(); });
    }

    /*
    |--------------------------------------------------------------------------
    | Kiểm tra
    |--------------------------------------------------------------------------
    */

    var ISSUE = {
        pending_migration: { title: 'Migration chưa chạy', icon: 'fa-hourglass-half', tip: 'Có file migration nhưng CSDL này chưa chạy: php artisan migrate' },
        missing_table: { title: 'Code dùng bảng không có trong CSDL', icon: 'fa-unlink', tip: 'Tên bảng viết trong code nhưng CSDL không có bảng đó (gõ sai tên, migration chưa chạy hoặc bảng đã bị xoá)' },
        missing_column: { title: 'Điều kiện join dùng trường không có trong bảng', icon: 'fa-times-circle', tip: 'Câu join / whereColumn tham chiếu trường không tồn tại - truy vấn sẽ lỗi khi chạy tới' },
        orphan_migration: { title: 'Migration đã chạy nhưng không còn file', icon: 'fa-ghost', tip: 'Bảng migrations có bản ghi nhưng thư mục database/migrations không còn file tương ứng' },
        type_mismatch: { title: 'Hai đầu quan hệ khác kiểu dữ liệu', icon: 'fa-not-equal', tip: 'Trường giữ khoá và trường được tham chiếu khác kiểu - join chậm, có thể so sánh sai' },
        missing_index: { title: 'Trường liên kết chưa có chỉ mục', icon: 'fa-tachometer-alt', tip: 'Trường dùng để join chưa có index - bảng lớn dần sẽ truy vấn chậm' },
        unresolved_id: { title: 'Trường *_id chưa xác định được bảng liên kết', icon: 'fa-question-circle', tip: 'Code chưa join trường này và tên trường không khớp bảng nào (có thể trỏ CSDL ngoài hoặc đa hình)' },
        unused_table: { title: 'Bảng chưa thấy code sử dụng', icon: 'fa-archive', tip: 'Không tìm thấy tên bảng trong app/, routes/, resources/views (bảng tên ghép động sẽ không dò được)' },
        no_migration_table: { title: 'Bảng không xác định được migration tạo', icon: 'fa-file-excel', tip: 'Không file migration nào tạo bảng này (tạo tay bằng SQL hoặc tên bảng ghép động)' },
        no_migration_column: { title: 'Trường không xác định được migration thêm', icon: 'fa-file-excel', tip: 'Không file migration nào thêm trường này (thêm tay bằng SQL hoặc tên ghép động)' }
    };

    function issueItem(i) {
        var parts = i.key.split('.');
        var target = i.target ? ' <i class="fas fa-long-arrow-alt-right text-muted mx-1"></i><span class="db-goto db-mono" data-goto="'
            + esc(i.target.split('.')[0]) + '" data-col="' + esc(i.target.split('.')[1] || '') + '">' + esc(i.target) + '</span>' : '';

        var main;
        if (i.type === 'pending_migration' || i.type === 'orphan_migration') {
            main = '<span class="db-mono">' + esc(i.key) + '</span>' + (i.batch ? ' <span class="db-badge is-muted">batch ' + esc(i.batch) + '</span>' : '');
        } else if (i.type === 'missing_table') {
            main = '<b class="db-mono">' + esc(i.key) + '</b> <span class="db-badge is-danger">' + i.count + ' chỗ</span>';
        } else if (T[parts[0]]) {
            main = '<span class="db-goto db-mono" data-goto="' + esc(parts[0]) + '" data-col="' + esc(parts[1] || '') + '">' + esc(i.key) + '</span>';
        } else {
            main = '<span class="db-mono">' + esc(i.key) + '</span>';
        }

        return '<div class="db-issue-item">' + main + target
            + (i.detail ? ' <span class="db-badge is-warn db-mono">' + esc(i.detail) + '</span>' : '')
            + (i.seed ? ' <span class="db-badge is-muted">chỉ có trong seeder</span>' : '')
            + (i.refs ? refsHtml(i.refs, 5) : '')
            + '</div>';
    }

    function renderIssues() {
        var groups = {};
        var order = [];
        DATA.issues.forEach(function (i) {
            if (!groups[i.type]) { groups[i.type] = []; order.push(i.type); }
            groups[i.type].push(i);
        });

        if (!order.length) {
            byId('db-issues').innerHTML = '<div class="db-card db-ok-state"><i class="fas fa-check-circle"></i>Không phát hiện vấn đề nào</div>';
            return;
        }

        var lvlIcon = { danger: 'fa-exclamation-circle', warning: 'fa-exclamation-triangle', info: 'fa-info-circle' };
        byId('db-issues').innerHTML = order.map(function (type) {
            var list = groups[type];
            var meta = ISSUE[type] || { title: type, icon: 'fa-info-circle', tip: '' };
            var lvl = list[0].level;
            return '<details class="db-issue lvl-' + lvl + '"' + (lvl === 'danger' ? ' open' : '') + '>'
                + '<summary title="' + esc(meta.tip) + '"><i class="fas ' + lvlIcon[lvl] + ' lvl-icon"></i>'
                + '<i class="fas ' + meta.icon + ' text-muted"></i>' + esc(meta.title)
                + '<span class="db-count">' + list.length + '</span><i class="fas fa-chevron-down chev"></i></summary>'
                + '<div class="db-issue-body">' + list.map(issueItem).join('') + '</div></details>';
        }).join('');
    }

    /*
    |--------------------------------------------------------------------------
    | Theo dõi thay đổi cấu trúc / code
    |--------------------------------------------------------------------------
    */

    var changed = false;

    function poll() {
        if (changed || document.hidden) return;
        fetch(CFG.signatureUrl, { credentials: 'same-origin', headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (r) { return r.ok ? r.json() : Promise.reject(r.status); })
            .then(function (d) {
                if (d.signature && d.signature !== DATA.signature) {
                    changed = true;
                    byId('db-changed').hidden = false;
                    byId('db-live').innerHTML = '<i class="fas fa-circle"></i> Có thay đổi mới';
                }
            })
            .catch(function () { /* mất kết nối tạm thời: lần sau thử lại */ });
    }

    setInterval(poll, 30000);
    document.addEventListener('visibilitychange', function () {
        if (!document.hidden) poll();
    });
    byId('db-reload').addEventListener('click', function () { location.reload(); });

    /*
    |--------------------------------------------------------------------------
    | Khởi động
    |--------------------------------------------------------------------------
    */

    renderList();
    renderIssues();
    if (state.table) renderDetail(state.table);
    showTab(state.tab);
});
</script>
@endverbatim
