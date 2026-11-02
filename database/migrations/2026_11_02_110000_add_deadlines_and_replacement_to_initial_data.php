<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bảng theo dõi khởi tạo dữ liệu:
 *  - initial_data_files.replaced_by_id: nộp file mới cho cùng việc (giai đoạn + module + phòng ban)
 *    thì file trước tự hết hiệu lực (status_id = 0) và trỏ sang file thay thế - vẫn giữ để xem
 *    lịch sử nộp trên từng dòng việc.
 *  - initial_data_deadlines: hạn nộp của giai đoạn 2 - 4 do Admin đặt; quá hạn không đính kèm được.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('initial_data_files', function (Blueprint $table) {
            $table->unsignedBigInteger('replaced_by_id')->nullable()->after('uploaded_by_user_id')
                ->comment('File nộp sau thay thế file này (file này đã hết hiệu lực)');
        });

        Schema::create('initial_data_deadlines', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('stage')->unique();
            $table->date('deadline')->comment('Hạn nộp - hết ngày này thì không đính kèm được nữa');
            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('initial_data_deadlines');

        Schema::table('initial_data_files', function (Blueprint $table) {
            $table->dropColumn('replaced_by_id');
        });
    }
};
