-- =============================================================================
-- CẬP NHẬT CSDL NGÀY 14/11/2026  (MySQL 5.7+ / MariaDB 10.2+)
-- Tương đương migration: 2026_11_14_120000_add_chemical_return_to_chemical_exports.php
--
-- TRẢ HOÁ CHẤT ACETONITRIL / METHANOL VỀ KHO
--   - chemical_exports.return_of_id: dòng type = 'return' (amount âm) trỏ về phiếu sử dụng gốc
--   - chemical_exports.type thêm giá trị 'return'
--   - Quyền export_chemical_return + quyền xem tab export_chemical_tab_return
--     (con của export_chemical_view), cấp cho Admin, Quản Lý Bộ Phân, Quản Lý Kho Hoá Chất,
--     Tổ Trưởng Quản Lý Kho Hoá Chất, Quản Lý Tổ Chuyên Môn
--   - Cập nhật diễn giải quyền export_chemical_update
--
-- Chạy trên TỪNG CSDL công ty (vd. wms và wms_nm1): chọn đúng database trước khi chạy.
-- Script chạy lại nhiều lần vẫn an toàn (có kiểm tra tồn tại / dữ liệu đã có thì bỏ qua).
-- NÊN BACKUP CSDL TRƯỚC KHI CHẠY.
-- =============================================================================

SET NAMES utf8mb4;

-- Giờ hệ thống theo Asia/Ho_Chi_Minh (giống now() của Laravel), không phụ thuộc múi giờ MySQL server
SET @wms_now := CONVERT_TZ(UTC_TIMESTAMP(), '+00:00', '+07:00');

-- Thủ tục phụ: thêm cột / index nếu chưa có
DELIMITER $$
DROP PROCEDURE IF EXISTS wms_20261114r_add_column $$
CREATE PROCEDURE wms_20261114r_add_column(IN p_table VARCHAR(64), IN p_column VARCHAR(64), IN p_definition TEXT)
BEGIN
    IF EXISTS (
        SELECT 1 FROM information_schema.TABLES
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = p_table
    ) AND NOT EXISTS (
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = p_table AND COLUMN_NAME = p_column
    ) THEN
        SET @wms_sql := CONCAT('ALTER TABLE `', p_table, '` ADD COLUMN `', p_column, '` ', p_definition);
        PREPARE wms_stmt FROM @wms_sql;
        EXECUTE wms_stmt;
        DEALLOCATE PREPARE wms_stmt;
    END IF;
END $$

DROP PROCEDURE IF EXISTS wms_20261114r_add_index $$
CREATE PROCEDURE wms_20261114r_add_index(IN p_table VARCHAR(64), IN p_index VARCHAR(64), IN p_columns TEXT)
BEGIN
    IF EXISTS (
        SELECT 1 FROM information_schema.TABLES
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = p_table
    ) AND NOT EXISTS (
        SELECT 1 FROM information_schema.STATISTICS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = p_table AND INDEX_NAME = p_index
    ) THEN
        SET @wms_sql := CONCAT('ALTER TABLE `', p_table, '` ADD INDEX `', p_index, '` (', p_columns, ')');
        PREPARE wms_stmt FROM @wms_sql;
        EXECUTE wms_stmt;
        DEALLOCATE PREPARE wms_stmt;
    END IF;
END $$
DELIMITER ;


-- -----------------------------------------------------------------------------
-- BƯỚC 1: Cột chemical_exports.return_of_id + loại dòng mới 'return'
-- -----------------------------------------------------------------------------
CALL wms_20261114r_add_column('chemical_exports', 'return_of_id', 'BIGINT UNSIGNED NULL DEFAULT NULL AFTER `quarantine_id`');
CALL wms_20261114r_add_index('chemical_exports', 'chemical_exports_return_of_id_index', '`return_of_id`');

-- Loại dòng mới 'return' (trả về kho, số lượng âm)
ALTER TABLE `chemical_exports`
    MODIFY `type` ENUM('export','cancel','transfer_out','return') NULL DEFAULT 'export';

DROP PROCEDURE IF EXISTS wms_20261114r_add_column;
DROP PROCEDURE IF EXISTS wms_20261114r_add_index;


-- -----------------------------------------------------------------------------
-- BƯỚC 2: Quyền "Trả Hoá Chất Acetonitril / Methanol Về Kho" (export_chemical_return)
-- -----------------------------------------------------------------------------
INSERT INTO `permissions` (`permission_group`, `parent_id`, `name`, `display_name`, `description`, `created_at`, `updated_at`)
VALUES (
    4,
    NULL,
    'export_chemical_return',
    'Trả Hoá Chất Acetonitril / Methanol Về Kho',
    CONCAT(
        'Khai báo trả lại tồn một lượng Acetonitril hoặc Methanol đã ghi sử dụng (tab Trả hoá chất Acetonitril hoặc Methanol về kho): lượng trả cộng lại đúng lô đã lấy ra, và huỷ phiếu trả đã khai.',
        CHAR(10),
        'Lưu ý: tổng lượng trả của một phiếu sử dụng không vượt số lượng đã sử dụng; huỷ phiếu trả chỉ được khi lô còn đủ tồn để trừ lại.'
    ),
    @wms_now,
    @wms_now
)
ON DUPLICATE KEY UPDATE
    `permission_group` = VALUES(`permission_group`),
    `parent_id`        = VALUES(`parent_id`),
    `display_name`     = VALUES(`display_name`),
    `description`      = VALUES(`description`),
    `created_at`       = VALUES(`created_at`),
    `updated_at`       = VALUES(`updated_at`);


-- -----------------------------------------------------------------------------
-- BƯỚC 3: Quyền xem tab (export_chemical_tab_return) - con của export_chemical_view
-- -----------------------------------------------------------------------------
SET @wms_view_pid := (SELECT `id` FROM `permissions` WHERE `name` = 'export_chemical_view' LIMIT 1);

INSERT INTO `permissions` (`permission_group`, `parent_id`, `name`, `display_name`, `description`, `created_at`, `updated_at`)
VALUES (
    4,
    @wms_view_pid,
    'export_chemical_tab_return',
    'Tab Trả hoá chất Acetonitril hoặc Methanol về kho',
    'Xem tab Trả hoá chất Acetonitril hoặc Methanol về kho: các phiếu sử dụng Acetonitril / Methanol còn trả được và lịch sử các lần trả về kho.',
    @wms_now,
    @wms_now
)
ON DUPLICATE KEY UPDATE
    `permission_group` = VALUES(`permission_group`),
    `parent_id`        = VALUES(`parent_id`),
    `display_name`     = VALUES(`display_name`),
    `description`      = VALUES(`description`),
    `created_at`       = VALUES(`created_at`),
    `updated_at`       = VALUES(`updated_at`);


-- -----------------------------------------------------------------------------
-- BƯỚC 4: Diễn giải quyền export_chemical_update
-- (dòng trả về kho trong Sổ sử dụng không sửa / khoá ở đó)
-- -----------------------------------------------------------------------------
UPDATE `permissions`
SET `description` = CONCAT(
        'Sửa (lô, số lượng, người kiểm tra, số PKN / OOS / BCSL, mục đích) hoặc khoá / mở khoá một dòng ở tab Sổ sử dụng hoá chất, kể cả phiếu ngày cũ. Khoá thì số lượng được cộng trả lại tồn, mở khoá thì trừ lại; mỗi lần sửa được lưu lịch sử điều chỉnh.',
        CHAR(10),
        'Lưu ý: dòng sinh từ phiếu cách ly chỉ xử lý ở tab Cách ly - Loại bỏ - Huỷ; dòng chuyển cho phòng khác không sửa được; dòng trả về kho chỉ huỷ ở tab Trả hoá chất Acetonitril hoặc Methanol về kho, phiếu sử dụng đã có lượng trả không khoá / đổi lô được và không sửa số lượng nhỏ hơn lượng đã trả.'
    ),
    `updated_at` = @wms_now
WHERE `name` = 'export_chemical_update';


-- -----------------------------------------------------------------------------
-- BƯỚC 5: Cấp 2 quyền mới cho các vai trò
-- -----------------------------------------------------------------------------
INSERT IGNORE INTO `role_permission` (`role_id`, `permission_id`)
SELECT r.id, p.id
FROM `roles` r
CROSS JOIN `permissions` p
WHERE p.name IN ('export_chemical_return', 'export_chemical_tab_return')
  AND r.name IN (
        'Admin',
        'Quản Lý Bộ Phân',
        'Quản Lý Bộ Phận',
        'Quản Lý Kho Hóa Chất',
        'Quản Lý Kho Hoá Chất',
        'Tổ Tưởng Quản Lý Kho Hoá chất - Chất chuẩn',
        'Tổ Trưởng Quản Lý Kho Hoá chất - Chất chuẩn',
        'Quản Lý Tổ Chuyên Môn'
  );


-- -----------------------------------------------------------------------------
-- BƯỚC 6: Ghi nhận migration vào bảng migrations (để php artisan migrate không chạy lại)
-- -----------------------------------------------------------------------------
SET @wms_batch := (SELECT COALESCE(MAX(batch), 0) + 1 FROM `migrations`);

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_11_14_120000_add_chemical_return_to_chemical_exports', @wms_batch
FROM DUAL
WHERE NOT EXISTS (
    SELECT 1 FROM `migrations` x
    WHERE x.migration = '2026_11_14_120000_add_chemical_return_to_chemical_exports'
);


-- =============================================================================
-- HOÀN TÁC (tương đương down()) - CHỈ chạy khi cần gỡ bỏ, bỏ comment rồi chạy:
-- =============================================================================
-- DELETE up FROM `user_permission` up JOIN `permissions` p ON p.id = up.permission_id
--  WHERE p.name IN ('export_chemical_return', 'export_chemical_tab_return');
-- DELETE rp FROM `role_permission` rp JOIN `permissions` p ON p.id = rp.permission_id
--  WHERE p.name IN ('export_chemical_return', 'export_chemical_tab_return');
-- DELETE FROM `permissions` WHERE `name` IN ('export_chemical_return', 'export_chemical_tab_return');
-- DELETE FROM `chemical_exports` WHERE `type` = 'return';
-- ALTER TABLE `chemical_exports` MODIFY `type` ENUM('export','cancel','transfer_out') NULL DEFAULT 'export';
-- ALTER TABLE `chemical_exports` DROP INDEX `chemical_exports_return_of_id_index`, DROP COLUMN `return_of_id`;
-- DELETE FROM `migrations` WHERE `migration` = '2026_11_14_120000_add_chemical_return_to_chemical_exports';
