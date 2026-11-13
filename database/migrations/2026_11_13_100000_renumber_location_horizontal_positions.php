<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Vị trí ngang của định khu đổi từ chữ (A, B, ..., AA) sang số nguyên từ 1:
 * đoạn cuối mã vị trí <chữ><dãy sâu> (vd. E2) thành <số 2 chữ số>.<dãy sâu> (vd. 05.2).
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('locations')->select('id', 'code')->orderBy('id')->chunkById(500, function ($rows) {
            foreach ($rows as $row) {
                if (! preg_match('#^(.*/)([A-Z]+)(\d+)$#', (string) $row->code, $m)) {
                    continue;
                }
                $n = 0;
                foreach (str_split($m[2]) as $ch) {
                    $n = $n * 26 + (ord($ch) - 64);
                }
                DB::table('locations')->where('id', $row->id)
                    ->update(['code' => $m[1] . str_pad((string) $n, 2, '0', STR_PAD_LEFT) . '.' . $m[3]]);
            }
        });
    }

    public function down(): void
    {
        DB::table('locations')->select('id', 'code')->orderBy('id')->chunkById(500, function ($rows) {
            foreach ($rows as $row) {
                if (! preg_match('#^(.*/)(\d+)-(\d+)$#', (string) $row->code, $m)) {
                    continue;
                }
                $n = (int) $m[2];
                $out = '';
                while ($n > 0) {
                    $n--;
                    $out = chr(65 + $n % 26) . $out;
                    $n = intdiv($n, 26);
                }
                DB::table('locations')->where('id', $row->id)->update(['code' => $m[1] . $out . $m[3]]);
            }
        });
    }
};
