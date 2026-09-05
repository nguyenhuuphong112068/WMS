<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

/**
 * Bản sao lưu file đính kèm phiếu nhập (hoá chất / vật tư / chất chuẩn).
 *
 * File gốc vẫn lưu ở disk mặc định (storage/app/private/public/<folder>/...), route
 * download vẫn đọc từ đó. Đây chỉ là 1 bản sao thêm ra public/uploads/<folder>/ theo
 * yêu cầu quản trị, không dùng để phục vụ tải file qua web.
 */
class AttachmentBackup
{
    public static function copy(string $storagePath, string $folder): void
    {
        if (! Storage::exists($storagePath)) {
            return;
        }

        $destDir = public_path('uploads/'.$folder);

        if (! is_dir($destDir)) {
            mkdir($destDir, 0755, true);
        }

        copy(Storage::path($storagePath), $destDir.DIRECTORY_SEPARATOR.basename($storagePath));
    }

    public static function delete(string $storagePath, string $folder): void
    {
        $target = public_path('uploads/'.$folder.'/'.basename($storagePath));

        if (is_file($target)) {
            @unlink($target);
        }
    }
}
