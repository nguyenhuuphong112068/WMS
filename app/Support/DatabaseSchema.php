<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * CƠ SỞ DỮ LIỆU - CẤU TRÚC & QUAN HỆ (trang quản trị chỉ dành cho Admin)
 *
 * Ghép hai nguồn, cả hai đều đọc lại mỗi lần mở trang nên luôn khớp hiện trạng:
 *
 *   1. CSDL thật (information_schema): bảng, trường, chỉ mục, khoá ngoại.
 *   2. Mã nguồn (DatabaseCodeScanner): nơi dùng bảng, điều kiện join, quan hệ model,
 *      lịch sử migration.
 *
 * Quan hệ giữa hai bảng được xác định theo thứ tự tin cậy:
 *   fk    - khoá ngoại khai thật trong CSDL
 *   join  - điều kiện nối cột trong code: join(...), ->on(...), ->whereColumn(...)
 *   model - quan hệ Eloquent belongsTo / hasMany / belongsToMany
 *   name  - suy theo quy ước tên trường *_id (chỉ khi 3 nguồn trên không có)
 *
 * Chiều quan hệ: bên giữ khoá (from) -> bên được tham chiếu (to, thường là id).
 * card: n1 = nhiều-một, 11 = một-một, nn = join ngang giữa hai trường không phải khoá.
 */
class DatabaseSchema
{
    const GROUPS = [
        'material' => 'Vật tư',
        'chemical' => 'Hoá chất',
        'standard' => 'Chất chuẩn',
        'master'   => 'Dữ liệu gốc',
        'user'     => 'Người dùng & phân quyền',
        'utility'  => 'Tiện ích',
        'system'   => 'Hệ thống Laravel',
        'other'    => 'Khác',
    ];

    /** Gán nhóm theo tên bảng đầy đủ - xét trước tiền tố */
    const GROUP_TABLES = [
        'system'   => ['migrations', 'cache', 'cache_locks', 'sessions', 'jobs', 'job_batches', 'failed_jobs', 'password_reset_tokens', 'users', 'personal_access_tokens'],
        'user'     => ['user_management', 'roles', 'permissions', 'permission_groups', 'role_permission', 'password_histories', 'audittriallog'],
        'utility'  => ['notifications', 'notification_recipients', 'estimate_item_chats'],
        'material' => ['consumption_objects', 'department_classification'],
        'chemical' => ['mixture_hazard_categories'],
        'master'   => [
            'companies', 'deparments', 'groups', 'units', 'statuses', 'warehouses', 'shelves', 'columns', 'tiers',
            'locations', 'manufacturers', 'suppliers', 'packaging_specifications', 'storage_conditions', 'purposes',
            'product_names', 'product_name', 'datamaster_histories', 'category_unit_conversions',
        ],
    ];

    const GROUP_PREFIXES = [
        'material_'         => 'material',
        'chemical_'         => 'chemical',
        'chem_'             => 'chemical',
        'active_ingredient' => 'chemical',
        'standard_'         => 'standard',
        'user_'             => 'user',
        'chat_'             => 'utility',
        'notification'      => 'utility',
    ];

    /** Trường *_id không theo quy ước số nhiều: gốc tên => bảng */
    const NAME_ALIASES = [
        'user'       => ['user_management'],
        'department' => ['deparments'],
        'parent'     => ['@self'],
    ];

    /**
     * Trường *_id không đoán bảng theo tên. status_id của bảng nghiệp vụ là cờ hoạt động
     * 1/0 (deActive đảo giá trị), không trỏ tới bảng statuses; chỗ nào code thật sự join
     * sang statuses thì vẫn nhận qua nguồn "join".
     */
    const NAME_IGNORE = ['status_id'];

    /** Số vị trí code tối đa giữ cho mỗi quan hệ / mỗi file */
    const MAX_REFS = 30;

    /*
    |--------------------------------------------------------------------------
    | Đọc CSDL
    |--------------------------------------------------------------------------
    */

    private static function isMysql(): bool
    {
        return in_array(DB::connection()->getDriverName(), ['mysql', 'mariadb'], true);
    }

    /** Tên mọi bảng hiện có trong CSDL */
    public static function tableNames(): array
    {
        if (self::isMysql()) {
            return DB::table('information_schema.TABLES')
                ->where('TABLE_SCHEMA', DB::connection()->getDatabaseName())
                ->pluck('TABLE_NAME')
                ->all();
        }

        return array_column(Schema::getTables(), 'name');
    }

    /**
     * @return array{tables: array, columns: array, indexes: array, foreign: array}
     *   tables  : [tên => [type, engine, rows, size, comment, created]]
     *   columns : [bảng => [[name, type, dtype, nullable, default, key, extra, comment]]]
     *   indexes : [bảng => [tên => [unique, primary, columns[]]]]
     *   foreign : [[bảng, cột, bảng tham chiếu, cột tham chiếu, tên ràng buộc]]
     */
    private static function readSchema(): array
    {
        return self::isMysql() ? self::readMysql() : self::readGeneric();
    }

    private static function readMysql(): array
    {
        $db = DB::connection()->getDatabaseName();

        $tables = [];
        $rows = DB::table('information_schema.TABLES')
            ->where('TABLE_SCHEMA', $db)
            ->select(
                'TABLE_NAME as name', 'TABLE_TYPE as type', 'ENGINE as engine', 'TABLE_ROWS as rows',
                'DATA_LENGTH as data_length', 'INDEX_LENGTH as index_length', 'TABLE_COMMENT as comment',
                'CREATE_TIME as created'
            )
            ->orderBy('TABLE_NAME')
            ->get();

        foreach ($rows as $r) {
            $tables[$r->name] = [
                'type'    => $r->type === 'VIEW' ? 'view' : 'table',
                'engine'  => $r->engine,
                'rows'    => (int) $r->rows,
                'size'    => (int) $r->data_length + (int) $r->index_length,
                'comment' => (string) $r->comment,
                'created' => $r->created,
            ];
        }

        $columns = [];
        $rows = DB::table('information_schema.COLUMNS')
            ->where('TABLE_SCHEMA', $db)
            ->select(
                'TABLE_NAME as t', 'COLUMN_NAME as name', 'COLUMN_TYPE as type', 'DATA_TYPE as dtype',
                'IS_NULLABLE as nullable', 'COLUMN_DEFAULT as def', 'COLUMN_KEY as ckey', 'EXTRA as extra',
                'COLUMN_COMMENT as comment'
            )
            ->orderBy('TABLE_NAME')
            ->orderBy('ORDINAL_POSITION')
            ->get();

        foreach ($rows as $r) {
            $columns[$r->t][] = [
                'name'     => $r->name,
                'type'     => $r->type,
                'dtype'    => $r->dtype,
                'nullable' => $r->nullable === 'YES',
                'default'  => $r->def,
                'key'      => $r->ckey,
                'extra'    => $r->extra,
                'comment'  => (string) $r->comment,
            ];
        }

        $indexes = [];
        $rows = DB::table('information_schema.STATISTICS')
            ->where('TABLE_SCHEMA', $db)
            ->select('TABLE_NAME as t', 'INDEX_NAME as name', 'NON_UNIQUE as non_unique', 'COLUMN_NAME as col')
            ->orderBy('TABLE_NAME')
            ->orderBy('INDEX_NAME')
            ->orderBy('SEQ_IN_INDEX')
            ->get();

        foreach ($rows as $r) {
            $indexes[$r->t][$r->name] ??= [
                'unique'  => ! (int) $r->non_unique,
                'primary' => $r->name === 'PRIMARY',
                'columns' => [],
            ];
            $indexes[$r->t][$r->name]['columns'][] = $r->col;
        }

        $foreign = DB::table('information_schema.KEY_COLUMN_USAGE')
            ->where('TABLE_SCHEMA', $db)
            ->whereNotNull('REFERENCED_TABLE_NAME')
            ->select('TABLE_NAME as t', 'COLUMN_NAME as c', 'REFERENCED_TABLE_NAME as rt', 'REFERENCED_COLUMN_NAME as rc', 'CONSTRAINT_NAME as name')
            ->get()
            ->map(fn ($r) => [$r->t, $r->c, $r->rt, $r->rc, $r->name])
            ->all();

        return compact('tables', 'columns', 'indexes', 'foreign');
    }

    /** CSDL khác MySQL (SQLite khi chạy test...): dùng Schema builder của Laravel */
    private static function readGeneric(): array
    {
        $tables = [];
        $columns = [];
        $indexes = [];
        $foreign = [];

        foreach (Schema::getTables() as $t) {
            $name = $t['name'];
            $tables[$name] = [
                'type'    => 'table',
                'engine'  => $t['engine'] ?? null,
                'rows'    => 0,
                'size'    => (int) ($t['size'] ?? 0),
                'comment' => (string) ($t['comment'] ?? ''),
                'created' => null,
            ];

            $uniqueCols = [];
            foreach (Schema::getIndexes($name) as $i) {
                $indexes[$name][$i['name']] = [
                    'unique'  => (bool) $i['unique'],
                    'primary' => (bool) $i['primary'],
                    'columns' => $i['columns'],
                ];
                if (count($i['columns']) === 1) {
                    $uniqueCols[$i['columns'][0]] = $i['primary'] ? 'PRI' : ($i['unique'] ? 'UNI' : 'MUL');
                }
            }

            foreach (Schema::getColumns($name) as $c) {
                $columns[$name][] = [
                    'name'     => $c['name'],
                    'type'     => $c['type'],
                    'dtype'    => $c['type_name'],
                    'nullable' => (bool) $c['nullable'],
                    'default'  => $c['default'],
                    'key'      => $uniqueCols[$c['name']] ?? '',
                    'extra'    => $c['auto_increment'] ? 'auto_increment' : '',
                    'comment'  => (string) ($c['comment'] ?? ''),
                ];
            }

            foreach (Schema::getForeignKeys($name) as $f) {
                foreach ($f['columns'] as $k => $col) {
                    $foreign[] = [$name, $col, $f['foreign_table'], $f['foreign_columns'][$k] ?? 'id', $f['name']];
                }
            }
        }

        return compact('tables', 'columns', 'indexes', 'foreign');
    }

    /*
    |--------------------------------------------------------------------------
    | Dấu vân tay - để trang tự báo khi cấu trúc / code thay đổi
    |--------------------------------------------------------------------------
    */

    public static function signature(): string
    {
        if (self::isMysql()) {
            $db = DB::connection()->getDatabaseName();
            $schema = DB::table('information_schema.COLUMNS')
                ->where('TABLE_SCHEMA', $db)
                ->orderBy('TABLE_NAME')
                ->orderBy('ORDINAL_POSITION')
                ->get(['TABLE_NAME', 'COLUMN_NAME', 'COLUMN_TYPE', 'IS_NULLABLE', 'COLUMN_KEY', 'COLUMN_DEFAULT', 'EXTRA'])
                ->map(fn ($r) => implode(',', (array) $r))
                ->implode('|');
            $schema .= '#' . DB::table('information_schema.STATISTICS')
                ->where('TABLE_SCHEMA', $db)
                ->orderBy('TABLE_NAME')
                ->orderBy('INDEX_NAME')
                ->orderBy('SEQ_IN_INDEX')
                ->get(['TABLE_NAME', 'INDEX_NAME', 'COLUMN_NAME'])
                ->map(fn ($r) => implode(',', (array) $r))
                ->implode('|');
        } else {
            $schema = json_encode(self::readGeneric());
        }

        $migrations = Schema::hasTable('migrations') ? DB::table('migrations')->count() : 0;

        // Quét lại code chỉ khi có file đổi (cache theo dấu vân tay file)
        return md5($schema . '#' . $migrations . '#' . (DatabaseCodeScanner::scan()['digest'] ?? ''));
    }

    /*
    |--------------------------------------------------------------------------
    | Dựng toàn bộ dữ liệu cho trang
    |--------------------------------------------------------------------------
    */

    public static function build(bool $refresh = false): array
    {
        $schema = self::readSchema();
        $scan = DatabaseCodeScanner::scan($refresh);

        $names = array_keys($schema['tables']);
        $lower = array_combine(array_map('strtolower', $names), $names);

        // Tra trường theo bảng: [bảng => [trường => thông tin]]
        $colMap = [];
        foreach ($schema['columns'] as $t => $cols) {
            foreach ($cols as $c) {
                $colMap[$t][$c['name']] = $c;
            }
        }

        $issues = [];
        $edges = self::buildEdges($schema, $scan, $lower, $colMap, $issues);
        $usage = self::buildUsage($scan['refs'], $lower, $issues);
        [$history, $tableMigs, $created] = self::buildHistory($scan['migrations'], $lower);
        $migrations = self::buildMigrations($scan['migrations'], $issues);

        $tables = [];
        foreach ($schema['tables'] as $name => $t) {
            $cols = [];
            foreach ($schema['columns'][$name] ?? [] as $c) {
                $h = $history[$name][$c['name']] ?? null;
                $c['added'] = $h['added'] ?? null;
                $c['changed'] = $h['changed'] ?? [];
                $cols[] = $c;
            }

            $idx = [];
            foreach ($schema['indexes'][$name] ?? [] as $iname => $i) {
                $idx[] = ['name' => $iname] + $i;
            }

            $tables[] = [
                'name'       => $name,
                'group'      => self::groupOf($name),
                'type'       => $t['type'],
                'engine'     => $t['engine'],
                'rows'       => $t['rows'],
                'size'       => $t['size'],
                'comment'    => $t['comment'],
                'created'    => $created[$name] ?? null,
                'columns'    => $cols,
                'indexes'    => $idx,
                'usage'      => $usage[$name] ?? ['code' => 0, 'seed' => 0, 'files' => []],
                'migrations' => $tableMigs[$name] ?? [],
            ];
        }

        self::checkSchema($tables, $edges, $colMap, $schema['indexes'], $issues);

        $groups = [];
        foreach (self::GROUPS as $key => $label) {
            $groups[] = ['key' => $key, 'label' => $label, 'count' => count(array_filter($tables, fn ($t) => $t['group'] === $key))];
        }

        $connection = DB::connection();

        return [
            'database'   => $connection->getDatabaseName(),
            'driver'     => $connection->getDriverName(),
            'version'    => self::isMysql() ? (string) DB::scalar('select version()') : null,
            'generated'  => now()->format('Y-m-d H:i:s'),
            'scanned'    => $scan['scanned_at'] ?? null,
            'files'      => $scan['files'],
            'signature'  => self::signature(),
            'groups'     => $groups,
            'tables'     => $tables,
            'edges'      => array_values($edges),
            'migrations' => $migrations,
            'issues'     => self::sortIssues($issues),
        ];
    }

    public static function groupOf(string $table): string
    {
        foreach (self::GROUP_TABLES as $group => $list) {
            if (in_array($table, $list, true)) {
                return $group;
            }
        }

        foreach (self::GROUP_PREFIXES as $prefix => $group) {
            if (str_starts_with($table, $prefix)) {
                return $group;
            }
        }

        return 'other';
    }

    /*
    |--------------------------------------------------------------------------
    | Quan hệ
    |--------------------------------------------------------------------------
    */

    private static function buildEdges(array $schema, array $scan, array $lower, array $colMap, array &$issues): array
    {
        $edges = [];

        // Khoá = id, hoặc trường đứng một mình trong khoá chính / chỉ mục duy nhất
        // (khoá chính ghép của bảng pivot role_permission không làm từng trường thành khoá)
        $single = [];
        foreach ($schema['indexes'] as $t => $list) {
            foreach ($list as $i) {
                if ($i['unique'] && count($i['columns']) === 1) {
                    $single[$t][$i['columns'][0]] = true;
                }
            }
        }

        $isKey = fn (string $t, string $c): bool => $c === 'id' || isset($single[$t][$c]);

        $add = function (string $from, string $fc, string $to, string $tc, string $card, string $src, ?array $ref = null) use (&$edges) {
            $key = "$from.$fc>$to.$tc";
            $edges[$key] ??= [
                'id'    => count($edges) + 1,
                'from'  => $from,
                'fc'    => $fc,
                'to'    => $to,
                'tc'    => $tc,
                'card'  => $card,
                'src'   => ['fk' => 0, 'join' => 0, 'model' => 0, 'name' => 0],
                'count' => 0,
                'refs'  => [],
            ];
            $edges[$key]['src'][$src]++;

            if ($ref) {
                $edges[$key]['count']++;
                if (count($edges[$key]['refs']) < self::MAX_REFS && ! in_array($ref, $edges[$key]['refs'], true)) {
                    $edges[$key]['refs'][] = $ref;
                }
            }
        };

        // 1. Khoá ngoại thật
        foreach ($schema['foreign'] as [$t, $c, $rt, $rc]) {
            $add($t, $c, $rt, $rc, $isKey($t, $c) ? '11' : 'n1', 'fk');
        }

        // 2. Điều kiện join trong code
        $missingCols = [];
        foreach ($scan['joins'] as $j) {
            $lt = $lower[strtolower($j['lt'])] ?? null;
            $rt = $lower[strtolower($j['rt'])] ?? null;
            $ref = [$j['file'], $j['line']];

            // Qualifier không phải bảng (alias truy vấn con, bảng CSDL khác...): bỏ qua.
            // Bảng có thật nhưng trường không có -> báo lỗi.
            if (! $lt || ! $rt) {
                continue;
            }

            foreach ([[$lt, $j['lc']], [$rt, $j['rc']]] as [$t, $c]) {
                if (! isset($colMap[$t][$c])) {
                    $missingCols["$t.$c"][] = $ref;
                }
            }
            if (! isset($colMap[$lt][$j['lc']]) || ! isset($colMap[$rt][$j['rc']])) {
                continue;
            }

            if ($lt === $rt && $j['lc'] === $j['rc']) {
                continue;
            }

            [$from, $fc, $to, $tc, $card] = self::orient($lt, $j['lc'], $rt, $j['rc'], $isKey);
            $add($from, $fc, $to, $tc, $card, 'join', $ref);
        }

        foreach ($missingCols as $col => $refs) {
            $issues[] = ['level' => 'danger', 'type' => 'missing_column', 'key' => $col, 'table' => explode('.', $col)[0], 'refs' => array_slice($refs, 0, 10)];
        }

        // 3. Quan hệ model
        foreach ($scan['models'] as [$t, $c, $rt, $rc, $file, $line]) {
            $t2 = $lower[strtolower($t)] ?? null;
            $rt2 = $lower[strtolower($rt)] ?? null;
            if (! $t2 || ! $rt2 || ! isset($colMap[$t2][$c]) || ! isset($colMap[$rt2][$rc])) {
                continue;
            }
            $add($t2, $c, $rt2, $rc, $isKey($t2, $c) ? '11' : 'n1', 'model', [$file, $line]);
        }

        // 4. Quy ước tên *_id cho trường chưa có quan hệ nào ở trên
        $covered = [];
        foreach ($edges as $e) {
            if ($e['card'] !== 'nn') {
                $covered[$e['from'] . '.' . $e['fc']] = true;
            }
        }

        foreach ($colMap as $t => $cols) {
            foreach ($cols as $c => $info) {
                if (! str_ends_with($c, '_id') || isset($covered["$t.$c"]) || $info['key'] === 'PRI' || in_array($c, self::NAME_IGNORE, true)) {
                    continue;
                }

                $target = self::guessTarget($t, $c, $colMap);
                if ($target) {
                    $add($t, $c, $target, 'id', $isKey($t, $c) ? '11' : 'n1', 'name');
                } else {
                    $issues[] = ['level' => 'info', 'type' => 'unresolved_id', 'key' => "$t.$c", 'table' => $t];
                }
            }
        }

        return $edges;
    }

    /** Xếp chiều: bên giữ khoá -> bên được tham chiếu */
    private static function orient(string $t1, string $c1, string $t2, string $c2, callable $isKey): array
    {
        $k1 = $isKey($t1, $c1);
        $k2 = $isKey($t2, $c2);

        if ($k2 && ! $k1) {
            return [$t1, $c1, $t2, $c2, 'n1'];
        }
        if ($k1 && ! $k2) {
            return [$t2, $c2, $t1, $c1, 'n1'];
        }

        // Cùng là khoá (1-1) hoặc cùng không phải khoá (join ngang): sắp theo tên cho cố định,
        // riêng 1-1 thì để trường "id" ở phía được tham chiếu
        $swap = $k1 && $k2
            ? ($c1 === 'id' && $c2 !== 'id') || (($c1 === 'id') === ($c2 === 'id') && "$t1.$c1" > "$t2.$c2")
            : "$t1.$c1" > "$t2.$c2";

        return $swap
            ? [$t2, $c2, $t1, $c1, $k1 ? '11' : 'nn']
            : [$t1, $c1, $t2, $c2, $k1 ? '11' : 'nn'];
    }

    /**
     * Đoán bảng đích của trường *_id:
     *   category_id ở chemical_imports  -> chemical_categories (thêm tiền tố của bảng chứa)
     *   to_department_id                -> deparments (bỏ dần từ đầu + bảng ngoại lệ)
     *   chem_names_id                   -> chem_names
     */
    private static function guessTarget(string $table, string $column, array $colMap): ?string
    {
        $base = substr($column, 0, -3);
        $words = explode('_', $base);

        $prefixes = [];
        $tw = explode('_', $table);
        for ($i = count($tw) - 1; $i >= 1; $i--) {
            $prefixes[] = implode('_', array_slice($tw, 0, $i)) . '_';
        }
        $prefixes[] = '';

        // Không tự trỏ về chính bảng chứa, trừ khi tên số nhiều khớp hẳn (source_import_id
        // ở standard_imports) hoặc là parent_id
        $exists = fn (string $t) => $t !== '' && isset($colMap[$t]['id']);

        for ($i = 0; $i < count($words); $i++) {
            $stem = implode('_', array_slice($words, $i));

            foreach (self::NAME_ALIASES[$stem] ?? [] as $alias) {
                $alias = $alias === '@self' ? $table : $alias;
                if ($exists($alias)) {
                    return $alias;
                }
            }

            // Số nhiều trước (kèm tiền tố của bảng chứa, dài trước ngắn sau), số ít sau:
            // bảng đặt tên số ít như standard_stability_assessment_list vẫn tìm được
            foreach ([self::plurals($stem), [$stem]] as $forms) {
                foreach ($prefixes as $prefix) {
                    foreach ($forms as $form) {
                        $candidate = $prefix . $form;
                        if ($exists($candidate) && ($candidate !== $table || $forms !== [$stem])) {
                            return $candidate;
                        }
                    }
                }
            }
        }

        return null;
    }

    private static function plurals(string $s): array
    {
        $out = [$s . 's', $s . 'es'];
        if (str_ends_with($s, 'y')) {
            $out[] = substr($s, 0, -1) . 'ies';
        }
        if (str_ends_with($s, 'f')) {
            $out[] = substr($s, 0, -1) . 'ves';
        }

        return $out;
    }

    /*
    |--------------------------------------------------------------------------
    | Nơi code dùng tới bảng
    |--------------------------------------------------------------------------
    */

    private static function buildUsage(array $refs, array $lower, array &$issues): array
    {
        $usage = [];
        $missing = [];

        foreach ($refs as [$table, $file, $line, $kind, $fileKind]) {
            $name = $lower[strtolower($table)] ?? null;

            if (! $name) {
                // SQL thô / kiểm tra hasTable dễ bắt nhầm chữ thường; tên viết hoa là bảng CSDL ngoài (CAL)
                if (! in_array($kind, ['sql', 'guard'], true) && preg_match('/^[a-z][a-z0-9_]*$/', $table)) {
                    $missing[$table][] = [$file, $line];
                }
                continue;
            }

            $usage[$name] ??= ['code' => 0, 'seed' => 0, 'files' => []];
            $usage[$name][$fileKind === 'seed' ? 'seed' : 'code']++;

            $f = &$usage[$name]['files'][$file];
            $f ??= ['file' => $file, 'seed' => $fileKind === 'seed', 'lines' => [], 'kinds' => []];
            if (count($f['lines']) < self::MAX_REFS && ! in_array($line, $f['lines'], true)) {
                $f['lines'][] = $line;
            }
            $f['kinds'][$kind] = true;
            unset($f);
        }

        foreach ($usage as &$u) {
            $files = array_values($u['files']);
            foreach ($files as &$f) {
                sort($f['lines']);
                $f['kinds'] = array_keys($f['kinds']);
            }
            unset($f);
            usort($files, fn ($a, $b) => [$a['seed'], $a['file']] <=> [$b['seed'], $b['file']]);
            $u['files'] = $files;
        }
        unset($u);

        foreach ($missing as $table => $list) {
            $issues[] = ['level' => 'danger', 'type' => 'missing_table', 'key' => $table, 'refs' => array_slice($list, 0, 10), 'count' => count($list)];
        }

        return $usage;
    }

    /*
    |--------------------------------------------------------------------------
    | Migration
    |--------------------------------------------------------------------------
    */

    /** Lịch sử cột theo migration: trường nào được thêm / sửa ở file nào */
    private static function buildHistory(array $migrations, array $lower): array
    {
        $history = [];
        $tableMigs = [];
        $created = [];

        $norm = fn (string $t) => $lower[strtolower($t)] ?? $t;

        foreach ($migrations as $m) {
            foreach ($m['tables'] as $t => $ops) {
                $tableMigs[$norm($t)][] = ['name' => $m['name'], 'ops' => $ops];
                if (in_array('create', $ops, true)) {
                    $created[$norm($t)] = $m['name'];
                }
            }

            foreach ($m['columns'] as [$t, $col, $op, $extra]) {
                $t = $norm($t);

                switch ($op) {
                    case 'add':
                        $history[$t][$col] = ['added' => $m['name'], 'changed' => []];
                        break;
                    case 'change':
                        $history[$t][$col] ??= ['added' => null, 'changed' => []];
                        $history[$t][$col]['changed'][] = $m['name'];
                        break;
                    case 'drop':
                        unset($history[$t][$col]);
                        break;
                    case 'rename':
                        $h = $history[$t][$col] ?? ['added' => null, 'changed' => []];
                        $h['changed'][] = $m['name'];
                        unset($history[$t][$col]);
                        $history[$t][$extra] = $h;
                        break;
                    case 'rename_table':
                        $to = $norm($extra);
                        $history[$to] = $history[$t] ?? [];
                        unset($history[$t]);
                        if (isset($created[$t])) {
                            $created[$to] = $created[$t];
                        }
                        $tableMigs[$to] = array_merge($tableMigs[$t] ?? [], $tableMigs[$to] ?? []);
                        break;
                    case 'drop_table':
                        unset($history[$t]);
                        break;
                }
            }
        }

        return [$history, $tableMigs, $created];
    }

    /** Danh sách file migration + trạng thái đã chạy / chờ chạy */
    private static function buildMigrations(array $parsed, array &$issues): array
    {
        $ran = Schema::hasTable('migrations')
            ? DB::table('migrations')->pluck('batch', 'migration')->all()
            : [];

        $out = [];
        foreach ($parsed as $m) {
            $date = preg_match('/^(\d{4})_(\d{2})_(\d{2})_(\d{2})(\d{2})(\d{2})_/', $m['name'], $d)
                ? "$d[1]-$d[2]-$d[3] $d[4]:$d[5]:$d[6]"
                : null;

            $out[] = [
                'name'   => $m['name'],
                'date'   => $date,
                'batch'  => $ran[$m['name']] ?? null,
                'ran'    => array_key_exists($m['name'], $ran),
                'tables' => $m['tables'],
            ];

            if (! array_key_exists($m['name'], $ran)) {
                $issues[] = ['level' => 'danger', 'type' => 'pending_migration', 'key' => $m['name']];
            }
            unset($ran[$m['name']]);
        }

        foreach ($ran as $name => $batch) {
            $issues[] = ['level' => 'warning', 'type' => 'orphan_migration', 'key' => $name, 'batch' => $batch];
        }

        return $out;
    }

    /*
    |--------------------------------------------------------------------------
    | Kiểm tra cấu trúc
    |--------------------------------------------------------------------------
    */

    private static function checkSchema(array $tables, array $edges, array $colMap, array $indexes, array &$issues): void
    {
        // Trường giữ khoá chưa có chỉ mục / lệch kiểu dữ liệu với bên được tham chiếu
        $seen = [];
        foreach ($edges as $e) {
            if ($e['card'] === 'nn') {
                continue;
            }

            $from = $colMap[$e['from']][$e['fc']] ?? null;
            $to = $colMap[$e['to']][$e['tc']] ?? null;
            if (! $from || ! $to) {
                continue;
            }

            $key = $e['from'] . '.' . $e['fc'];
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;

            $indexed = false;
            foreach ($indexes[$e['from']] ?? [] as $i) {
                if (($i['columns'][0] ?? null) === $e['fc']) {
                    $indexed = true;
                    break;
                }
            }
            if (! $indexed) {
                $issues[] = ['level' => 'warning', 'type' => 'missing_index', 'key' => $key, 'table' => $e['from'], 'target' => $e['to'] . '.' . $e['tc']];
            }

            if (self::normalizeType($from['type']) !== self::normalizeType($to['type'])) {
                $issues[] = [
                    'level'  => 'warning',
                    'type'   => 'type_mismatch',
                    'key'    => $key,
                    'table'  => $e['from'],
                    'target' => $e['to'] . '.' . $e['tc'],
                    'detail' => $from['type'] . ' ≠ ' . $to['type'],
                ];
            }
        }

        foreach ($tables as $t) {
            if ($t['group'] === 'system') {
                continue;
            }

            if (! $t['usage']['code']) {
                $issues[] = ['level' => 'info', 'type' => 'unused_table', 'key' => $t['name'], 'table' => $t['name'], 'seed' => $t['usage']['seed']];
            }

            if (! $t['created']) {
                $issues[] = ['level' => 'info', 'type' => 'no_migration_table', 'key' => $t['name'], 'table' => $t['name']];
                continue;
            }

            foreach ($t['columns'] as $c) {
                if (! $c['added']) {
                    $issues[] = ['level' => 'info', 'type' => 'no_migration_column', 'key' => $t['name'] . '.' . $c['name'], 'table' => $t['name']];
                }
            }
        }
    }

    /** int(11) unsigned -> int unsigned; bỏ độ rộng hiển thị không ảnh hưởng kiểu */
    private static function normalizeType(string $type): string
    {
        $type = strtolower(trim($type));

        return preg_replace('/^(tinyint|smallint|mediumint|int|integer|bigint)\(\d+\)/', '$1', $type);
    }

    private static function sortIssues(array $issues): array
    {
        $rank = ['danger' => 0, 'warning' => 1, 'info' => 2];
        usort($issues, fn ($a, $b) => [$rank[$a['level']], $a['type'], $a['key']] <=> [$rank[$b['level']], $b['type'], $b['key']]);

        return $issues;
    }
}
