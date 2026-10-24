{{--
|--------------------------------------------------------------------------
| TỒN KHO HOÁ CHẤT - DỰNG BẢNG TỪ JSON
|--------------------------------------------------------------------------
| Trang chỉ trả khung; số liệu lấy MỘT LẦN từ route data() theo kỳ đang xem.
| Mỗi tab chỉ khởi tạo DataTables ở lần mở đầu tiên, và nhờ deferRender chỉ
| dựng <tr> của trang đang hiển thị (25 dòng) thay vì cả nghìn dòng x 5 tab.
|
| Nút trong ô (badge .inv-mv, Cân đối, Lịch sử, Hạn nội bộ, File đính kèm) giữ
| nguyên class + data-* như bản render ở server nên các modal trong
| inventory/shared/assets và shared/attachmentListAssets chạy không đổi.
--}}
@php
    // Màu thẻ mã vị trí - cùng quy tắc với App\Support\ZoneType::tag()
    $invZoneTypes = collect(\App\Support\ZoneType::TYPES)
        ->map(function ($label, $type) {
            $color = \App\Support\ZoneType::DEFAULT_COLORS[$type] ?? null;

            return [
                'label' => $label,
                'color' => $color,
                'text' => $color ? \App\Support\ZoneType::textOn($color) : null,
            ];
        })
        ->all();

    $invConfig = [
        'dataUrl' => route('pages.inventory.chemicalInventory.data'),
        'zonesUrl' => route('pages.inventory.chemicalInventory.zones'),
        'period' => ['from_date' => $period['from'], 'to_date' => $period['to']],
        'states' => $states,
        'nearExpiryDays' => $nearExpiryDays,
        'expiringFilters' => $expiringFilters,
        'vs' => $vsFilter,
        'zoneTypes' => $invZoneTypes,
        'can' => [
            'internal' => user_can('inventory_chemical_internalExpiry'),
            'balancing' => user_can('inventory_chemical_balancing'),
            'upload' => user_can('import_chemical_attachment_upload'),
            'toggle' => user_can('import_chemical_attachment_status'),
            'quarantine' => user_can('export_chemical_quarantine'),
        ],
        'urls' => [
            'download' => route('pages.inventory.chemicalInventory.downloadAttachment', ['id' => '__ID__']),
            'upload' => route('pages.inventory.chemicalInventory.uploadAttachment', ['id' => '__ID__']),
            'toggle' => route('pages.inventory.chemicalInventory.toggleAttachmentStatus'),
        ],
    ];
@endphp

<script>
    document.addEventListener('DOMContentLoaded', function() {
        var cfg = @json($invConfig);

        var payload = null;
        var source = null;
        var built = {};
        var zonesRequest = null;

        /* ---------- Định dạng - khớp các hàm $invNum / $invDate / $mvCls ở Blade ---------- */

        function esc(value) {
            return String(value === null || value === undefined ? '' : value).replace(/[&<>"']/g, function(c) {
                return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
            });
        }

        /** Bỏ số 0 thừa ở phần thập phân, phân tách hàng nghìn bằng dấu phẩy: 1234.5000 -> 1,234.5 */
        function num(value) {
            var n = Number(value) || 0;
            var parts = Math.abs(n).toFixed(4).split('.');
            var whole = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ',');
            var dec = parts[1].replace(/0+$/, '');

            return (n < 0 && (whole !== '0' || dec) ? '-' : '') + whole + (dec ? '.' + dec : '');
        }

        /** 'Y-m-d' hoặc 'Y-m-d H:i:s' -> d/m/Y, trống thì gạch ngang */
        function date(value) {
            if (!value) return '—';

            var p = String(value).substr(0, 10).split('-');

            return p.length === 3 ? p[2] + '/' + p[1] + '/' + p[0] : esc(value);
        }

        function strip(html) {
            return String(html).replace(/<[^>]*>/g, ' ');
        }

        /** Class badge .inv-mv: 0 -> mờ, Sử Dụng ép xanh dương, Huỷ ép đỏ, cột tổng tồn ép xanh, còn lại theo dấu */
        function mvCls(value, tone) {
            var n = Number(value) || 0;

            if (n === 0) return 'inv-mv is-muted';
            if (tone === 'out') return 'inv-mv is-out';
            if (tone === 'in') return 'inv-mv is-in';
            if (tone === 'use') return 'inv-mv is-use';

            return 'inv-mv ' + (n > 0 ? 'is-in' : 'is-out');
        }

        function mvBtn(value, tone, scope, id, metric, extra, text) {
            return '<button type="button" class="' + mvCls(value, tone) + (extra || '') + '" data-scope="' + scope +
                '" data-id="' + esc(id) + '" data-metric="' + metric + '">' + (text === undefined ? num(value) : text) +
                '</button>';
        }

        function sub(html) {
            return '<div class="md-sub">' + html + '</div>';
        }

        function tag(text) {
            return '<span class="md-tag">' + esc(text || '—') + '</span>';
        }

        function attr(obj) {
            return esc(JSON.stringify(obj));
        }

        /** Thẻ mã vị trí tô màu theo phân loại định khu - như ZoneType::tag() */
        function zoneTag(row) {
            if (!row.location_code) return '<span class="inv-zone-none">Chưa xếp vị trí</span>';

            var zone = cfg.zoneTypes[row.location_zone_type] || null;
            var label = zone ? zone.label : 'Chưa phân loại';
            var html = zone && zone.color ?
                '<span class="zone-tag" style="--zt:' + zone.color + ';--zt-text:' + zone.text + '"' :
                '<span class="zone-tag is-none"';

            return '<div class="font-weight-bold">' + html + ' title="' + esc(label) + '">' +
                esc(row.location_code) + '</span></div>';
        }

        function specialBadge(row) {
            return row.special ?
                ' <span class="badge-special-control ml-1" title="Hoá chất kiểm soát đặc biệt (Phụ lục III NĐ 24/2026)">' +
                '<i class="fas fa-shield-alt"></i>Kiểm soát đặc biệt</span>' : '';
        }

        function stateBadge(row) {
            return '<span class="inv-badge inv-badge-' + esc(row.state) + '">' + esc(cfg.states[row.state] || '') + '</span>';
        }

        function unitOf(row) {
            return esc(row.unit || '');
        }

        /** Nút mở modal file đính kèm - như partial pages/shared/attachmentList */
        function filesBtn(row) {
            var files = (row.files || []).map(function(file) {
                return $.extend({}, file, { url: cfg.urls.download.replace('__ID__', file.id) });
            });

            if (!files.length && !cfg.can.upload) return '<span class="text-muted">—</span>';

            return '<button type="button" class="btn btn-xs btn-outline-primary att-open-modal" title="Xem/thêm file đính kèm"' +
                ' data-code="' + esc(row.code) + '" data-name="' + esc(row.chem_name) + '" data-type-label="Hoá chất"' +
                ' data-toggle-url="' + (cfg.can.toggle ? esc(cfg.urls.toggle) : '') + '"' +
                ' data-upload-url="' + (cfg.can.upload ? esc(cfg.urls.upload.replace('__ID__', row.id)) : '') + '"' +
                ' data-files="' + attr(files) + '"><i class="fas fa-paperclip"></i> (' + files.length + ')</button>';
        }

        /**
         * Nút [Cách ly] của một mã xuất nhập còn tồn - mở #invQuarantineModal (ChemicalInventory/quarantine).
         * Lấy tồn HIỆN TẠI (gap_all, không cắt theo kỳ) vì cách ly trừ vào tồn hôm nay.
         */
        function quarantineBtn(row) {
            if (!cfg.can.quarantine || !(row.gap_all > 0)) return '';

            return '<button type="button" class="inv-act-btn is-danger btn-inv-quarantine" title="Cách ly hoá chất hỏng / hết hạn"' +
                ' data-row="' + attr({
                    import_id: row.id,
                    code: row.code,
                    chem_name: row.chem_name,
                    category_code: row.category_code,
                    batch_no: row.batch_no,
                    unit: row.unit,
                    limit: Math.round(row.gap_all * 10000) / 10000,
                    location_code: row.location_code,
                    expired_date: row.effective_expired_date,
                    days: row.days_to_effective_expiry
                }) + '"><i class="fas fa-biohazard"></i></button>';
        }

        function internalBtn(row, className, label) {
            return '<button type="button" class="btn btn-sm ' + className + ' btn-inv-internal"' +
                ' title="Xác định hạn dùng nội bộ cho mã xuất nhập này" data-row="' + attr({
                    import_id: row.id,
                    code: row.code,
                    chem_name: row.chem_name,
                    shelf_life_months: row.shelf_life_months,
                    expired_date: row.expired_date,
                    internal_expired_date: row.internal_expired_date
                }) + '"><i class="fas fa-hourglass-half mr-1"></i> ' + label + '</button>';
        }

        /**
         * Một cột DataTables: display = HTML của ô, sort = giá trị sắp xếp (thay data-order
         * cũ), filter mặc định = chữ thuần của ô để ô Tìm kiếm khớp như khi render ở server.
         */
        function col(spec) {
            return {
                data: spec.data || null,
                className: spec.className || '',
                orderable: spec.orderable !== false,
                searchable: spec.searchable !== false,
                defaultContent: '',
                render: function(value, type, row, meta) {
                    if (type === 'display') return spec.display(row, meta);
                    if (type === 'filter') return spec.filter ? spec.filter(row) : strip(spec.display(row, meta));

                    return spec.sort ? spec.sort(row, meta) : value;
                }
            };
        }

        // Mỗi bảng một object cột riêng: DataTables ghi thêm khoá vào object cấu hình
        function colStt() {
            return col({
                className: 'text-center',
                display: function(row, meta) { return meta.row + 1; },
                sort: function(row, meta) { return meta.row; },
                filter: function() { return ''; }
            });
        }

        function dateSort(key) {
            return function(row) { return row[key] || '9999-12-31'; };
        }

        function codeCell(withBatch) {
            return col({
                data: 'code',
                display: function(row) {
                    return '<span class="inv-code">' + esc(row.code) + '</span>' + sub(tag(row.category_code)) +
                        (withBatch && row.batch_no ? sub('Lô ' + esc(row.batch_no)) : '');
                }
            });
        }

        /** Cột Đơn Vị riêng - các ô số liệu không lặp lại đơn vị nữa */
        function unitCell() {
            return col({
                data: 'unit',
                className: 'text-center md-sub',
                display: function(row) { return esc(row.unit || '—'); }
            });
        }

        /** Gắn tooltip vào nút badge .inv-mv - thông tin phụ để ở tooltip cho ô gọn một dòng như Tồn Kho Vật Tư */
        function tip(html, title) {
            return title ? html.replace('<button ', '<button title="' + esc(title) + '" ') : html;
        }

        /**
         * Cấu hình chung của các bảng nạp JSON: bảng đặt trong khung .inv-scroll cao vừa
         * màn hình để tiêu đề cột luôn dính trên cùng khi cuộn; Hiển thị / Tìm kiếm do
         * window.mdTablebar (layout/master) gom lên thanh công cụ.
         */
        function lazyOptions(order, emptyText, columnDefs) {
            return $.extend(true, invTableOptions(order, emptyText, columnDefs), {
                dom: "<'row'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6'f>>" +
                    "<'inv-scroll'tr>" +
                    "<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
                // Bỏ chữ "Hiển thị ... dòng" / "Tìm kiếm:" cho thanh công cụ vừa một hàng
                language: {
                    lengthMenu: '_MENU_',
                    search: '',
                    searchPlaceholder: 'Tìm kiếm...'
                }
            });
        }

        /* ---------- Tab Theo mã xuất nhập ---------- */
        function buildCode() {
            return $('#mdTable').DataTable($.extend(lazyOptions([13, 'asc'],
                'Chưa có phiếu nhập nào để tính tồn kho. Hãy tạo phiếu ở màn hình Nhập Hoá Chất.',
                [{ targets: -1, orderable: false, searchable: false }]), {
                data: payload.rows,
                deferRender: true,
                search: { smart: false },
                columns: [
                    colStt(),
                    col({
                        data: 'code',
                        display: function(row) {
                            return '<span class="inv-code font-weight-bold">' + esc(row.code) + '</span>' + sub(tag(row.category_code));
                        }
                    }),
                    col({
                        data: 'chem_name',
                        display: function(row) {
                            var parts = [];

                            if (row.category_type) parts.push(tag(row.category_type));
                            if (row.batch_no) {
                                parts.push('<span class="' + (row.category_type ? 'ml-1' : '') + '">Lô ' + esc(row.batch_no) + '</span>');
                            }

                            return '<div class="font-weight-bold">' + esc(row.chem_name || '—') + specialBadge(row) + '</div>' +
                                (parts.length ? '<div class="md-sub small text-muted">' + parts.join('') + '</div>' : '');
                        }
                    }),
                    col({
                        data: 'location_code',
                        className: 'md-sub',
                        display: zoneTag,
                        sort: function(row) { return row.location_order; }
                    }),
                    unitCell(),
                    col({
                        data: 'opening',
                        className: 'text-right',
                        display: function(row) {
                            return tip(mvBtn(row.opening, null, 'import', row.id, 'opening'),
                                row.period_imported > 0 ? 'Mới nhập trong kỳ' : '');
                        }
                    }),
                    col({
                        data: 'period_imported',
                        className: 'text-right',
                        display: function(row) {
                            return tip(mvBtn(row.period_imported, null, 'import', row.id, 'period_in'),
                                row.period_imported > 0 ? 'Nhập ' + date(row.imported_date) : '');
                        }
                    }),
                    col({
                        data: 'period_balanced',
                        className: 'text-right',
                        display: function(row) {
                            return tip(mvBtn(row.period_balanced, null, 'import', row.id, 'period_balanced', '',
                                    (row.period_balanced > 0 ? '+' : '') + num(row.period_balanced)),
                                row.period_balancing_times > 0 ? row.period_balancing_times + ' lần cân đối' : '');
                        }
                    }),
                    col({
                        data: 'period_used',
                        className: 'text-right',
                        display: function(row) {
                            return tip(mvBtn(row.period_used, 'use', 'import', row.id, 'period_used'),
                                row.period_export_times > 0 ? row.period_export_times + ' lần xuất' : '');
                        }
                    }),
                    col({
                        data: 'period_cancelled',
                        className: 'text-right',
                        display: function(row) {
                            return mvBtn(row.period_cancelled, 'out', 'import', row.id, 'period_cancelled');
                        }
                    }),
                    col({
                        data: 'gap',
                        className: 'text-right font-weight-bold',
                        display: function(row) {
                            // Tồn âm (đã xuất vượt) hiện đúng số âm để thấy phần phải cân đối
                            return tip(mvBtn(row.gap, null, 'import', row.id, 'closing', row.state === 'over' ? ' is-out' : ''),
                                'Đã dùng ' + row.used_percent + '% (luỹ kế ' + num(row.used_total) + ')');
                        }
                    }),
                    col({
                        data: 'batch_remaining',
                        className: 'text-right',
                        display: function(row) {
                            return '<button type="button" class="' + mvCls(row.batch_remaining, 'in') + '" data-scope="category"' +
                                ' data-id="' + row.category_id + '" data-metric="stock_batch" data-batch="' + esc(row.batch_no || '') +
                                '" title="' + esc((row.batch_no ? 'Lô ' + row.batch_no : 'Chưa có số lô') + ' · ' + row.batch_codes + ' mã') +
                                '">' + num(row.batch_remaining) + '</button>';
                        }
                    }),
                    col({
                        data: 'category_remaining',
                        className: 'text-right',
                        display: function(row) {
                            return tip(mvBtn(row.category_remaining, 'in', 'category', row.category_id, 'stock_category'),
                                row.category_batches + ' lô · ' + row.category_codes + ' mã');
                        }
                    }),
                    col({
                        data: 'expired_date',
                        className: 'text-center md-sub',
                        sort: dateSort('expired_date'),
                        display: function(row) {
                            var days = row.days_to_expiry;

                            if (days === null || row.remaining <= 0) return date(row.expired_date);
                            if (days < 0) {
                                return '<span class="text-danger font-weight-bold" title="Quá ' + Math.abs(days) + ' ngày">' +
                                    date(row.expired_date) + '</span>';
                            }

                            return '<span title="Còn ' + days + ' ngày">' + date(row.expired_date) + '</span>';
                        }
                    }),
                    col({
                        data: 'internal_expired_date',
                        className: 'text-center md-sub',
                        sort: dateSort('internal_expired_date'),
                        display: function(row) {
                            if (row.internal_expired_date) return '<span class="inv-internal">' + date(row.internal_expired_date) + '</span>';
                            if (row.shelf_life_months > 0) return '<span class="inv-internal-none">Chưa xác định</span>';

                            return '<span class="md-empty" title="Hoá chất chưa khai báo hạn dùng mặc định trong Danh Mục">—</span>';
                        }
                    }),
                    col({
                        data: 'state',
                        className: 'text-center',
                        display: stateBadge,
                        sort: function(row) { return cfg.states[row.state] || ''; }
                    }),
                    col({
                        data: 'files',
                        className: 'text-center',
                        searchable: false,
                        display: filesBtn,
                        sort: function(row) { return (row.files || []).length; }
                    }),
                    col({
                        className: 'text-center',
                        orderable: false,
                        searchable: false,
                        display: function(row) {
                            var html = '<div class="inv-actions">' + quarantineBtn(row);

                            if (row.shelf_life_months > 0 && cfg.can.internal) {
                                html += '<button type="button" class="inv-act-btn btn-inv-internal"' +
                                    ' title="Xác định hạn dùng nội bộ" data-row="' + attr({
                                        import_id: row.id,
                                        code: row.code,
                                        chem_name: row.chem_name,
                                        shelf_life_months: row.shelf_life_months,
                                        expired_date: row.expired_date,
                                        internal_expired_date: row.internal_expired_date
                                    }) + '"><i class="fas fa-hourglass-half"></i></button>';
                            }

                            if (cfg.can.balancing) {
                                html += '<button type="button" class="inv-act-btn btn-inv-balancing' +
                                    (row.state === 'over' ? ' is-danger' : '') + '" title="Cân đối" data-row="' + attr({
                                        import_id: row.id,
                                        code: row.code,
                                        chem_name: row.chem_name,
                                        unit: row.unit,
                                        imported: row.imported,
                                        // Cân đối ghi vào dữ liệu hiện tại nên không cắt theo kỳ
                                        balanced: row.balanced_all,
                                        gap: row.gap_all,
                                        limit: row.balancing_limit,
                                        min_input: row.balancing_min_input,
                                        max_input: row.balancing_max_input
                                    }) + '"><i class="fas fa-balance-scale"></i></button>';
                            }

                            // Số lần đã cân đối, bấm vào để xem lịch sử
                            if (row.balancing_times > 0) {
                                html += '<button type="button" class="inv-act-count btn-inv-history"' +
                                    ' title="Xem lịch sử cân đối" data-row="' + attr({
                                        import_id: row.id,
                                        code: row.code,
                                        chem_name: row.chem_name,
                                        category_code: row.category_code,
                                        unit: row.unit,
                                        imported: row.imported,
                                        balanced: row.balanced_all,
                                        gap: row.gap_all
                                    }) + '">' + row.balancing_times + '</button>';
                            }

                            return html + '</div>';
                        }
                    })
                ]
            }));
        }

        /* ---------- Tab Theo hoá chất ---------- */
        function buildChem() {
            function sumBtn(key, metric, tone) {
                return col({
                    data: key,
                    className: 'text-right' + (key === 'closing' ? ' font-weight-bold' : ''),
                    display: function(sum) {
                        var text = key === 'period_balanced' ? (sum[key] > 0 ? '+' : '') + num(sum[key]) : undefined;

                        return mvBtn(sum[key], tone, 'category', sum.category_id, metric, '', text);
                    }
                });
            }

            return $('#invSummaryTable').DataTable($.extend(lazyOptions([1, 'asc'], 'Chưa có hoá chất nào trong kho.',
                [{ targets: -1, orderable: false, searchable: false }]), {
                data: payload.summaries,
                deferRender: true,
                columns: [
                    colStt(),
                    col({
                        data: 'chem_name',
                        display: function(sum) {
                            return '<div class="font-weight-bold">' + esc(sum.chem_name || '—') + specialBadge(sum) + '</div>' +
                                sub(tag(sum.category_code));
                        }
                    }),
                    col({
                        data: 'code_count',
                        className: 'text-center',
                        display: function(sum) {
                            return '<span title="Mã còn tồn / tổng số mã">' + sum.in_stock_count + '/' + sum.code_count + '</span>';
                        }
                    }),
                    unitCell(),
                    sumBtn('opening', 'opening'),
                    sumBtn('period_imported', 'period_in'),
                    sumBtn('period_balanced', 'period_balanced'),
                    sumBtn('period_used', 'period_used', 'use'),
                    sumBtn('period_cancelled', 'period_cancelled', 'out'),
                    sumBtn('closing', 'closing'),
                    col({
                        data: 'nearest_expiry',
                        className: 'text-center md-sub',
                        sort: dateSort('nearest_expiry'),
                        display: function(sum) { return date(sum.nearest_expiry); }
                    }),
                    // Mở biểu đồ nhập - xuất - tồn của đúng hoá chất này, theo kỳ đang xem
                    col({
                        className: 'text-center',
                        orderable: false,
                        searchable: false,
                        display: function(sum) {
                            return '<button type="button" class="btn btn-sm btn-outline-primary btn-inv-chart"' +
                                ' data-category="' + sum.category_id + '" data-chem="' + esc(sum.chem_name) + '"' +
                                ' title="Xem biểu đồ nhập - xuất - tồn của hoá chất này">' +
                                '<i class="fas fa-chart-bar mr-1"></i> Biểu đồ</button>';
                        }
                    })
                ]
            }));
        }

        /* ---------- Tab Theo định khu: sơ đồ thẻ như Tồn Kho Vật Tư (inventory/shared/zoneGrid) ---------- */
        function buildZone() {
            // Khung 5 cấp định khu chỉ tải khi mở tab này - phòng nhiều vị trí thì danh sách nặng.
            // Giữ lại để đổi bộ lọc VS dựng lại sơ đồ không phải tải lần nữa.
            zonesRequest = zonesRequest || $.getJSON(cfg.zonesUrl);

            zonesRequest.done(function(zones) {
                window.invZoneGrid('#invZoneMap', {
                    zones: zones,
                    rows: payload.rows.map(function(row) {
                        return $.extend({}, row, {
                            name: row.chem_name,
                            sub: [row.category_code, row.batch_no ? 'Lô ' + row.batch_no : '', row.category_type]
                                .filter(Boolean).join(' · '),
                            expired: row.effective_expired_date,
                            state_label: cfg.states[row.state] || ''
                        });
                    })
                });

                // Dựng lại (đổi bộ lọc VS) thì áp lại tình trạng / loại định khu đang chọn
                $('#invZoneMap .mz-filter[data-state].is-active, #invZoneMap .mz-zt-filter.is-active').trigger('click');
            }).fail(function() {
                zonesRequest = null;
                built.invPaneZone = false;
                $('#invZoneMap .mz-body').html('<div class="mz-empty text-danger"><i class="fas fa-exclamation-triangle"></i>' +
                    'Không tải được danh sách định khu, mở lại tab để thử lại.</div>');
            });
        }

        /* ---------- Tab Cận Hạn Dùng ---------- */
        var expiryFilter = 'all';

        /** Hạn áp dụng nằm trong mốc lọc (so chuỗi Y-m-d), 'all' = cả danh sách dưới 6 tháng */
        function inExpiryFilter(row, key) {
            var filter = cfg.expiringFilters[key];

            return !filter || String(row.effective_expired_date || '').substr(0, 10) <= filter.until;
        }

        $.fn.dataTable.ext.search.push(function(settings, data, index, rowData) {
            if (settings.nTable.id !== 'invExpiringTable' || expiryFilter === 'all') return true;

            return inExpiryFilter(rowData, expiryFilter);
        });

        $(document).on('click', '.inv-chip[data-expiry]', function() {
            expiryFilter = $(this).data('expiry');

            $('.inv-chip[data-expiry]').removeClass('is-active');
            $(this).addClass('is-active');

            if ($.fn.dataTable.isDataTable('#invExpiringTable')) $('#invExpiringTable').DataTable().draw();
        });

        function buildExpiring() {
            return $('#invExpiringTable').DataTable($.extend(lazyOptions([7, 'asc'],
                'Không có mã xuất nhập nào còn tồn mà sắp hết hạn.',
                [{ targets: -1, orderable: false, searchable: false }]), {
                data: payload.expiring,
                deferRender: true,
                columns: [
                    colStt(),
                    codeCell(true),
                    col({
                        data: 'chem_name',
                        display: function(row) {
                            var parts = [];

                            if (row.category_type) parts.push(tag(row.category_type));

                            return '<div class="font-weight-bold">' + esc(row.chem_name || '—') + specialBadge(row) + '</div>' +
                                sub(parts.join(''));
                        }
                    }),
                    col({
                        data: 'remaining',
                        className: 'text-right',
                        display: function(row) {
                            return '<span class="inv-remaining">' + num(row.remaining) + '</span> <span class="md-sub">' +
                                unitOf(row) + '</span>';
                        }
                    }),
                    col({
                        data: 'batch_remaining',
                        className: 'text-right',
                        display: function(row) {
                            return '<span class="inv-group-total">' + num(row.batch_remaining) + '</span> <span class="md-sub">' +
                                unitOf(row) + '</span>' + sub(row.batch_codes + ' mã');
                        }
                    }),
                    col({
                        data: 'expired_date',
                        className: 'text-center md-sub',
                        sort: dateSort('expired_date'),
                        display: function(row) { return date(row.expired_date); }
                    }),
                    col({
                        data: 'internal_expired_date',
                        className: 'text-center md-sub',
                        sort: dateSort('internal_expired_date'),
                        display: function(row) {
                            return row.internal_expired_date ? date(row.internal_expired_date) :
                                '<span class="md-empty">Chưa xác định</span>';
                        }
                    }),
                    col({
                        data: 'effective_expired_date',
                        className: 'text-center',
                        sort: dateSort('effective_expired_date'),
                        display: function(row) {
                            var days = row.days_to_effective_expiry;
                            var countdown = days < 0 ?
                                '<span class="inv-countdown is-over">Quá ' + Math.abs(days) + ' ngày</span>' :
                                '<span class="inv-countdown ' + (days <= cfg.nearExpiryDays ? 'is-near' : '') + '">Còn ' + days +
                                ' ngày</span>';

                            return '<div class="font-weight-bold">' + date(row.effective_expired_date) + '</div>' + countdown;
                        }
                    }),
                    col({
                        data: 'state',
                        className: 'text-center',
                        display: stateBadge,
                        sort: function(row) { return cfg.states[row.state] || ''; }
                    }),
                    col({
                        className: 'text-center',
                        orderable: false,
                        searchable: false,
                        display: function(row) {
                            return '<div class="inv-actions">' + (quarantineBtn(row) || '<span class="md-empty">—</span>') + '</div>';
                        }
                    })
                ]
            }));
        }

        /* ---------- Tab Chưa có hạn nội bộ ---------- */
        function buildInternal() {
            return $('#invInternalTable').DataTable($.extend(lazyOptions([4, 'asc'],
                'Tất cả hoá chất có khai báo hạn dùng mặc định đều đã xác định hạn dùng nội bộ.',
                [{ targets: -1, orderable: false, searchable: false }]), {
                data: payload.internal,
                deferRender: true,
                columns: [
                    colStt(),
                    codeCell(true),
                    col({
                        data: 'chem_name',
                        display: function(row) {
                            return '<div class="font-weight-bold">' + esc(row.chem_name || '—') + specialBadge(row) + '</div>' +
                                (row.category_type ? sub(tag(row.category_type)) : '');
                        }
                    }),
                    col({
                        data: 'gap',
                        className: 'text-right',
                        display: function(row) {
                            return '<span class="inv-remaining ' + (row.remaining > 0 ? '' : 'is-zero') + '">' + num(row.gap) +
                                '</span> <span class="md-sub">' + unitOf(row) + '</span>';
                        }
                    }),
                    col({
                        data: 'imported_date',
                        className: 'text-center md-sub',
                        sort: dateSort('imported_date'),
                        display: function(row) { return date(row.imported_date); }
                    }),
                    col({
                        data: 'expired_date',
                        className: 'text-center md-sub',
                        sort: dateSort('expired_date'),
                        display: function(row) { return date(row.expired_date); }
                    }),
                    col({
                        data: 'shelf_life_months',
                        className: 'text-center',
                        display: function(row) { return tag(row.shelf_life_months + ' tháng'); }
                    }),
                    col({
                        className: 'text-center',
                        display: function(row) {
                            return cfg.can.internal ? internalBtn(row, 'btn-primary', 'Xác định') : '';
                        }
                    })
                ]
            }));
        }

        /* ---------- Dựng bảng theo tab ---------- */
        var builders = {
            invPaneCode: { table: 'mdTable', build: buildCode, data: 'rows' },
            invPaneChem: { table: 'invSummaryTable', build: buildChem, data: 'summaries' },
            invPaneZone: { table: 'invZoneMap', build: buildZone, grid: true },
            invPaneExpiring: { table: 'invExpiringTable', build: buildExpiring, data: 'expiring' },
            invPaneInternal: { table: 'invInternalTable', build: buildInternal, data: 'internal' }
        };

        /** Dựng bảng của một tab nếu chưa dựng, dữ liệu đã về và tab được phép xem */
        function ensure(pane) {
            var item = builders[pane];

            if (!payload || !item || built[pane] || !document.getElementById(item.table)) return;

            built[pane] = true;
            item.build();

            if (item.grid) return;

            $('#' + item.table + '_length select').attr('title', 'Số dòng hiển thị mỗi trang');

            // Gom chip trạng thái, quét mã, phụ lục, Hiển thị / Tìm kiếm về một thanh
            if (window.mdTablebar) window.mdTablebar(document.getElementById(item.table));

            if (window.clsRefreshCounts) window.clsRefreshCounts(item.table);
        }

        function activePane() {
            return $('.inv-pane.is-active').attr('id');
        }

        $(document).on('inv:pane-shown', function(e, pane) {
            ensure(pane);
        });

        // Quét mã vạch tìm trên mọi tab nên phải dựng đủ các bảng trước
        $(document).on('bcs:before-find', function() {
            Object.keys(builders).forEach(ensure);
        });

        /** Số đếm trên nút lọc trạng thái và trên tab, tính từ đúng dữ liệu vừa tải */
        function fillCounts() {
            var counts = {};

            payload.rows.forEach(function(row) {
                counts[row.state] = (counts[row.state] || 0) + 1;
            });

            $('.inv-chip[data-state]').each(function() {
                var state = $(this).data('state');

                $(this).find('.count').text(state === 'all' ? payload.rows.length : (counts[state] || 0));
            });

            $('[data-inv-count="expiring"]').text(payload.expiring.length);

            $('.inv-chip[data-expiry]').each(function() {
                var key = $(this).data('expiry');

                $(this).find('.count').text(payload.expiring.filter(function(row) {
                    return inExpiryFilter(row, key);
                }).length);
            });
            $('[data-inv-count="internal"]').text(payload.internal.length);
        }

        function showError(message) {
            $('.inv-loading-row td').html('<span class="text-danger"><i class="fas fa-exclamation-triangle mr-1"></i>' +
                esc(message) + '</span>');
        }

        /* ---------- Lọc thuộc tính Hoá chất VS - áp cho mọi tab ---------- */
        var vsFilter = cfg.vs || 'all';

        function vsMatch(row) {
            return vsFilter === 'all' || (vsFilter === 'yes') === !!row.vs;
        }

        /** Tập dữ liệu của các tab theo bộ lọc VS đang chọn */
        function compose() {
            var rows = source.rows.filter(vsMatch);

            payload = {
                rows: rows,
                // Lọc VS thì dùng bản cộng dồn tách theo VS để tổng khớp đúng các mã đang hiện
                summaries: vsFilter === 'all' ? source.summaries : source.summariesVs.filter(vsMatch),
                // Còn tồn và hạn áp dụng rơi vào 6 tháng tới - gần hết hạn nhất lên trước
                expiring: rows.filter(function(row) { return row.is_expiring_soon; })
                    .sort(function(a, b) {
                        return String(a.effective_expired_date || '').localeCompare(String(b.effective_expired_date || ''));
                    }),
                // Hoá chất có hạn dùng mặc định nhưng phiếu chưa xác định hạn nội bộ
                internal: rows.filter(function(row) { return row.shelf_life_months > 0 && !row.internal_expired_date; })
            };
        }

        /** Nạp lại dữ liệu vào các bảng đã dựng; sơ đồ định khu dựng lại khi đang mở, không thì lúc mở tab */
        function reloadBuilt() {
            Object.keys(builders).forEach(function(pane) {
                var item = builders[pane];

                if (!built[pane]) return;

                if (item.grid) {
                    built[pane] = false;

                    return;
                }

                $('#' + item.table).DataTable().clear().rows.add(payload[item.data]).draw();

                if (window.clsRefreshCounts) window.clsRefreshCounts(item.table);
            });

            ensure(activePane());
        }

        $('#invVsFilter').on('change', function() {
            vsFilter = $(this).val();
            $(this).toggleClass('is-on', vsFilter !== 'all');

            if (!source) return;

            compose();
            fillCounts();
            reloadBuilt();
        });

        $.getJSON(cfg.dataUrl, cfg.period).done(function(res) {
            source = {
                rows: res.rows || [],
                summaries: res.summaries || [],
                summariesVs: res.summaries_vs || []
            };

            compose();
            fillCounts();
            ensure(activePane());
        }).fail(function(xhr) {
            showError((xhr.responseJSON && xhr.responseJSON.message) || 'Không tải được dữ liệu tồn kho, vui lòng tải lại trang.');
        });
    });
</script>
