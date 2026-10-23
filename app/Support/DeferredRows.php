<?php

namespace App\Support;

/**
 * DÒNG BẢNG NẠP SAU (AJAX) CHO CÁC TRANG DANH SÁCH LỚN
 *
 * Trang có hàng nghìn dòng mà render sẵn toàn bộ <tr> thì HTML lên tới hàng chục MB và
 * trình duyệt phải dựng hàng trăm nghìn thẻ trước khi DataTables kịp cắt ra 25 dòng.
 * Cơ chế này giữ NGUYÊN markup dòng trong Blade, chỉ đổi thời điểm gửi:
 *
 *   1. Lần tải trang: phần nằm giữa @deferredRows('idBảng') ... @enddeferredRows KHÔNG
 *      render, bảng gắn data-deferred chỉ có thead.
 *   2. JS (pages/shared/deferredRows) gọi lại đúng URL đó kèm ?_deferred=1. Lần này các
 *      dòng được render vào bộ đệm, middleware ServeDeferredRows trả JSON
 *      {tables: {idBảng: [dòng]}} thay cho trang HTML.
 *   3. DataTables nhận dòng dạng dữ liệu + deferRender: chỉ dựng <tr> của trang đang xem.
 *
 * Mỗi dòng JSON: {a: thuộc tính <tr>, c: [ô]}, mỗi ô {h: HTML bên trong, o: data-order,
 * s: data-search, a: các thuộc tính khác của <td>}. Phía JS đặt lại thuộc tính khi dựng
 * <tr> nên CSS, data-row của nút và các modal dùng chung chạy không đổi.
 *
 * Bảng data-deferred="lazy" (bảng chọn trong modal, không phải DataTables) không lấy lúc
 * tải trang: JS màn hình gọi mdDeferred.load(id) khi mở modal lần đầu, lượt gọi kèm
 * ?_tables=id nên server chỉ render đúng bảng đó.
 */
class DeferredRows
{
    /** Tham số query của lượt gọi lấy dòng. */
    public const PARAM = '_deferred';

    /** Tham số query giới hạn các bảng cần lấy (id cách nhau dấu phẩy). */
    public const TABLES = '_tables';

    /** @var string[] Bảng đang mở bộ đệm (cho phép lồng nhau) */
    private static array $stack = [];

    /** @var array<string, string> HTML các dòng đã render, theo id bảng */
    private static array $html = [];

    /** Request hiện tại là lượt lấy dòng (?_deferred=1). */
    public static function requested(): bool
    {
        return request()->query(self::PARAM) === '1';
    }

    /**
     * Mở vùng dòng của một bảng. Lần tải trang trả false để Blade bỏ qua cả vùng; lượt
     * lấy dòng thì bắt đầu ghi bộ đệm và trả true.
     */
    public static function start(string $table): bool
    {
        if (! self::requested()) {
            return false;
        }

        // ?_tables=a,b chỉ xin vài bảng (bảng "lazy" lấy khi mở modal/tab): bảng khác bỏ qua
        // luôn phần render để lượt gọi nhẹ nhất có thể
        $only = request()->query(self::TABLES);

        if (is_string($only) && $only !== '' && ! in_array($table, explode(',', $only), true)) {
            return false;
        }

        self::$stack[] = $table;
        ob_start();

        return true;
    }

    public static function end(): void
    {
        $table = array_pop(self::$stack);
        $html = (string) ob_get_clean();

        self::$html[$table] = (self::$html[$table] ?? '').$html;
    }

    /** @return array<string, array> dòng của mọi bảng đã ghi trong request */
    public static function payload(): array
    {
        $out = [];

        foreach (self::$html as $table => $html) {
            $out[$table] = self::parse($html);
        }

        return $out;
    }

    /** Tách chuỗi <tr>...</tr> thành mảng dòng / ô. */
    private static function parse(string $html): array
    {
        if (trim($html) === '') {
            return [];
        }

        // Gộp khoảng trắng thụt lề của Blade (chiếm phần lớn dung lượng). Bỏ qua khi
        // trong dòng có nội dung mà khoảng trắng mang nghĩa.
        if (! preg_match('/<(textarea|pre|script)\b/i', $html)) {
            $html = preg_replace('/\s+/u', ' ', $html);
        }

        $doc = new \DOMDocument();
        $previous = libxml_use_internal_errors(true);
        $doc->loadHTML(
            '<?xml encoding="utf-8"?><table><tbody>'.$html.'</tbody></table>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_COMPACT | LIBXML_PARSEHUGE
        );
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $tbody = $doc->getElementsByTagName('tbody')->item(0);
        $rows = [];

        foreach ($tbody ? $tbody->childNodes : [] as $tr) {
            if (! $tr instanceof \DOMElement || strtolower($tr->tagName) !== 'tr') {
                continue;
            }

            $row = ['c' => []];

            if ($attrs = self::attributes($tr)) {
                $row['a'] = $attrs;
            }

            foreach ($tr->childNodes as $td) {
                if (! $td instanceof \DOMElement || ! in_array(strtolower($td->tagName), ['td', 'th'], true)) {
                    continue;
                }

                $cell = ['h' => self::innerHtml($doc, $td)];
                $attrs = self::attributes($td);

                // Giá trị sắp xếp / tìm kiếm riêng của DataTables (HTML5 data-*)
                foreach (['o' => ['data-order', 'data-sort'], 's' => ['data-search', 'data-filter']] as $key => $names) {
                    foreach ($names as $name) {
                        if (array_key_exists($name, $attrs)) {
                            $cell[$key] = $attrs[$name];
                            unset($attrs[$name]);
                        }
                    }
                }

                if ($attrs) {
                    $cell['a'] = $attrs;
                }

                $row['c'][] = $cell;
            }

            $rows[] = $row;
        }

        return $rows;
    }

    private static function attributes(\DOMElement $element): array
    {
        $out = [];

        foreach ($element->attributes as $attribute) {
            $out[$attribute->name] = $attribute->value;
        }

        return $out;
    }

    private static function innerHtml(\DOMDocument $doc, \DOMElement $element): string
    {
        $html = '';

        foreach ($element->childNodes as $child) {
            $html .= $doc->saveHTML($child);
        }

        return trim($html);
    }
}
