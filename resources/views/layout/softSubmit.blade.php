{{--
|--------------------------------------------------------------------------
| GỬI FORM NGẦM - không tải lại trang (Dữ Liệu Gốc + Danh Mục)
|--------------------------------------------------------------------------
| Thao tác trên MỘT bản ghi đang có - Sửa, Khoá / Mở khoá, Duyệt, Từ chối, Xác nhận
| cổng TT quốc gia, Xoá (định khu) - gửi bằng fetch thay vì tải lại trang, nên trang
| giữ nguyên: bộ lọc, ô tìm kiếm, trang đang xem, sắp xếp, tab, vị trí cuộn.
|
| Không phải sửa từng màn hình:
|   - Form POST có action kết thúc bằng /update, /deActive, /approve, /reject,
|     /declarePortal, /destroy được nhận ra theo URL (kể cả form tạm của hộp xác nhận
|     mdConfirmSubmit và form.submit() gọi từ JS của màn hình).
|   - Controller giữ nguyên redirect()->back()->with(...); middleware SoftSubmit đổi
|     phản hồi thành JSON {success, error, warning, errors} khi có header X-WMS-Soft.
|   - Thành công: đóng modal, báo kết quả, lấy lại dòng của mọi bảng ngoài modal -
|     bảng data-deferred qua mdDeferred.refresh, bảng dựng sẵn lấy tbody từ chính URL
|     đang xem - rồi vẽ lại với draw(false) và phát 'md:rows-loaded' để bộ lọc đếm lại.
|   - Lỗi kiểm tra dữ liệu: hiện ngay dưới ô trong modal, modal giữ nguyên.
|
| Thêm mới vẫn tải lại trang như cũ. Form muốn đứng ngoài gắn data-soft-submit="off";
| form muốn gửi ngầm dù action khác mẫu gắn data-soft-submit="on". Khối ngoài bảng
| cần làm mới theo (số đếm trên tab...) gắn id + data-soft-refresh.
|
| Chỉ nạp ở trang pages.materData.* / pages.category.* (layout/js.blade.php).
--}}
<script>
    (function($) {
        if (!$ || !window.fetch || !window.FormData || !window.DOMParser) return;

        var ACTIONS = /\/(update|deActive|approve|reject|declarePortal|destroy)\/?$/;
        var nativeSubmit = HTMLFormElement.prototype.submit;

        function actionUrl(form) {
            // Không đọc form.action: ô <input name="action"> sẽ che mất thuộc tính
            try {
                return new URL(form.getAttribute('action') || window.location.href, window.location.href);
            } catch (e) {
                return null;
            }
        }

        function handles(form) {
            if (!form || form.nodeName !== 'FORM') return false;
            if ((form.getAttribute('method') || 'GET').toUpperCase() !== 'POST') return false;
            if (form.closest('[data-soft-submit="off"]')) return false;

            var target = form.getAttribute('target');
            if (target && target !== '_self') return false;

            var url = actionUrl(form);
            if (!url || url.origin !== window.location.origin) return false;

            return form.getAttribute('data-soft-submit') === 'on' || ACTIONS.test(url.pathname);
        }

        /* ---------- Thông báo ---------- */
        function escapeHtml(text) {
            return String(text === null || text === undefined ? '' : text)
                .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
        }

        function popup(options) {
            if (window.Swal) return Swal.fire(options);

            window.alert(options.text || $('<div>').html(options.html || '').text() || options.title);
            return Promise.resolve({});
        }

        function showError(message, title) {
            return popup({
                title: title || 'Không thực hiện được',
                html: escapeHtml(message).replace(/\n/g, '<br>'),
                icon: 'error',
                confirmButtonColor: '#2E7BC4',
                confirmButtonText: 'Đã hiểu'
            });
        }

        // Thành công (tự đóng) -> Lưu ý -> Lỗi: hiện lần lượt như flash của mdFlash
        function notify(data) {
            var chain = Promise.resolve();

            if (data.success) {
                chain = chain.then(function() {
                    return popup({ title: 'Thành công!', text: data.success, icon: 'success', timer: 1800, showConfirmButton: false });
                });
            }

            if (data.warning) {
                chain = chain.then(function() {
                    return popup({ title: 'Lưu ý', text: data.warning, icon: 'warning', confirmButtonColor: '#F59E0B', confirmButtonText: 'Đã hiểu' });
                });
            }

            if (data.error) {
                chain = chain.then(function() { return showError(data.error); });
            }

            if (!data.success && !data.warning && !data.error && window.Swal && Swal.isVisible()) {
                Swal.close();
            }

            return chain;
        }

        function showWaiting() {
            if (!window.Swal) return;

            Swal.fire({
                title: 'Đang xử lý...',
                allowOutsideClick: false,
                allowEscapeKey: false,
                showConfirmButton: false,
                didOpen: function() { Swal.showLoading(); }
            });
        }

        function expired(message) {
            popup({
                title: 'Hết phiên làm việc',
                text: message || 'Phiên làm việc đã hết hạn, vui lòng tải lại trang và đăng nhập lại.',
                icon: 'warning',
                allowOutsideClick: false,
                confirmButtonColor: '#2E7BC4',
                confirmButtonText: 'Tải lại trang'
            }).then(function() { window.location.reload(); });
        }

        /* ---------- Lỗi kiểm tra dữ liệu trong modal ---------- */
        function clearErrors(form) {
            var $root = $(form).closest('.modal');
            if (!$root.length) $root = $(form);

            $root.find('.wms-soft-error').remove();
            $root.find('.wms-soft-invalid').removeClass('is-invalid wms-soft-invalid');
        }

        // "items.0.amount" -> items.0.amount | items[0][amount] | items[0][amount][] | items[] | items
        function findField(form, key) {
            var parts = String(key).split('.');
            var names = [key, key + '[]'];

            if (parts.length > 1) {
                var bracket = parts[0] + parts.slice(1).map(function(p) { return '[' + p + ']'; }).join('');
                names.push(bracket, bracket + '[]', parts[0] + '[]', parts[0]);
            }

            for (var i = 0; i < names.length; i++) {
                var el = form.querySelector('[name="' + names[i].replace(/(["\\])/g, '\\$1') + '"]');
                if (el && el.type !== 'hidden') return el;
            }

            return null;
        }

        function placeError(el, text) {
            var $el = $(el);
            var $anchor = $el;

            $el.addClass('is-invalid wms-soft-invalid');

            if (el.type === 'checkbox' || el.type === 'radio') {
                var $group = $el.closest('.cat-check-group');
                $anchor = $group.length ? $group.addClass('is-invalid wms-soft-invalid') :
                    ($el.closest('.form-check, .custom-control').length ? $el.closest('.form-check, .custom-control') : $el);
            } else if ($el.next('.select2-container').length) {
                $anchor = $el.next('.select2-container');
            } else if ($el.parent('.input-group').length) {
                $anchor = $el.parent();
            }

            if ($anchor.next('.wms-soft-error').length) return;

            $('<span class="md-error invalid-feedback d-block wms-soft-error"></span>').text(text).insertAfter($anchor);
        }

        // bags: {tênBag: {ô: [câu lỗi]}}
        function showErrors(form, bags) {
            var modal = $(form).closest('.modal')[0];
            var loose = [];
            var first = null;

            $.each(bags || {}, function(bag, fields) {
                $.each(fields || {}, function(key, list) {
                    var text = $.isArray(list) ? list[0] : list;
                    var el = modal ? findField(form, key) : null;

                    if (el) {
                        placeError(el, text);
                        first = first || el;
                    } else if (loose.indexOf(text) === -1) {
                        loose.push(text);
                    }
                });
            });

            if (!modal) {
                return showError(loose.join('\n') || 'Dữ liệu chưa hợp lệ.');
            }

            if (loose.length) {
                var $box = $('<div class="alert alert-danger wms-soft-error"><ul class="mb-0 pl-3"></ul></div>');

                loose.forEach(function(text) { $box.find('ul').append($('<li>').text(text)); });

                var $body = $(form).find('.modal-body').first();
                ($body.length ? $body : $(form)).prepend($box);
                $(modal).scrollTop(0);
            }

            if (first && !loose.length) {
                try { first.focus({ preventScroll: false }); } catch (e) { first.focus(); }
            }
        }

        // Đóng modal thì gỡ lỗi do lần gửi ngầm trước để lại
        $(document).on('hidden.bs.modal', '.modal', function() {
            $(this).find('.wms-soft-error').remove();
            $(this).find('.wms-soft-invalid').removeClass('is-invalid wms-soft-invalid');
        });

        /* ---------- Giữ vị trí cuộn khi vẽ lại bảng ---------- */
        function keepView(fn) {
            var spots = [{ el: window, top: window.pageYOffset, left: window.pageXOffset }];

            $('.content-wrapper, .table-responsive, .dataTables_scrollBody').each(function() {
                spots.push({ el: this, top: this.scrollTop, left: this.scrollLeft });
            });

            fn();

            spots.forEach(function(spot) {
                if (spot.el === window) {
                    window.scrollTo(spot.left, spot.top);
                } else {
                    spot.el.scrollTop = spot.top;
                    spot.el.scrollLeft = spot.left;
                }
            });
        }

        /* ---------- Làm mới dòng bảng ---------- */
        function isEmptyRow(tr) {
            var cells = $(tr).children('td, th');
            return cells.length === 1 && parseInt(cells.attr('colspan') || 1, 10) > 1;
        }

        function fetchPage() {
            var url = new URL(window.location.href);
            url.hash = '';

            return fetch(url.toString(), { credentials: 'same-origin', cache: 'no-store' })
                .then(function(res) {
                    if (!res.ok || res.redirected) throw new Error('refresh');
                    return res.text();
                })
                .then(function(html) {
                    return new DOMParser().parseFromString(html, 'text/html');
                });
        }

        function applyPage(doc, tables, blocks) {
            keepView(function() {
                tables.forEach(function(table) {
                    var fresh = doc.getElementById(table.id);
                    if (!fresh || fresh.nodeName !== 'TABLE') return;

                    var $freshBody = $(fresh).children('tbody');

                    if (!$.fn.dataTable || !$.fn.dataTable.isDataTable(table)) {
                        $(table).children('tbody').html($freshBody.html() || '');
                        return;
                    }

                    var rows = $freshBody.children('tr').get()
                        .filter(function(tr) { return !isEmptyRow(tr); })
                        .map(function(tr) { return document.importNode(tr, true); });
                    var api = $(table).DataTable();

                    api.clear().rows.add(rows).draw(false);

                    $(table).trigger('md:rows-loaded', [api]);
                    if (window.clsRefreshCounts) window.clsRefreshCounts(table.id);
                });

                blocks.forEach(function(el) {
                    var fresh = doc.getElementById(el.id);
                    if (fresh) el.innerHTML = fresh.innerHTML;
                });

                syncAttributes(doc);
            });
        }

        /*
         | Khối gắn data-soft-sync="<khoá>" (dữ liệu JSON nằm trên thuộc tính data-*, ví dụ
         | khối Quy đổi đơn vị trong modal): chép lại các thuộc tính data-* từ trang mới và
         | xoá bộ đệm .data() của jQuery để lần đọc sau lấy giá trị mới. Ghép theo thứ tự
         | xuất hiện của cùng một khoá.
        */
        function syncAttributes(doc) {
            var seen = {};

            $('[data-soft-sync]').each(function() {
                var el = this;
                var key = el.getAttribute('data-soft-sync');
                var index = seen[key] = (seen[key] || 0) + 1;
                var fresh = doc.querySelectorAll('[data-soft-sync="' + key.replace(/(["\\])/g, '\\$1') + '"]')[index - 1];
                var names = [];

                if (!fresh) return;

                $.each(fresh.attributes, function(i, attr) {
                    if (attr.name.indexOf('data-') === 0 && attr.name !== 'data-soft-sync') {
                        names.push(attr.name.slice(5));
                    }
                });

                names.forEach(function(name) {
                    el.setAttribute('data-' + name, fresh.getAttribute('data-' + name));
                });

                $(el).removeData(names);
            });
        }

        function refresh() {
            var deferredIds = [];
            var tables = [];
            var blocks = $('[data-soft-refresh][id]').not('[data-soft-refresh="off"]').not('table').get();

            $('table[id]').each(function() {
                var $table = $(this);

                if ($table.closest('.modal, [data-soft-refresh="off"]').length) return;

                if ($table.is('[data-deferred]')) {
                    if (!$table.is('[data-deferred="lazy"]')) deferredIds.push(this.id);
                } else if (($.fn.dataTable && $.fn.dataTable.isDataTable(this)) ||
                    ($table.is('#mdTable, .md-table') && !$table.is('[data-no-datatable]'))) {
                    tables.push(this);
                }
            });

            var jobs = [];

            if (deferredIds.length && window.mdDeferred && mdDeferred.refresh) {
                jobs.push(Promise.resolve(mdDeferred.refresh(deferredIds, keepView)));
            }

            if (tables.length || blocks.length || $('[data-soft-sync]').length) {
                jobs.push(fetchPage().then(function(doc) { applyPage(doc, tables, blocks); }));
            }

            return Promise.all(jobs).then(function() {
                // Màn hình giữ dữ liệu riêng trong biến JS (vd cây cấp cha của Định Khu) đọc lại tại đây
                document.dispatchEvent(new CustomEvent('wms:soft-refreshed'));
            }).catch(function() {
                popup({
                    title: 'Đã lưu',
                    text: 'Thao tác đã thực hiện nhưng chưa làm mới được bảng. Tải lại trang để xem dữ liệu mới nhất.',
                    icon: 'info',
                    confirmButtonColor: '#2E7BC4',
                    confirmButtonText: 'Tải lại trang'
                }).then(function() { window.location.reload(); });
            });
        }

        /* ---------- Gửi ---------- */
        function lockButtons(form, submitter) {
            var $buttons = $(form).find('button[type="submit"], button:not([type]), input[type="submit"]');

            if (form.id) $buttons = $buttons.add('[type="submit"][form="' + form.id + '"]');

            $buttons = $buttons.filter(':not(:disabled)');

            var $main = submitter && $buttons.is(submitter) ? $(submitter) : $buttons.first();
            var html = $main.is('button') ? $main.html() : null;

            $buttons.prop('disabled', true);
            if (html !== null) $main.html('<i class="fas fa-circle-notch fa-spin mr-1"></i> Đang lưu...');

            return function() {
                $buttons.prop('disabled', false);
                if (html !== null) $main.html(html);
            };
        }

        /*
         | Đóng modal bằng nút [data-dismiss] thật: vài màn hình cũ nạp thêm một bản
         | jQuery/Bootstrap riêng, modal mở bằng bản nào thì .modal('hide') của bản kia
         | không đóng được - còn cú click thì cả hai bản đều nhận.
        */
        function closeModal(modal, tries) {
            var instance = $(modal).data('bs.modal');

            tries = tries || 0;

            // Modal còn đang chạy hiệu ứng mở thì Bootstrap bỏ qua lệnh đóng: chờ hiệu ứng xong
            if (instance && instance._isTransitioning && tries < 20) {
                setTimeout(function() { closeModal(modal, tries + 1); }, 100);
                return;
            }

            var close = $(modal).find('[data-dismiss="modal"]').filter(function() {
                return $(this).closest('.modal')[0] === modal;
            }).get(0);

            if (close) close.click();
            if ($(modal).hasClass('show')) $(modal).modal('hide');
        }

        function readResponse(res) {
            var type = res.headers.get('Content-Type') || '';
            var base = { status: res.status, redirected: res.redirected, url: res.url, json: null };

            if (type.indexOf('application/json') === -1) return Promise.resolve(base);

            return res.json().then(function(json) {
                return $.extend(base, { json: json });
            }, function() {
                return base;
            });
        }

        function send(form, submitter) {
            if (form.__wmsSoftBusy) return;

            var url = actionUrl(form);
            var data = new FormData(form);
            var modal = $(form).closest('.modal')[0] || null;

            if (submitter && submitter.name && !data.has(submitter.name)) {
                data.append(submitter.name, submitter.value || '');
            }

            if (!data.has('_token')) {
                data.append('_token', $('input[name="_token"]').first().val() || '');
            }

            form.__wmsSoftBusy = true;
            clearErrors(form);

            var unlock = modal ? lockButtons(form, submitter) : function() {};

            if (!modal) showWaiting();

            fetch(url.toString(), {
                method: 'POST',
                body: data,
                credentials: 'same-origin',
                headers: {
                    'X-WMS-Soft': '1',
                    'X-WMS-Page': window.location.href,
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            }).then(readResponse, function() {
                return { network: true };
            }).then(function(result) {
                var json = result.json || {};

                form.__wmsSoftBusy = false;
                unlock();

                if (result.network) {
                    return showError('Không kết nối được máy chủ. Vui lòng kiểm tra mạng rồi thử lại.');
                }

                if (result.status === 419 || result.status === 401) return expired(json.message);

                // Laravel tự trả 422 khi Controller dùng $request->validate()
                if (result.status === 422) {
                    return showErrors(form, { default: json.errors || {} });
                }

                if (result.status >= 400) {
                    return showError(json.message || ('Máy chủ báo lỗi (' + result.status + '). Vui lòng thử lại hoặc tải lại trang.'));
                }

                // Phản hồi là trang HTML (không qua được middleware SoftSubmit): mở đúng trang đó
                if (!result.json) {
                    if (result.redirected && result.url) {
                        window.location.href = result.url;
                    } else {
                        window.location.reload();
                    }
                    return;
                }

                if (json.redirect) {
                    window.location.href = json.redirect;
                    return;
                }

                var bags = json.errors || {};

                if (json.soft && Object.keys(bags).length) {
                    if (window.Swal && Swal.isVisible()) Swal.close();
                    return showErrors(form, bags);
                }

                // Báo lỗi nghiệp vụ (không tìm thấy, chưa có thay đổi, sai mật khẩu...): giữ nguyên modal
                if (json.soft && json.error && !json.success) {
                    return showError(json.error);
                }

                if (modal) closeModal(modal);

                notify(json.soft ? json : { success: json.message });
                refresh();
            });
        }

        // Nút submit trong modal: chạy sau mọi handler của màn hình (kiểm tra dữ liệu, xác nhận...)
        window.addEventListener('submit', function(e) {
            var form = e.target;

            if (e.defaultPrevented || !handles(form)) return;

            e.preventDefault();
            send(form, e.submitter || null);
        });

        // form.submit() từ JS (hộp xác nhận Khoá / Duyệt / Từ chối) không phát sự kiện submit
        HTMLFormElement.prototype.submit = function() {
            if (handles(this)) return send(this, null);

            return nativeSubmit.call(this);
        };

        window.wmsSoft = {
            handles: handles,
            refresh: refresh
        };
    })(window.jQuery);
</script>
