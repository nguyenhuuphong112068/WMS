<?php

namespace App\Console\Commands;

use App\Support\InitialData\InitialDataImporter;
use App\Support\InitialData\InitialDataSchema;
use App\Support\InitialData\InitialDataTemplate;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * KHỞI TẠO DỮ LIỆU BAN ĐẦU TỪ DÒNG LỆNH
 *
 * Cùng bộ luật với màn hình Dữ Liệu Gốc > Khởi Tạo Dữ Liệu, dùng khi file lớn (chạy trên web
 * dễ quá thời gian) hoặc khi chuẩn bị máy chủ mới chưa có tài khoản Admin.
 *
 *   php artisan wms:initial-data template [đường dẫn.xlsx]
 *   php artisan wms:initial-data check  file.xlsx
 *   php artisan wms:initial-data import file.xlsx --password="Wms@2026"
 */
class InitialDataCommand extends Command
{
    protected $signature = 'wms:initial-data
        {action : template | check | import}
        {file? : file Excel (template: nơi lưu file mẫu; check/import: file đã điền)}
        {--department= : (template) tên viết tắt phòng ban - tạo file riêng của phòng đó}
        {--stage= : (template) 1 | 2 | 3 - tạo file chỉ thu thập một giai đoạn}
        {--module= : (template) material | chemical | standard - tạo file chỉ thu thập một module}
        {--password= : mật khẩu khởi tạo chung cho tài khoản mới ở sheet Người dùng}
        {--actor=Khởi tạo dữ liệu (CLI) : tên người thực hiện ghi vào lịch sử}';

    protected $description = 'Tải file Excel mẫu / kiểm tra / nạp dữ liệu ban đầu (dữ liệu gốc, danh mục, định khu, tồn đầu kỳ)';

    public function handle(): int
    {
        $action = $this->argument('action');
        $file = $this->argument('file');

        if ($action === 'template') {
            $path = $file ?: storage_path('app/initial-data/Mau_Khoi_Tao_Du_Lieu_WMS.xlsx');
            $departmentId = null;
            if ($this->option('department')) {
                $departmentId = DB::table('deparments')->where('shortName', $this->option('department'))->value('id');
                if (! $departmentId) {
                    $this->error('Không có phòng ban: '.$this->option('department'));

                    return self::INVALID;
                }
            }
            $module = $this->option('module') ?: null;
            if ($module !== null && ! isset(InitialDataSchema::MODULES[$module])) {
                $this->error('Module phải là: '.implode(', ', array_keys(InitialDataSchema::MODULES)));

                return self::INVALID;
            }
            $stage = $this->option('stage') ? (int) $this->option('stage') : null;
            if ($stage !== null && ! isset(InitialDataSchema::STAGES[$stage])) {
                $this->error('Giai đoạn phải là: '.implode(', ', array_keys(InitialDataSchema::STAGES)));

                return self::INVALID;
            }
            InitialDataTemplate::save($path, $departmentId, $module, $stage);
            $this->info('Đã tạo file mẫu: '.$path);

            return self::SUCCESS;
        }

        if (! in_array($action, ['check', 'import'], true)) {
            $this->error('Thao tác phải là template, check hoặc import.');

            return self::INVALID;
        }

        if (! $file || ! is_file($file)) {
            $this->error('Không tìm thấy file: '.($file ?: '(chưa truyền)'));

            return self::INVALID;
        }

        $report = (new InitialDataImporter($this->option('actor'), 'CLI', $this->option('password')))
            ->run($file, $action === 'import', basename($file));

        $this->table(
            ['Sheet', 'Số dòng', $report['committed'] ? 'Đã thêm mới' : 'Sẽ thêm mới', 'Đã có sẵn'],
            collect($report['stats'])->where('rows', '>', 0)
                ->map(fn ($s) => [$s['title'], $s['rows'], $s['inserted'], $s['existing']])->all()
        );

        foreach ($report['warnings'] as $w) {
            $this->warn(sprintf('Lưu ý [%s] dòng %s %s: %s', $w['sheet'], $w['row'] ?? '-', $w['column'] ?? '', $w['message']));
        }

        if ($report['error_total']) {
            foreach ($report['errors'] as $e) {
                $this->line(sprintf('<fg=red>Lỗi</> [%s] dòng %s %s: %s', $e['sheet'] ?: '-', $e['row'] ?? '-', $e['column'] ?? '', $e['message']));
            }
            $this->error($report['error_total'].' lỗi - chưa có dữ liệu nào được ghi.');

            return self::FAILURE;
        }

        $report['committed']
            ? $this->info('Đã nạp dữ liệu vào hệ thống.')
            : $this->info('Kiểm tra đạt - chạy lại với "import" để ghi vào hệ thống.');

        return self::SUCCESS;
    }
}
