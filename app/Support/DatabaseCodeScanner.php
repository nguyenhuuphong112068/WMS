<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * QUÉT CODE ĐỂ TÌM BẢNG / QUAN HỆ DỮ LIỆU
 *
 * CSDL của dự án không khai khoá ngoại thật: quan hệ giữa các bảng chỉ thể hiện trong
 * code. Lớp này đọc mã nguồn (không chạy) để rút ra:
 *
 *   - refs       : nơi code dùng tới một bảng (DB::table, join, from, Schema::, exists:..., SQL thô)
 *   - joins      : điều kiện nối 2 cột ở join(...), ->on(...), ->whereColumn(...)
 *   - models     : quan hệ Eloquent belongsTo / hasMany / hasOne / belongsToMany
 *   - migrations : bảng / cột mà mỗi file migration tạo, sửa, đổi tên, xoá
 *
 * Hằng số lớp (self::TABLE, DepartmentMaterial::TABLE...) được thay bằng giá trị chuỗi
 * trước khi dò, vì phần lớn Support / Controller khai tên bảng qua hằng số.
 *
 * Kết quả không phụ thuộc CSDL (chỉ phụ thuộc file) nên được cache theo "dấu vân tay"
 * của các file: file nào đổi nội dung / thời gian sửa thì lần mở trang sau quét lại.
 */
class DatabaseCodeScanner
{
    /** Tăng khi đổi cách quét để bỏ cache cũ */
    const VERSION = 7;

    const CACHE_KEY = 'database_code_scan';

    /** Thư mục chứa code chạy thật (tính là "đang được dùng") */
    const CODE_DIRS = ['app', 'routes', 'resources/views'];

    /** Dữ liệu mẫu: vẫn quét nhưng đánh dấu riêng */
    const SEED_DIR = 'database/seeders';

    const MIGRATION_DIR = 'database/migrations';

    /** Không tự quét chính mình (mã ví dụ trong regex không phải tham chiếu thật) */
    const SELF_FILES = [
        'app/Support/DatabaseCodeScanner.php',
        'app/Support/DatabaseSchema.php',
    ];

    /** Chuỗi trong nháy đơn / nháy kép, không chứa biến nội suy */
    const Q = '[\'"]([^\'"\n$]*)[\'"]';

    /** Phương thức Blueprint thêm cột: tên => cột sinh ra khi gọi không tham số */
    const COLUMN_TYPES = [
        'bigIncrements', 'bigInteger', 'binary', 'boolean', 'char', 'date', 'dateTime', 'dateTimeTz',
        'decimal', 'double', 'enum', 'float', 'foreignId', 'geometry', 'increments', 'integer',
        'ipAddress', 'json', 'jsonb', 'longText', 'macAddress', 'mediumIncrements', 'mediumInteger',
        'mediumText', 'set', 'smallIncrements', 'smallInteger', 'string', 'text', 'time', 'timeTz',
        'timestamp', 'timestampTz', 'tinyIncrements', 'tinyInteger', 'tinyText', 'unsignedBigInteger',
        'unsignedDecimal', 'unsignedInteger', 'unsignedMediumInteger', 'unsignedSmallInteger',
        'unsignedTinyInteger', 'uuid', 'ulid', 'year', 'softDeletes', 'softDeletesTz',
    ];

    const NO_ARG_COLUMNS = [
        'id'                 => ['id'],
        'timestamps'         => ['created_at', 'updated_at'],
        'nullableTimestamps' => ['created_at', 'updated_at'],
        'timestampsTz'       => ['created_at', 'updated_at'],
        'softDeletes'        => ['deleted_at'],
        'softDeletesTz'      => ['deleted_at'],
        'rememberToken'      => ['remember_token'],
    ];

    /*
    |--------------------------------------------------------------------------
    | Danh sách file + dấu vân tay
    |--------------------------------------------------------------------------
    */

    /** @return array<string, array{path:string, kind:string}> khoá = đường dẫn tương đối */
    public static function files(): array
    {
        $files = [];

        foreach (self::CODE_DIRS as $dir) {
            foreach (self::listDir($dir) as $rel => $abs) {
                $files[$rel] = ['path' => $abs, 'kind' => 'code'];
            }
        }

        foreach (self::listDir(self::SEED_DIR) as $rel => $abs) {
            $files[$rel] = ['path' => $abs, 'kind' => 'seed'];
        }

        foreach (self::listDir(self::MIGRATION_DIR) as $rel => $abs) {
            $files[$rel] = ['path' => $abs, 'kind' => 'migration'];
        }

        foreach (self::SELF_FILES as $rel) {
            unset($files[$rel]);
        }

        ksort($files);

        return $files;
    }

    private static function listDir(string $dir): array
    {
        $root = base_path($dir);
        $out = [];

        if (! is_dir($root)) {
            return $out;
        }

        $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS));

        foreach ($it as $file) {
            if (! $file->isFile() || strtolower($file->getExtension()) !== 'php') {
                continue;
            }

            $rel = str_replace('\\', '/', substr($file->getPathname(), strlen(base_path()) + 1));
            $out[$rel] = $file->getPathname();
        }

        return $out;
    }

    /** Đổi khi có file thêm / xoá / sửa */
    public static function fingerprint(?array $files = null): string
    {
        $files ??= self::files();
        $parts = [self::VERSION];

        foreach ($files as $rel => $f) {
            $parts[] = $rel . ':' . @filemtime($f['path']) . ':' . @filesize($f['path']);
        }

        return md5(implode('|', $parts));
    }

    /*
    |--------------------------------------------------------------------------
    | Quét (có cache)
    |--------------------------------------------------------------------------
    */

    public static function scan(bool $force = false): array
    {
        $files = self::files();
        $fingerprint = self::fingerprint($files);
        $store = Cache::store('file');

        if (! $force) {
            $cached = $store->get(self::CACHE_KEY);
            if (is_array($cached) && ($cached['fingerprint'] ?? null) === $fingerprint) {
                return $cached;
            }
        }

        $result = self::run($files);
        $result['fingerprint'] = $fingerprint;
        $result['digest'] = self::digest($result);
        $result['scanned_at'] = now()->format('Y-m-d H:i:s');

        $store->forever(self::CACHE_KEY, $result);

        return $result;
    }

    /**
     * Băm phần kết quả có ý nghĩa (bỏ số dòng): sửa giao diện, thêm comment... không đổi
     * digest; thêm join / đổi bảng dùng trong file / thêm migration mới đổi.
     */
    private static function digest(array $result): string
    {
        $joins = array_map(fn ($j) => "{$j['lt']}.{$j['lc']}={$j['rt']}.{$j['rc']}", $result['joins']);
        $refs = array_map(fn ($r) => $r[0] . '@' . $r[1], $result['refs']);
        $models = array_map(fn ($m) => implode(',', [$m[0], $m[1], $m[2], $m[3]]), $result['models']);

        $parts = [];
        foreach ([$joins, $refs, $models] as $list) {
            $list = array_unique($list);
            sort($list);
            $parts[] = implode('|', $list);
        }
        $parts[] = json_encode($result['migrations']);

        return md5(implode('#', $parts));
    }

    private static function run(array $files): array
    {
        // Bỏ comment trước: docblock hay chép mã ví dụ (const TABLE = 'units'...)
        $sources = [];
        foreach ($files as $rel => $f) {
            $raw = (string) @file_get_contents($f['path']);
            $sources[$rel] = str_ends_with($rel, '.blade.php') ? self::cleanBlade($raw) : self::cleanPhp($raw);
        }

        $consts = self::collectConstants($sources);

        $refs = [];
        $joins = [];
        $migrations = [];

        foreach ($files as $rel => $f) {
            $code = self::resolveConstants($sources[$rel], $consts, self::fileConstants($sources[$rel]));

            if ($f['kind'] === 'migration') {
                $migrations[] = self::parseMigration($rel, $code);
                continue;
            }

            $lines = self::lineIndex($code);

            foreach (self::findRefs($code, $lines) as $r) {
                $refs[] = [$r[0], $rel, $r[1], $r[2], $f['kind']];
            }

            if ($f['kind'] === 'code') {
                foreach (self::findJoins($code, $lines) as $j) {
                    $j['file'] = $rel;
                    $joins[] = $j;
                }
            }
        }

        $models = self::parseModels($files, $sources);

        usort($migrations, fn ($a, $b) => strcmp($a['name'], $b['name']));

        return [
            'files'      => count($files),
            'refs'       => $refs,
            'joins'      => $joins,
            'models'     => $models,
            'migrations' => $migrations,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Tiền xử lý mã nguồn
    |--------------------------------------------------------------------------
    */

    /** Bỏ comment PHP, giữ nguyên số dòng */
    private static function cleanPhp(string $code): string
    {
        try {
            $tokens = token_get_all($code);
        } catch (\Throwable) {
            return $code;
        }

        $out = '';
        foreach ($tokens as $t) {
            if (is_array($t)) {
                if ($t[0] === T_COMMENT || $t[0] === T_DOC_COMMENT) {
                    $out .= str_repeat("\n", substr_count($t[1], "\n"));
                    continue;
                }
                $out .= $t[1];
            } else {
                $out .= $t;
            }
        }

        return $out;
    }

    /** Bỏ comment Blade {{-- --}}, giữ nguyên số dòng */
    private static function cleanBlade(string $code): string
    {
        return preg_replace_callback('/\{\{--.*?--\}\}/s', fn ($m) => str_repeat("\n", substr_count($m[0], "\n")), $code);
    }

    private static function className(string $code): ?string
    {
        return preg_match('/^\s*(?:final\s+|abstract\s+|readonly\s+)*(?:class|trait|enum)\s+(\w+)/m', $code, $m) ? $m[1] : null;
    }

    /** Hằng số chuỗi khai trong một file: [HẰNG => giá trị] */
    private static function fileConstants(string $code): array
    {
        $out = [];
        if (preg_match_all('/\bconst\s+([A-Z][A-Z0-9_]*)\s*=\s*' . self::Q . '\s*;/', $code, $mm, PREG_SET_ORDER)) {
            foreach ($mm as $m) {
                $out[$m[1]] = $m[2];
            }
        }

        return $out;
    }

    /** Hằng số chuỗi của mọi lớp: [TênLớp => [HẰNG => giá trị]] */
    private static function collectConstants(array $sources): array
    {
        $consts = [];

        foreach ($sources as $code) {
            $class = self::className($code);
            if (! $class) {
                continue;
            }

            foreach (self::fileConstants($code) as $name => $value) {
                // Hai lớp trùng tên nhưng khác giá trị: không đoán
                if (array_key_exists($name, $consts[$class] ?? []) && $consts[$class][$name] !== $value) {
                    $consts[$class][$name] = null;
                    continue;
                }
                $consts[$class][$name] = $value;
            }
        }

        return $consts;
    }

    /**
     * self::TABLE / static::TABLE / \App\Support\DepartmentMaterial::TABLE -> 'giá trị'
     * rồi nối các chuỗi liền nhau trên cùng dòng: 'a' . '.id' -> 'a.id'
     *
     * self:: lấy hằng của chính file (kể cả lớp ẩn danh của migration).
     */
    private static function resolveConstants(string $code, array $consts, array $own): string
    {
        $code = preg_replace_callback(
            '/(?<![\w$\\\\])((?:\\\\?[A-Z]\w*\\\\)*)(self|static|[A-Z]\w*)::([A-Z][A-Z0-9_]*)\b(?!\s*\()/',
            function ($m) use ($consts, $own) {
                $value = in_array($m[2], ['self', 'static'], true)
                    ? ($own[$m[3]] ?? null)
                    : ($consts[$m[2]][$m[3]] ?? null);

                return $value === null ? $m[0] : "'" . $value . "'";
            },
            $code
        );

        return self::collapse($code);
    }

    /** 'a' . '.id' -> 'a.id' (chỉ nối trên cùng dòng để giữ nguyên số dòng) */
    private static function collapse(string $code): string
    {
        $pattern = '/' . self::Q . '[ \t]*\.[ \t]*' . self::Q . '/';
        for ($i = 0; $i < 6; $i++) {
            $next = preg_replace($pattern, "'$1$2'", $code);
            if ($next === $code || $next === null) {
                break;
            }
            $code = $next;
        }

        return $code;
    }

    /** Vị trí đầu mỗi dòng để đổi offset -> số dòng */
    private static function lineIndex(string $code): array
    {
        $starts = [0];
        $pos = -1;
        while (($pos = strpos($code, "\n", $pos + 1)) !== false) {
            $starts[] = $pos + 1;
        }

        return $starts;
    }

    private static function lineAt(array $starts, int $offset): int
    {
        $lo = 0;
        $hi = count($starts) - 1;
        while ($lo < $hi) {
            $mid = ($lo + $hi + 1) >> 1;
            if ($starts[$mid] <= $offset) {
                $lo = $mid;
            } else {
                $hi = $mid - 1;
            }
        }

        return $lo + 1;
    }

    /** 'chemical_imports as ci' -> ['chemical_imports', 'ci'] */
    private static function splitTable(string $s): ?array
    {
        if (! preg_match('/^\s*(?:\w+\.)?(\w+)(?:\s+as\s+(\w+))?\s*$/i', $s, $m)) {
            return null;
        }

        return [$m[1], $m[2] ?? null];
    }

    /*
    |--------------------------------------------------------------------------
    | Tham chiếu bảng
    |--------------------------------------------------------------------------
    */

    /** @return array<int, array{0:string,1:int,2:string,3?:string}> [bảng, dòng, loại, alias] */
    private static function findRefs(string $code, array $lines): array
    {
        $out = [];
        $q = self::Q;

        $patterns = [
            'table'    => '/DB::table\(\s*' . $q . '/',
            'join'     => '/->(?:join|leftJoin|rightJoin|innerJoin|crossJoin|joinWhere|leftJoinWhere)\(\s*' . $q . '/',
            'from'     => '/->from\(\s*' . $q . '/',
            'schema'   => '/Schema::(?:create|table|getColumnListing|getColumns|getIndexes|dropIfExists|drop)\(\s*' . $q . '/',
            // Kiểm tra trước khi dùng: bảng chưa có cũng không phải lỗi
            'guard'    => '/Schema::(?:hasTable|hasColumn|hasColumns)\(\s*' . $q . '/',
            'validate' => '/Rule::(?:exists|unique)\(\s*' . $q . '/',
            'model'    => '/(?:protected|public)\s+\$table\s*=\s*' . $q . '/',
        ];

        foreach ($patterns as $kind => $re) {
            if (! preg_match_all($re, $code, $mm, PREG_OFFSET_CAPTURE)) {
                continue;
            }
            foreach ($mm[1] as $hit) {
                $t = self::splitTable($hit[0]);
                if ($t) {
                    $out[] = [$t[0], self::lineAt($lines, $hit[1]), $kind, $t[1]];
                }
            }
        }

        // Luật validate dạng chuỗi: 'exists:units,id' / 'unique:companies,code'
        if (preg_match_all('/[\'"|](?:exists|unique):(\w+)/', $code, $mm, PREG_OFFSET_CAPTURE)) {
            foreach ($mm[1] as $hit) {
                $out[] = [$hit[0], self::lineAt($lines, $hit[1]), 'validate', null];
            }
        }

        // SQL thô trong DB::select / DB::statement / selectRaw...
        if (preg_match_all('/\b(?:FROM|JOIN|UPDATE|INTO)\s+`?([A-Za-z_]\w*)`?/i', $code, $mm, PREG_OFFSET_CAPTURE)) {
            foreach ($mm[1] as $hit) {
                $out[] = [$hit[0], self::lineAt($lines, $hit[1]), 'sql', null];
            }
        }

        return $out;
    }

    /*
    |--------------------------------------------------------------------------
    | Điều kiện nối cột
    |--------------------------------------------------------------------------
    */

    private static function findJoins(string $code, array $lines): array
    {
        $q = self::Q;
        $sep = '\s*,\s*';

        // Bảng khai alias trong file: [alias, bảng, offset]
        $aliases = [];
        $aliasRe = '/(?:DB::table|->(?:join|leftJoin|rightJoin|innerJoin|crossJoin|from))\(\s*' . $q . '/';
        if (preg_match_all($aliasRe, $code, $mm, PREG_OFFSET_CAPTURE)) {
            foreach ($mm[1] as $hit) {
                $t = self::splitTable($hit[0]);
                if ($t && $t[1]) {
                    $aliases[] = [$t[1], $t[0], $hit[1]];
                }
            }
        }

        // Alias của bảng tên động ($tables['imports'] . ' as i') hoặc truy vấn con:
        // vẫn ghi lại (bảng = null) để không gán nhầm sang alias trùng tên ở hàm khác
        $dynamicRe = [
            '/(?:DB::table|->(?:join|leftJoin|rightJoin|innerJoin|crossJoin|from))\(\s*[^\'"\s)][^,);]*?[\'"]\s*as\s+(\w+)\s*[\'"]/i',
            '/->(?:joinSub|leftJoinSub|rightJoinSub|fromSub)\(\s*[^,;]+,\s*' . $q . '/',
        ];
        foreach ($dynamicRe as $re) {
            if (preg_match_all($re, $code, $mm, PREG_OFFSET_CAPTURE)) {
                foreach ($mm[1] as $hit) {
                    $aliases[] = [$hit[0], null, $hit[1]];
                }
            }
        }
        usort($aliases, fn ($a, $b) => $a[2] <=> $b[2]);

        $conditions = [];

        // join('bảng', 'a.x', '=', 'b.y')  |  join('bảng', 'a.x', 'b.y')
        $re = '/->(?:join|leftJoin|rightJoin|innerJoin)\(\s*' . $q . $sep . $q . $sep . $q . '(?:' . $sep . $q . ')?/';
        if (preg_match_all($re, $code, $mm, PREG_SET_ORDER | PREG_OFFSET_CAPTURE)) {
            foreach ($mm as $m) {
                $conditions[] = isset($m[4])
                    ? [$m[2][0], $m[3][0], $m[4][0], $m[0][1], 'join']
                    : [$m[2][0], '=', $m[3][0], $m[0][1], 'join'];
            }
        }

        // ->on('a.x', '=', 'b.y')  |  ->whereColumn('a.x', 'b.y')
        $re = '/->(on|orOn|whereColumn|orWhereColumn)\(\s*' . $q . $sep . $q . '(?:' . $sep . $q . ')?\s*[,)]/';
        if (preg_match_all($re, $code, $mm, PREG_SET_ORDER | PREG_OFFSET_CAPTURE)) {
            foreach ($mm as $m) {
                $kind = str_contains(strtolower($m[1][0]), 'column') ? 'whereColumn' : 'on';
                $conditions[] = isset($m[4]) && $m[4][1] >= 0
                    ? [$m[2][0], $m[3][0], $m[4][0], $m[0][1], $kind]
                    : [$m[2][0], '=', $m[3][0], $m[0][1], $kind];
            }
        }

        $out = [];
        foreach ($conditions as [$left, $op, $right, $offset, $kind]) {
            if (trim($op) !== '=') {
                continue;
            }

            $l = self::splitColumn($left);
            $r = self::splitColumn($right);
            if (! $l || ! $r) {
                continue;
            }

            $lt = self::resolveAlias($l[0], $offset, $aliases);
            $rt = self::resolveAlias($r[0], $offset, $aliases);
            if ($lt === null || $rt === null) {
                continue;
            }

            $out[] = [
                'lt'   => $lt,
                'lc'   => $l[1],
                'rt'   => $rt,
                'rc'   => $r[1],
                'line' => self::lineAt($lines, $offset),
                'kind' => $kind,
            ];
        }

        return $out;
    }

    /** 'ci.category_id' -> ['ci', 'category_id'] */
    private static function splitColumn(string $s): ?array
    {
        return preg_match('/^\s*(\w+)\.(\w+)\s*$/', $s, $m) ? [$m[1], $m[2]] : null;
    }

    /**
     * Alias khai gần nhất phía trước; không có thì lấy khai sớm nhất phía sau; không
     * khai alias thì chính là tên bảng. Trả null khi alias trỏ tới bảng tên động.
     */
    private static function resolveAlias(string $name, int $offset, array $aliases): ?string
    {
        $found = false;
        $before = null;
        $after = null;
        $hasAfter = false;

        foreach ($aliases as [$alias, $table, $pos]) {
            if ($alias !== $name) {
                continue;
            }
            if ($pos <= $offset) {
                $found = true;
                $before = $table;
            } elseif (! $hasAfter) {
                $hasAfter = true;
                $after = $table;
            }
        }

        if ($found) {
            return $before;
        }

        return $hasAfter ? $after : $name;
    }

    /*
    |--------------------------------------------------------------------------
    | Quan hệ Eloquent
    |--------------------------------------------------------------------------
    */

    private static function parseModels(array $files, array $sources): array
    {
        $tables = [];
        $modelFiles = [];

        foreach (array_keys($files) as $rel) {
            if (! str_starts_with($rel, 'app/Models/')) {
                continue;
            }
            $class = self::className($sources[$rel]);
            if (! $class) {
                continue;
            }
            $modelFiles[$rel] = $class;
            $tables[$class] = preg_match('/\$table\s*=\s*' . self::Q . '/', $sources[$rel], $m)
                ? $m[1]
                : Str::snake(Str::pluralStudly($class));
        }

        $out = [];
        $q = self::Q;
        $re = '/->(belongsTo|hasMany|hasOne|belongsToMany)\(\s*\\\\?(?:\w+\\\\)*(\w+)::class(?:\s*,\s*' . $q . ')?(?:\s*,\s*' . $q . ')?(?:\s*,\s*' . $q . ')?/';

        foreach ($modelFiles as $rel => $class) {
            $code = $sources[$rel];
            $lines = self::lineIndex($code);
            $self = $tables[$class];

            if (! preg_match_all($re, $code, $mm, PREG_SET_ORDER | PREG_OFFSET_CAPTURE)) {
                continue;
            }

            foreach ($mm as $m) {
                $type = $m[1][0];
                $related = $tables[$m[2][0]] ?? Str::snake(Str::pluralStudly($m[2][0]));
                $a1 = isset($m[3]) && $m[3][1] >= 0 ? $m[3][0] : null;
                $a2 = isset($m[4]) && $m[4][1] >= 0 ? $m[4][0] : null;
                $a3 = isset($m[5]) && $m[5][1] >= 0 ? $m[5][0] : null;
                $line = self::lineAt($lines, $m[0][1]);

                if ($type === 'belongsTo') {
                    $out[] = [$self, $a1 ?? Str::snake(Str::singular($related)) . '_id', $related, $a2 ?? 'id', $rel, $line, $type];
                } elseif ($type === 'belongsToMany') {
                    $pivot = $a1 ?? implode('_', collect([Str::singular($self), Str::singular($related)])->sort()->all());
                    $out[] = [$pivot, $a2 ?? Str::singular($self) . '_id', $self, 'id', $rel, $line, $type];
                    $out[] = [$pivot, $a3 ?? Str::singular($related) . '_id', $related, 'id', $rel, $line, $type];
                } else {
                    $out[] = [$related, $a1 ?? Str::snake($class) . '_id', $self, $a2 ?? 'id', $rel, $line, $type];
                }
            }
        }

        return $out;
    }

    /*
    |--------------------------------------------------------------------------
    | Migration
    |--------------------------------------------------------------------------
    */

    private static function parseMigration(string $rel, string $code): array
    {
        $name = basename($rel, '.php');

        // Chỉ xét up(): down() là chiều quay lui
        $up = $code;
        if (preg_match('/function\s+down\s*\(/', $code, $m, PREG_OFFSET_CAPTURE)) {
            $up = substr($code, 0, $m[0][1]);
        }
        $up = self::expandMigration($up, $code);

        $q = self::Q;

        // Gom thao tác kèm vị trí rồi xếp theo thứ tự xuất hiện: migration "dựng lại bảng"
        // drop rồi create ngay sau, đảo thứ tự là mất hết lịch sử cột
        $events = [];

        if (preg_match_all('/Schema::(create|table)\(\s*' . $q . '\s*,\s*function\s*\([^)]*\)\s*(?:use\s*\([^)]*\)\s*)?(?::\s*\w+\s*)?\{/', $up, $mm, PREG_SET_ORDER | PREG_OFFSET_CAPTURE)) {
            foreach ($mm as $m) {
                $isCreate = $m[1][0] === 'create';
                $body = self::blockBody($up, $m[0][1] + strlen($m[0][0]));
                $cols = [];
                foreach (self::parseBlueprint($body) as [$col, $op, $extra]) {
                    $cols[] = [$col, $isCreate ? 'add' : $op, $extra];
                }
                $events[] = [$m[0][1], $isCreate ? 'create' : 'alter', $m[2][0], $cols];
            }
        }

        if (preg_match_all('/Schema::rename\(\s*' . $q . '\s*,\s*' . $q . '/', $up, $mm, PREG_SET_ORDER | PREG_OFFSET_CAPTURE)) {
            foreach ($mm as $m) {
                $events[] = [$m[0][1], 'rename', $m[1][0], $m[2][0]];
            }
        }

        if (preg_match_all('/Schema::(?:drop|dropIfExists)\(\s*' . $q . '/', $up, $mm, PREG_SET_ORDER | PREG_OFFSET_CAPTURE)) {
            foreach ($mm as $m) {
                $events[] = [$m[0][1], 'drop', $m[1][0], null];
            }
        }

        // Migration chỉ sửa dữ liệu (cập nhật giá trị, nạp quyền...) - không đổi cấu trúc
        if (preg_match_all('/DB::table\(\s*' . $q . '/', $up, $mm, PREG_SET_ORDER | PREG_OFFSET_CAPTURE)) {
            foreach ($mm as $m) {
                $t = self::splitTable($m[1][0]);
                if ($t) {
                    $events[] = [$m[0][1], 'data', $t[0], null];
                }
            }
        }

        if (preg_match_all('/ALTER\s+TABLE\s+`?(\w+)`?/i', $up, $mm, PREG_SET_ORDER | PREG_OFFSET_CAPTURE)) {
            foreach ($mm as $m) {
                $events[] = [$m[0][1], 'alter', $m[1][0], []];
            }
        }

        usort($events, fn ($a, $b) => $a[0] <=> $b[0]);

        $tables = [];
        $columns = [];
        $note = function (string $table, string $op) use (&$tables) {
            $tables[$table] ??= [];
            if (! in_array($op, $tables[$table], true)) {
                $tables[$table][] = $op;
            }
        };

        foreach ($events as [, $type, $table, $data]) {
            $note($table, $type);

            if ($type === 'rename') {
                $note($data, 'rename');
                $columns[] = [$table, '*', 'rename_table', $data];
            } elseif ($type === 'drop') {
                $columns[] = [$table, '*', 'drop_table', null];
            } elseif ($type !== 'data') {
                if ($type === 'create') {
                    $columns[] = [$table, '*', 'drop_table', null];
                }
                foreach ($data as [$col, $op, $extra]) {
                    $columns[] = [$table, $col, $op, $extra];
                }
            }
        }

        return ['name' => $name, 'tables' => $tables, 'columns' => $columns];
    }

    /**
     * Migration hay tạo / sửa nhiều bảng cùng khuôn bằng vòng lặp hoặc hàm phụ:
     *
     *   foreach (self::TYPES as $type) { Schema::create($type.'_estimate_signs', ...) }
     *   foreach (self::TABLES as $from => $to) { $this->rename($from, $to); }
     *
     * Trải vòng lặp trên mảng hằng / mảng viết sẵn thành từng bản sao với giá trị cụ thể,
     * và chép thân hàm phụ có Schema:: vào chỗ gọi, để các mẫu dò phía sau thấy tên bảng.
     */
    private static function expandMigration(string $up, string $full): string
    {
        // Mảng hằng (self::TABLES) và thuộc tính mảng ($this->tables)
        $arrays = [];
        if (preg_match_all('/\bconst\s+(\w+)\s*=\s*\[/', $full, $mm, PREG_SET_ORDER | PREG_OFFSET_CAPTURE)) {
            foreach ($mm as $m) {
                $arrays['self::' . $m[1][0]] = self::blockBody($full, $m[0][1] + strlen($m[0][0]), '[', ']');
            }
        }
        if (preg_match_all('/(?:private|protected|public)\s+(?:static\s+)?(?:\??array\s+)?\$(\w+)\s*=\s*\[/', $full, $mm, PREG_SET_ORDER | PREG_OFFSET_CAPTURE)) {
            foreach ($mm as $m) {
                $arrays['$this->' . $m[1][0]] = self::blockBody($full, $m[0][1] + strlen($m[0][0]), '[', ']');
            }
        }

        $methods = [];
        if (preg_match_all('/function\s+(\w+)\s*\(([^)]*)\)\s*(?::\s*\??\w+\s*)?\{/', $full, $mm, PREG_SET_ORDER | PREG_OFFSET_CAPTURE)) {
            foreach ($mm as $m) {
                if (in_array($m[1][0], ['up', 'down'], true)) {
                    continue;
                }
                $body = self::blockBody($full, $m[0][1] + strlen($m[0][0]));
                if (str_contains($body, 'Schema::')) {
                    preg_match_all('/\$(\w+)/', $m[2][0], $pp);
                    $methods[$m[1][0]] = [$pp[1], $body];
                }
            }
        }

        $loopRe = '/foreach\s*\(\s*(?:(?<keys>array_keys\(\s*)?(?<ref>self::\w+|\$this->\w+)\s*\)?|\[(?<list>[^\[\]]*)\])'
            . '\s+as\s+\$(?<a>\w+)(?:\s*=>\s*\$(?<b>\w+))?\s*\)\s*\{/';
        $from = 0;

        for ($round = 0; $round < 60; $round++) {
            if (preg_match($loopRe, $up, $m, PREG_OFFSET_CAPTURE | PREG_UNMATCHED_AS_NULL, $from)) {
                $bodyStart = $m[0][1] + strlen($m[0][0]);
                $body = self::blockBody($up, $bodyStart);
                $end = $bodyStart + strlen($body) + 1;

                $keysOnly = $m['keys'][0] !== null;
                $source = $m['ref'][0] !== null ? ($arrays[$m['ref'][0]] ?? null) : $m['list'][0];
                $items = $source === null ? [] : self::arrayItems($source);

                if (! $items || (! str_contains($body, 'Schema::') && ! str_contains($body, '$this->'))) {
                    $from = $end;
                    continue;
                }

                $copies = '';
                foreach ($items as $i => [$key, $value]) {
                    $key ??= (string) $i;
                    $vars = $m['b'][0] !== null
                        ? [$m['a'][0] => $key, $m['b'][0] => $value]
                        : [$m['a'][0] => $keysOnly ? $key : $value];
                    $copies .= "\n" . self::substitute($body, $vars);
                }

                $up = substr($up, 0, $m[0][1]) . $copies . substr($up, $end);
                $from = $m[0][1];
                continue;
            }

            // Hết vòng lặp: chép thân hàm phụ vào chỗ gọi $this->hàm('a', 'b')
            $inlined = false;
            if ($methods && preg_match_all('/\$this->(\w+)\(([^;]*?)\)\s*;/', $up, $mm, PREG_SET_ORDER | PREG_OFFSET_CAPTURE)) {
                foreach (array_reverse($mm) as $m) {
                    if (! isset($methods[$m[1][0]])) {
                        continue;
                    }
                    [$params, $body] = $methods[$m[1][0]];
                    // Tham số là chuỗi viết sẵn thì thay giá trị; còn lại (mảng, biến) chép nguyên thân hàm
                    preg_match_all('/' . self::Q . '/', $m[2][0], $aa);
                    if ($params && count($aa[1]) === count($params)) {
                        $body = self::substitute($body, array_combine($params, $aa[1]));
                    }
                    $body = preg_replace('/\$this->' . preg_quote($m[1][0], '/') . '\(/', '$skip(', $body);
                    $up = substr($up, 0, $m[0][1]) . $body . substr($up, $m[0][1] + strlen($m[0][0]));
                    $inlined = true;
                }
            }
            if (! $inlined) {
                break;
            }
            $from = 0;
        }

        return $up;
    }

    /** Phần tử mảng PHP viết sẵn: [[khoá|null, giá trị|null]] - chỉ lấy mảng một cấp */
    private static function arrayItems(string $text): array
    {
        $q = self::Q;

        if (preg_match_all('/' . $q . '\s*=>\s*' . $q . '/', $text, $mm, PREG_SET_ORDER)) {
            return array_map(fn ($m) => [$m[1], $m[2]], $mm);
        }
        if (preg_match_all('/' . $q . '\s*=>/', $text, $mm, PREG_SET_ORDER)) {
            return array_map(fn ($m) => [$m[1], null], $mm);
        }
        if (str_contains($text, '[')) {
            return [];
        }
        preg_match_all('/' . $q . '/', $text, $mm, PREG_SET_ORDER);

        return array_map(fn ($m) => [null, $m[1]], $mm);
    }

    /** Thay biến bằng giá trị chuỗi rồi lan tiếp qua phép gán đơn giản $x = 'a'; */
    private static function substitute(string $body, array $vars): string
    {
        for ($pass = 0; $pass < 3; $pass++) {
            foreach ($vars as $name => $value) {
                if ($value === null || $name === '') {
                    continue;
                }
                $body = str_replace('{$' . $name . '}', $value, $body);
                $body = preg_replace('/\$' . preg_quote($name, '/') . '\b(?!\s*(?:->|\[|=[^=>]))/', "'" . $value . "'", $body);
            }
            $body = self::collapse($body);

            $assigned = [];
            if (preg_match_all('/\$(\w+)\s*=\s*' . self::Q . '\s*;/', $body, $mm, PREG_SET_ORDER)) {
                foreach ($mm as $m) {
                    if (! isset($vars[$m[1]])) {
                        $assigned[$m[1]] = $m[2];
                    }
                }
            }
            if (! $assigned) {
                break;
            }
            $vars = $assigned;
        }

        return $body;
    }

    /** Nội dung giữa cặp ngoặc, bắt đầu ngay sau ngoặc mở */
    private static function blockBody(string $code, int $start, string $open = '{', string $close = '}'): string
    {
        $depth = 1;
        $len = strlen($code);

        for ($i = $start; $i < $len; $i++) {
            $c = $code[$i];
            if ($c === $open) {
                $depth++;
            } elseif ($c === $close) {
                $depth--;
                if ($depth === 0) {
                    return substr($code, $start, $i - $start);
                }
            }
        }

        return substr($code, $start);
    }

    /** @return array<int, array{0:string,1:string,2:?string}> [cột, add|change|drop|rename, tên mới] */
    private static function parseBlueprint(string $body): array
    {
        $out = [];
        $q = self::Q;

        $known = array_merge(
            self::COLUMN_TYPES,
            array_keys(self::NO_ARG_COLUMNS),
            ['morphs', 'nullableMorphs', 'uuidMorphs', 'ulidMorphs', 'dropColumn', 'dropColumns', 'renameColumn']
        );

        // Mỗi câu lệnh $table->...; xét riêng để biết có ->change() hay không. Lấy lời gọi
        // Blueprint đầu tiên trong câu: phía trước có thể là if (! Schema::hasColumn($t->getTable(), ...))
        foreach (preg_split('/;\s*/', $body) as $stmt) {
            $m = null;
            if (preg_match_all('/\$\w+->(\w+)\(\s*/', $stmt, $calls, PREG_SET_ORDER | PREG_OFFSET_CAPTURE)) {
                foreach ($calls as $call) {
                    if (in_array($call[1][0], $known, true)) {
                        $m = [null, $call[1][0], substr($stmt, $call[0][1] + strlen($call[0][0]))];
                        break;
                    }
                }
            }
            if (! $m) {
                continue;
            }

            $method = $m[1];
            $args = $m[2];
            $isChange = (bool) preg_match('/->change\(\s*\)/', $stmt);

            if (isset(self::NO_ARG_COLUMNS[$method]) && preg_match('/^\s*\)/', $args)) {
                foreach (self::NO_ARG_COLUMNS[$method] as $col) {
                    $out[] = [$col, 'add', null];
                }
                continue;
            }

            if (in_array($method, self::COLUMN_TYPES, true) && preg_match('/^' . $q . '/', $args, $a)) {
                $out[] = [$a[1], $isChange ? 'change' : 'add', null];
                continue;
            }

            if (in_array($method, ['morphs', 'nullableMorphs', 'uuidMorphs', 'ulidMorphs'], true) && preg_match('/^' . $q . '/', $args, $a)) {
                $out[] = [$a[1] . '_id', 'add', null];
                $out[] = [$a[1] . '_type', 'add', null];
                continue;
            }

            if ($method === 'dropColumn' || $method === 'dropColumns') {
                preg_match_all('/' . $q . '/', $args, $aa);
                foreach ($aa[1] as $col) {
                    $out[] = [$col, 'drop', null];
                }
                continue;
            }

            if ($method === 'renameColumn' && preg_match('/^' . $q . '\s*,\s*' . $q . '/', $args, $a)) {
                $out[] = [$a[1], 'rename', $a[2]];
            }
        }

        return $out;
    }
}
