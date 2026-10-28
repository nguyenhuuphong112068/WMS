<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Thanh Thông báo (RightNAV) = lịch sử các đề nghị đã hiện ở trang chủ.
 *
 * - notifications.review_*  : phân loại giống thẻ trang chủ (loại / nhóm nghiệp vụ / đối tượng VT-HC-CC-DC);
 *                             review_key nhận diện một đề nghị để không ghi trùng.
 * - notification_recipients.resolved_at         : đề nghị không còn chờ người này nữa (đã xử lý).
 * - notification_recipients.scope_department_id : phòng ban đang chọn lúc thấy đề nghị lần cuối -
 *                             chỉ đánh dấu "đã xử lý" khi đang đứng đúng phòng đó, tránh chuyển phòng
 *                             làm đề nghị của phòng kia bị coi là xong.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            if (! Schema::hasColumn('notifications', 'review_type')) {
                $table->string('review_type', 50)->nullable()->after('activity_type');
            }
            if (! Schema::hasColumn('notifications', 'review_group')) {
                $table->string('review_group', 30)->nullable()->after('review_type');
            }
            if (! Schema::hasColumn('notifications', 'review_object')) {
                $table->string('review_object', 4)->nullable()->after('review_group');
            }
            if (! Schema::hasColumn('notifications', 'review_key')) {
                $table->string('review_key', 64)->nullable()->after('review_object');
                $table->index('review_key');
            }
        });

        Schema::table('notification_recipients', function (Blueprint $table) {
            if (! Schema::hasColumn('notification_recipients', 'resolved_at')) {
                $table->timestamp('resolved_at')->nullable()->after('read_at');
            }
            if (! Schema::hasColumn('notification_recipients', 'scope_department_id')) {
                $table->unsignedBigInteger('scope_department_id')->nullable()->after('resolved_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('notification_recipients', function (Blueprint $table) {
            $table->dropColumn(['resolved_at', 'scope_department_id']);
        });

        Schema::table('notifications', function (Blueprint $table) {
            $table->dropIndex(['review_key']);
            $table->dropColumn(['review_type', 'review_group', 'review_object', 'review_key']);
        });
    }
};
