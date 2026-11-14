-- =============================================================================
-- CẬP NHẬT CSDL NGÀY 13-14/11/2026  (MySQL 5.7+ / MariaDB 10.2+)
-- Gộp 5 migration:
--   1. 2026_11_13_130000_seed_export_material_label_permission.php
--   2. 2026_11_14_090000_add_sign_flow_to_chemical_standard_transfers.php
--   3. 2026_11_14_091000_seed_own_purchasing_departments_per_factory.php
--   4. 2026_11_14_100000_create_chemical_portal_declarations_table.php
--   5. 2026_11_14_110000_add_manufacturer_code_to_chemical_estimate_items_table.php
--
-- Chạy trên TỪNG CSDL công ty (vd. wms và wms_nm1): chọn đúng database trước khi chạy.
-- Script chạy lại nhiều lần vẫn an toàn (có kiểm tra tồn tại / dữ liệu đã có thì bỏ qua).
-- NÊN BACKUP CSDL TRƯỚC KHI CHẠY.
-- =============================================================================

SET NAMES utf8mb4;

-- Giờ hệ thống theo Asia/Ho_Chi_Minh (giống now() của Laravel), không phụ thuộc múi giờ MySQL server
SET @wms_now := CONVERT_TZ(UTC_TIMESTAMP(), '+00:00', '+07:00');

-- Thủ tục phụ: thêm cột nếu chưa có
DELIMITER $$
DROP PROCEDURE IF EXISTS wms_20261114_add_column $$
CREATE PROCEDURE wms_20261114_add_column(IN p_table VARCHAR(64), IN p_column VARCHAR(64), IN p_definition TEXT)
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
DELIMITER ;


-- -----------------------------------------------------------------------------
-- BƯỚC 1 (migration 1): Quyền "In Nhãn Cấp Phát Vật Tư" (export_material_label)
-- Cấp cho Admin + mọi vai trò đang có quyền export_material_issue
-- -----------------------------------------------------------------------------
INSERT INTO `permissions` (`permission_group`, `name`, `display_name`, `description`, `created_at`, `updated_at`)
VALUES (
    4,
    'export_material_label',
    'In Nhãn Cấp Phát Vật Tư',
    CONCAT(
        'In nhãn cấp phát (tên vật tư, ngày cấp, hạn dùng, số lượng, người đề nghị, mục đích, QR mã xuất nhập) cho một dòng ở tab Sổ cấp phát. Mỗi lần in được ghi Audit Trail.',
        CHAR(10),
        'Cũng in được nhãn Cách ly - Chờ quyết định và Loại bỏ - Chờ huỷ cho dòng sinh từ phiếu cách ly.',
        CHAR(10),
        'Lưu ý: phiếu cách ly đã huỷ / trả về kho, dòng loại bỏ lập thẳng và cấp phát liên phòng ban không có nhãn.'
    ),
    @wms_now,
    @wms_now
)
ON DUPLICATE KEY UPDATE
    `permission_group` = VALUES(`permission_group`),
    `display_name`     = VALUES(`display_name`),
    `description`      = VALUES(`description`),
    `updated_at`       = VALUES(`updated_at`);

SET @wms_label_pid := (SELECT `id` FROM `permissions` WHERE `name` = 'export_material_label' LIMIT 1);

INSERT IGNORE INTO `role_permission` (`role_id`, `permission_id`)
SELECT t.role_id, @wms_label_pid
FROM (
    SELECT r.id AS role_id FROM `roles` r WHERE r.name = 'Admin'
    UNION
    SELECT rp.role_id
    FROM `role_permission` rp
    JOIN `permissions` p ON p.id = rp.permission_id
    WHERE p.name = 'export_material_issue'
) t;


-- -----------------------------------------------------------------------------
-- BƯỚC 2 (migration 2): Ký duyệt đề nghị HC / Chất chuẩn liên phòng ban
-- Bảng chemical_transfer_signs, standard_transfer_signs + 4 cột trên *_transfer_requests
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `chemical_transfer_signs` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `transfer_request_id` BIGINT UNSIGNED NOT NULL,
    `step_no` TINYINT UNSIGNED NOT NULL,
    `role_names` VARCHAR(255) NULL DEFAULT NULL,
    `department_id` BIGINT UNSIGNED NULL DEFAULT NULL,
    `status` VARCHAR(20) NOT NULL DEFAULT 'pending',
    `signed_by` VARCHAR(255) NULL DEFAULT NULL,
    `signed_at` TIMESTAMP NULL DEFAULT NULL,
    `note` VARCHAR(500) NULL DEFAULT NULL,
    `active` TINYINT NOT NULL DEFAULT 1,
    `created_by` VARCHAR(255) NULL DEFAULT NULL,
    `updated_by` VARCHAR(255) NULL DEFAULT NULL,
    `created_at` TIMESTAMP NULL DEFAULT NULL,
    `updated_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `chemical_transfer_signs_request_step_index` (`transfer_request_id`, `step_no`),
    KEY `chemical_transfer_signs_department_id_index` (`department_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `standard_transfer_signs` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `transfer_request_id` BIGINT UNSIGNED NOT NULL,
    `step_no` TINYINT UNSIGNED NOT NULL,
    `role_names` VARCHAR(255) NULL DEFAULT NULL,
    `department_id` BIGINT UNSIGNED NULL DEFAULT NULL,
    `status` VARCHAR(20) NOT NULL DEFAULT 'pending',
    `signed_by` VARCHAR(255) NULL DEFAULT NULL,
    `signed_at` TIMESTAMP NULL DEFAULT NULL,
    `note` VARCHAR(500) NULL DEFAULT NULL,
    `active` TINYINT NOT NULL DEFAULT 1,
    `created_by` VARCHAR(255) NULL DEFAULT NULL,
    `updated_by` VARCHAR(255) NULL DEFAULT NULL,
    `created_at` TIMESTAMP NULL DEFAULT NULL,
    `updated_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `standard_transfer_signs_request_step_index` (`transfer_request_id`, `step_no`),
    KEY `standard_transfer_signs_department_id_index` (`department_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CALL wms_20261114_add_column('chemical_transfer_requests', 'current_step',       'TINYINT UNSIGNED NULL DEFAULT NULL AFTER `status`');
CALL wms_20261114_add_column('chemical_transfer_requests', 'sign_rejected_by',   'VARCHAR(255) NULL DEFAULT NULL AFTER `current_step`');
CALL wms_20261114_add_column('chemical_transfer_requests', 'sign_rejected_at',   'TIMESTAMP NULL DEFAULT NULL AFTER `sign_rejected_by`');
CALL wms_20261114_add_column('chemical_transfer_requests', 'sign_reject_reason', 'VARCHAR(500) NULL DEFAULT NULL AFTER `sign_rejected_at`');

CALL wms_20261114_add_column('standard_transfer_requests', 'current_step',       'TINYINT UNSIGNED NULL DEFAULT NULL AFTER `status`');
CALL wms_20261114_add_column('standard_transfer_requests', 'sign_rejected_by',   'VARCHAR(255) NULL DEFAULT NULL AFTER `current_step`');
CALL wms_20261114_add_column('standard_transfer_requests', 'sign_rejected_at',   'TIMESTAMP NULL DEFAULT NULL AFTER `sign_rejected_by`');
CALL wms_20261114_add_column('standard_transfer_requests', 'sign_reject_reason', 'VARCHAR(500) NULL DEFAULT NULL AFTER `sign_rejected_at`');


-- -----------------------------------------------------------------------------
-- BƯỚC 3 (migration 3): Mỗi nhà máy tự có bộ phận mua hàng (deparments.purchasing_key)
-- Chỉ xử lý DB có ĐÚNG 1 công ty. DB wms (NM2) đã có đủ 'supply' + 'admin' -> không đổi gì.
--   - supply: chưa có phòng nào -> gắn cho phòng "Cung Ứng - NM1" sẵn có, không có thì tạo mới
--   - admin : chưa có phòng nào -> gắn cho phòng "Hành Chính..." đang hoạt động đầu tiên
-- -----------------------------------------------------------------------------
SET @wms_one_company := ((SELECT COUNT(*) FROM `companies`) = 1);
SET @wms_company_id  := (SELECT MIN(`id`) FROM `companies`);

-- 3a. supply - phòng "Cung Ứng - NM1" đã có sẵn
SET @wms_has_supply := EXISTS (SELECT 1 FROM `deparments` WHERE `isActive` = 1 AND `purchasing_key` = 'supply');

UPDATE `deparments`
SET `purchasing_key` = 'supply', `isActive` = 1, `updated_at` = @wms_now
WHERE `name` = 'Cung Ứng - NM1'
  AND @wms_one_company = 1
  AND @wms_has_supply = 0
ORDER BY `id`
LIMIT 1;

-- 3b. supply - chưa có phòng thì tạo mới
SET @wms_has_supply := EXISTS (SELECT 1 FROM `deparments` WHERE `isActive` = 1 AND `purchasing_key` = 'supply');

INSERT INTO `deparments`
    (`company_id`, `name`, `shortName`, `EngshortName`, `prepareBy`, `isActive`, `is_general`, `purchasing_key`, `created_at`, `updated_at`)
SELECT @wms_company_id, 'Cung Ứng - NM1', 'CƯ', 'PROC', 'Hệ thống', 1, 1, 'supply', @wms_now, @wms_now
FROM DUAL
WHERE @wms_one_company = 1
  AND @wms_has_supply = 0
  AND NOT EXISTS (SELECT 1 FROM `deparments` WHERE `name` = 'Cung Ứng - NM1');

-- 3c. admin (Hành Chính - mua + giữ kho VPP)
SET @wms_has_admin := EXISTS (SELECT 1 FROM `deparments` WHERE `isActive` = 1 AND `purchasing_key` = 'admin');

UPDATE `deparments`
SET `purchasing_key` = 'admin', `updated_at` = @wms_now
WHERE `isActive` = 1
  AND `purchasing_key` IS NULL
  AND `name` LIKE 'Hành Chính%'
  AND @wms_one_company = 1
  AND @wms_has_admin = 0
ORDER BY `id`
LIMIT 1;


-- -----------------------------------------------------------------------------
-- BƯỚC 4 (migration 4): Khai báo Cổng thông tin quốc gia NHIỀU LẦN
-- Bảng chemical_portal_declarations + cột national_portal_manufacturer_code
-- -----------------------------------------------------------------------------
SET @wms_portal_table_existed := EXISTS (
    SELECT 1 FROM information_schema.TABLES
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'chemical_portal_declarations'
);

CREATE TABLE IF NOT EXISTS `chemical_portal_declarations` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `chemical_category_id` BIGINT UNSIGNED NOT NULL,
    `declaration_no` VARCHAR(100) NULL DEFAULT NULL,
    `manufacturer_code` VARCHAR(100) NULL DEFAULT NULL,
    `status_id` BIGINT UNSIGNED NOT NULL DEFAULT 1,
    `created_by` VARCHAR(255) NULL DEFAULT NULL,
    `updated_by` VARCHAR(255) NULL DEFAULT NULL,
    `created_at` TIMESTAMP NULL DEFAULT NULL,
    `updated_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `chemical_portal_declarations_chemical_category_id_index` (`chemical_category_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Chép các mã đã xác nhận khai báo trước đây làm lần khai báo đầu tiên (Code NSX để trống)
-- Chỉ chép khi bảng vừa được tạo ở lần chạy này
INSERT INTO `chemical_portal_declarations`
    (`chemical_category_id`, `declaration_no`, `manufacturer_code`, `status_id`, `created_by`, `created_at`, `updated_at`)
SELECT
    c.id,
    c.national_portal_declaration_no,
    NULL,
    1,
    c.national_portal_declared_by,
    COALESCE(c.national_portal_declared_at, c.updated_at, @wms_now),
    COALESCE(c.national_portal_declared_at, c.updated_at, @wms_now)
FROM `chemical_categories` c
WHERE @wms_portal_table_existed = 0
  AND c.national_portal_declared = 1
  AND NOT EXISTS (SELECT 1 FROM `chemical_portal_declarations` d WHERE d.chemical_category_id = c.id);

CALL wms_20261114_add_column('chemical_categories',         'national_portal_manufacturer_code', 'VARCHAR(100) NULL DEFAULT NULL AFTER `national_portal_declaration_no`');
CALL wms_20261114_add_column('chemical_category_histories', 'national_portal_manufacturer_code', 'VARCHAR(100) NULL DEFAULT NULL AFTER `national_portal_declaration_no`');


-- -----------------------------------------------------------------------------
-- BƯỚC 5 (migration 5): "Code nhà sản xuất" của từng mặt hàng Dự Trù Hoá Chất
-- -----------------------------------------------------------------------------
CALL wms_20261114_add_column('chemical_estimate_items', 'manufacturer_code', 'VARCHAR(100) NULL DEFAULT NULL AFTER `chem_name`');


DROP PROCEDURE IF EXISTS wms_20261114_add_column;


-- -----------------------------------------------------------------------------
-- BƯỚC 6: Ghi nhận 5 migration vào bảng migrations (để php artisan migrate không chạy lại)
-- -----------------------------------------------------------------------------
SET @wms_batch := (SELECT COALESCE(MAX(batch), 0) + 1 FROM `migrations`);

INSERT INTO `migrations` (`migration`, `batch`)
SELECT m.migration, @wms_batch
FROM (
    SELECT '2026_11_13_130000_seed_export_material_label_permission' AS migration
    UNION ALL SELECT '2026_11_14_090000_add_sign_flow_to_chemical_standard_transfers'
    UNION ALL SELECT '2026_11_14_091000_seed_own_purchasing_departments_per_factory'
    UNION ALL SELECT '2026_11_14_100000_create_chemical_portal_declarations_table'
    UNION ALL SELECT '2026_11_14_110000_add_manufacturer_code_to_chemical_estimate_items_table'
) m
WHERE NOT EXISTS (SELECT 1 FROM `migrations` x WHERE x.migration = m.migration);
