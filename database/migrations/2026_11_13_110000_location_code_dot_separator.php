<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/** Mã vị trí: đoạn cuối <số ô ngang>-<dãy sâu> (05-2) đổi dấu thành 05.2. */
return new class extends Migration
{
    public function up(): void
    {
        $this->swap('#^(.*/)(\d{2,})-(\d+)$#', '.');
    }

    public function down(): void
    {
        $this->swap('#^(.*/)(\d{2,})\.(\d+)$#', '-');
    }

    private function swap(string $pattern, string $sep): void
    {
        DB::table('locations')->select('id', 'code')->orderBy('id')->chunkById(500, function ($rows) use ($pattern, $sep) {
            foreach ($rows as $row) {
                if (preg_match($pattern, (string) $row->code, $m)) {
                    DB::table('locations')->where('id', $row->id)->update(['code' => $m[1] . $m[2] . $sep . $m[3]]);
                }
            }
        });
    }
};
