-- =============================================================================
-- CẬP NHẬT CSDL NGÀY 16/11/2026  (MySQL 5.7+ / MariaDB 10.2+)
-- Tương đương migration: 2026_11_16_090000_create_vpp_names_and_split_from_material_names.php
--
-- DỮ LIỆU GỐC "TÊN VPP" - tách tên văn phòng phẩm khỏi Tên Vật Tư
--   - Bảng mới vpp_names (cùng cấu trúc material_names)
--   - Tên chuyển sang vpp_names (giữ nguyên id): mọi tên Danh Mục VPP đang / từng dùng + toàn bộ tên VPP
--     nạp từ DanhMucVPPALL (created_by 'Import DanhMucVPPALL (system)') mà Danh Mục Vật Tư chưa dùng.
--     Tên Danh Mục Vật Tư cũng đang dùng thì chép sang (giữ ở cả hai); tên còn lại xoá khỏi material_names.
--   - Lịch sử (datamaster_histories) đi theo tên: chuyển (hoặc chép) sang table_name 'vpp_names',
--     nhãn "Tên vật tư" trong ảnh chụp / mô tả đổi thành "Tên VPP"
--   - vpp_categories.material_names_id / vpp_category_histories.material_names_id -> vpp_names_id
--   - Quyền materData_vpp_review (chép vai trò / user từ materData_material_review)
--     + quyền con materData_vpp_{view,manage,review}_vppName
--
-- Chạy trên TỪNG CSDL công ty (vd. wms và wms_nm1): chọn đúng database trước khi chạy.
-- Script chạy lại nhiều lần vẫn an toàn: phần chuyển dữ liệu chỉ chạy khi vpp_categories còn cột
-- material_names_id (chưa đổi tên); quyền đã có thì chỉ cập nhật.
-- Upload code mới (VppNameController, view...) CÙNG LÚC với chạy script này.
-- NÊN BACKUP CSDL TRƯỚC KHI CHẠY.
-- =============================================================================

SET NAMES utf8mb4;

-- Giờ hệ thống theo Asia/Ho_Chi_Minh (giống now() của Laravel), không phụ thuộc múi giờ MySQL server
SET @wms_now := CONVERT_TZ(UTC_TIMESTAMP(), '+00:00', '+07:00');


-- -----------------------------------------------------------------------------
-- BƯỚC 1: Bảng vpp_names
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `vpp_names` (
    `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name`        VARCHAR(255) NOT NULL,
    `app_status`  VARCHAR(20) NOT NULL DEFAULT 'pending',
    `status_id`   TINYINT NOT NULL DEFAULT 1,
    `created_by`  VARCHAR(255) NULL DEFAULT NULL,
    `updated_by`  VARCHAR(255) NULL DEFAULT NULL,
    `approved_by` VARCHAR(255) NULL DEFAULT NULL,
    `approved_at` TIMESTAMP NULL DEFAULT NULL,
    `created_at`  TIMESTAMP NULL DEFAULT NULL,
    `updated_at`  TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `vpp_names_name_unique` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- -----------------------------------------------------------------------------
-- BƯỚC 2: Chuyển tên + lịch sử sang vpp_names, đổi tên cột ở Danh Mục VPP
-- -----------------------------------------------------------------------------
DELIMITER $$
DROP PROCEDURE IF EXISTS wms_20261116_vpp_names_split $$
CREATE PROCEDURE wms_20261116_vpp_names_split()
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;

    -- Chỉ chạy khi vpp_categories còn cột material_names_id (chưa chạy script này lần nào)
    IF EXISTS (
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'vpp_categories' AND COLUMN_NAME = 'material_names_id'
    ) THEN

        -- Danh sách id tên cần chuyển; shared = 1 khi Danh Mục Vật Tư (hoặc lịch sử của nó) cũng dùng
        DROP TEMPORARY TABLE IF EXISTS wms_tmp_vpp_move;
        CREATE TEMPORARY TABLE wms_tmp_vpp_move (
            `id`     BIGINT UNSIGNED NOT NULL PRIMARY KEY,
            `shared` TINYINT(1) NOT NULL DEFAULT 0
        ) ENGINE=InnoDB;

        -- Tên Danh Mục VPP đang / từng dùng
        INSERT IGNORE INTO wms_tmp_vpp_move (`id`)
        SELECT DISTINCT `material_names_id` FROM `vpp_categories` WHERE `material_names_id` IS NOT NULL;

        INSERT IGNORE INTO wms_tmp_vpp_move (`id`)
        SELECT DISTINCT `material_names_id` FROM `vpp_category_histories` WHERE `material_names_id` IS NOT NULL;

        -- Tên VPP nạp từ DanhMucVPPALL mà Danh Mục Vật Tư chưa dùng
        INSERT IGNORE INTO wms_tmp_vpp_move (`id`)
        SELECT mn.`id`
        FROM `material_names` mn
        WHERE mn.`created_by` = 'Import DanhMucVPPALL (system)'
          AND NOT EXISTS (SELECT 1 FROM `material_categories` mc WHERE mc.`material_names_id` = mn.`id`)
          AND NOT EXISTS (SELECT 1 FROM `material_category_histories` mh WHERE mh.`material_names_id` = mn.`id`);

        -- Đánh dấu tên dùng chung với Danh Mục Vật Tư
        UPDATE wms_tmp_vpp_move t
        SET t.`shared` = 1
        WHERE EXISTS (SELECT 1 FROM `material_categories` mc WHERE mc.`material_names_id` = t.`id`)
           OR EXISTS (SELECT 1 FROM `material_category_histories` mh WHERE mh.`material_names_id` = t.`id`);

        START TRANSACTION;

        -- Chép tên sang vpp_names (giữ nguyên id)
        INSERT IGNORE INTO `vpp_names`
            (`id`, `name`, `app_status`, `status_id`, `created_by`, `updated_by`, `approved_by`, `approved_at`, `created_at`, `updated_at`)
        SELECT mn.`id`, mn.`name`, mn.`app_status`, mn.`status_id`, mn.`created_by`, mn.`updated_by`,
               mn.`approved_by`, mn.`approved_at`, mn.`created_at`, mn.`updated_at`
        FROM `material_names` mn
        JOIN wms_tmp_vpp_move t ON t.`id` = mn.`id`;

        -- Lịch sử tên dùng chung: chép một bản cho vpp_names (bỏ qua nếu đã có)
        INSERT INTO `datamaster_histories`
            (`table_name`, `record_id`, `action`, `snapshot`, `change_note`, `change_reason`, `created_by`, `created_at`)
        SELECT 'vpp_names', h.`record_id`, h.`action`, h.`snapshot`, h.`change_note`, h.`change_reason`, h.`created_by`, h.`created_at`
        FROM `datamaster_histories` h
        JOIN wms_tmp_vpp_move t ON t.`id` = h.`record_id` AND t.`shared` = 1
        WHERE h.`table_name` = 'material_names'
          AND NOT EXISTS (
              SELECT 1 FROM (SELECT DISTINCT `record_id` FROM `datamaster_histories` WHERE `table_name` = 'vpp_names') v
              WHERE v.`record_id` = h.`record_id`
          )
        ORDER BY h.`id`;

        -- Lịch sử tên chỉ VPP dùng: chuyển hẳn
        UPDATE `datamaster_histories` h
        JOIN wms_tmp_vpp_move t ON t.`id` = h.`record_id` AND t.`shared` = 0
        SET h.`table_name` = 'vpp_names'
        WHERE h.`table_name` = 'material_names';

        -- Nhãn "Tên vật tư" -> "Tên VPP"
        UPDATE `datamaster_histories`
        SET `snapshot`    = REPLACE(`snapshot`, '"Tên vật tư"', '"Tên VPP"'),
            `change_note` = REPLACE(`change_note`, 'Tên vật tư', 'Tên VPP')
        WHERE `table_name` = 'vpp_names';

        -- Xoá khỏi material_names các tên chỉ VPP dùng
        DELETE mn FROM `material_names` mn
        JOIN wms_tmp_vpp_move t ON t.`id` = mn.`id` AND t.`shared` = 0;

        COMMIT;

        DROP TEMPORARY TABLE IF EXISTS wms_tmp_vpp_move;

        -- Đổi tên cột (MariaDB 10.4 chưa có RENAME COLUMN nên dùng CHANGE, giữ nguyên kiểu / NULL)
        ALTER TABLE `vpp_categories`
            CHANGE COLUMN `material_names_id` `vpp_names_id` BIGINT UNSIGNED NOT NULL;
    END IF;

    IF EXISTS (
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'vpp_category_histories' AND COLUMN_NAME = 'material_names_id'
    ) THEN
        ALTER TABLE `vpp_category_histories`
            CHANGE COLUMN `material_names_id` `vpp_names_id` BIGINT UNSIGNED NULL DEFAULT NULL;
    END IF;
END $$
DELIMITER ;

CALL wms_20261116_vpp_names_split();
DROP PROCEDURE IF EXISTS wms_20261116_vpp_names_split;


-- -----------------------------------------------------------------------------
-- BƯỚC 3: Quyền nhóm materData_vpp_review (Duyệt/Từ Chối Dữ Liệu Gốc VPP)
-- Lần đầu tạo: cấp cho mọi vai trò / user đang có materData_material_review
-- -----------------------------------------------------------------------------
SET @wms_mat_review_id := (SELECT `id` FROM `permissions` WHERE `name` = 'materData_material_review' LIMIT 1);

INSERT IGNORE INTO `permissions` (`permission_group`, `parent_id`, `name`, `display_name`, `description`, `created_at`, `updated_at`)
SELECT s.`permission_group`, NULL, 'materData_vpp_review', 'Duyệt/Từ Chối Dữ Liệu Gốc VPP',
    CONCAT(
        'Quyền nền cho nút Duyệt / Từ chối bản ghi đang Chờ duyệt ở các màn Dữ Liệu Gốc nhóm VPP có bước duyệt (Tên VPP).',
        CHAR(10),
        'Lưu ý: bật thêm quyền con "Duyệt/Từ Chối <màn hình>". Bản ghi chỉ chọn được ở các màn nghiệp vụ sau khi Đã duyệt.'
    ),
    @wms_now, @wms_now
FROM `permissions` s
WHERE s.`id` = @wms_mat_review_id;

SET @wms_new := (ROW_COUNT() = 1);
SET @wms_vpp_review_id := (SELECT `id` FROM `permissions` WHERE `name` = 'materData_vpp_review' LIMIT 1);

INSERT IGNORE INTO `role_permission` (`role_id`, `permission_id`)
SELECT rp.`role_id`, @wms_vpp_review_id
FROM `role_permission` rp
WHERE rp.`permission_id` = @wms_mat_review_id AND @wms_new = 1;

INSERT IGNORE INTO `user_permission` (`user_id`, `permission_id`, `is_denied`)
SELECT up.`user_id`, @wms_vpp_review_id, up.`is_denied`
FROM `user_permission` up
WHERE up.`permission_id` = @wms_mat_review_id AND @wms_new = 1;


-- -----------------------------------------------------------------------------
-- BƯỚC 4: Diễn giải quyền nhóm materData_vpp_view / materData_vpp_manage
-- -----------------------------------------------------------------------------
UPDATE `permissions`
SET `description` = CONCAT(
        'Quyền nền để xem các màn Dữ Liệu Gốc nhóm VPP: Tên VPP. Chỉ xem, không thêm hay sửa được.',
        CHAR(10),
        'Lưu ý: chọn màn nào hiện trên menu bằng các quyền con "Xem <màn hình>" bên dưới; chỉ có quyền này mà không có quyền con thì không thấy màn nào.'
    ),
    `updated_at` = @wms_now
WHERE `name` = 'materData_vpp_view';

UPDATE `permissions`
SET `description` = CONCAT(
        'Quyền nền cho các nút Thêm mới, Sửa, Khoá / Mở khoá ở các màn Dữ Liệu Gốc nhóm VPP (Tên VPP).',
        CHAR(10),
        'Lưu ý: bật thêm quyền con "Thêm/Sửa/Khoá <màn hình>" cho từng màn được phép làm. Sửa bản ghi đã duyệt sẽ đưa bản ghi về Chờ duyệt.'
    ),
    `updated_at` = @wms_now
WHERE `name` = 'materData_vpp_manage';


-- -----------------------------------------------------------------------------
-- BƯỚC 5: Quyền con màn Tên VPP (MaterDataPermissions::seed cho vppName)
-- Quyền con MỚI được cấp cho mọi vai trò / user đang có quyền nhóm cha
-- -----------------------------------------------------------------------------

-- 5.1 Xem Tên VPP  (cha: materData_vpp_view)
SET @wms_parent_id := (SELECT `id` FROM `permissions` WHERE `name` = 'materData_vpp_view' LIMIT 1);

INSERT INTO `permissions` (`permission_group`, `parent_id`, `name`, `display_name`, `description`, `created_at`, `updated_at`)
SELECT p.`permission_group`, p.`id`, 'materData_vpp_view_vppName', 'Xem Tên VPP',
    'Thấy mục Tên VPP trên menu Dữ Liệu Gốc và xem danh sách tên văn phòng phẩm dùng chung toàn công ty (Danh Mục VPP chọn tên từ đây).',
    @wms_now, @wms_now
FROM `permissions` p
WHERE p.`id` = @wms_parent_id
ON DUPLICATE KEY UPDATE
    `permission_group` = VALUES(`permission_group`),
    `parent_id`        = VALUES(`parent_id`),
    `display_name`     = VALUES(`display_name`),
    `description`      = VALUES(`description`),
    `updated_at`       = VALUES(`updated_at`);

SET @wms_new := (ROW_COUNT() = 1);
SET @wms_child_id := (SELECT `id` FROM `permissions` WHERE `name` = 'materData_vpp_view_vppName' LIMIT 1);

INSERT IGNORE INTO `role_permission` (`role_id`, `permission_id`)
SELECT rp.`role_id`, @wms_child_id FROM `role_permission` rp
WHERE rp.`permission_id` = @wms_parent_id AND @wms_new = 1;

INSERT IGNORE INTO `user_permission` (`user_id`, `permission_id`, `is_denied`)
SELECT up.`user_id`, @wms_child_id, up.`is_denied` FROM `user_permission` up
WHERE up.`permission_id` = @wms_parent_id AND @wms_new = 1;

-- 5.2 Thêm/Sửa/Khoá Tên VPP  (cha: materData_vpp_manage)
SET @wms_parent_id := (SELECT `id` FROM `permissions` WHERE `name` = 'materData_vpp_manage' LIMIT 1);

INSERT INTO `permissions` (`permission_group`, `parent_id`, `name`, `display_name`, `description`, `created_at`, `updated_at`)
SELECT p.`permission_group`, p.`id`, 'materData_vpp_manage_vppName', 'Thêm/Sửa/Khoá Tên VPP',
    CONCAT(
        'Thêm tên VPP mới, sửa tên, khoá / mở khoá ở màn Tên VPP.',
        CHAR(10),
        'Lưu ý: tên mới hoặc tên vừa sửa ở trạng thái Chờ duyệt, phải được duyệt mới chọn được ở Danh Mục VPP.'
    ),
    @wms_now, @wms_now
FROM `permissions` p
WHERE p.`id` = @wms_parent_id
ON DUPLICATE KEY UPDATE
    `permission_group` = VALUES(`permission_group`),
    `parent_id`        = VALUES(`parent_id`),
    `display_name`     = VALUES(`display_name`),
    `description`      = VALUES(`description`),
    `updated_at`       = VALUES(`updated_at`);

SET @wms_new := (ROW_COUNT() = 1);
SET @wms_child_id := (SELECT `id` FROM `permissions` WHERE `name` = 'materData_vpp_manage_vppName' LIMIT 1);

INSERT IGNORE INTO `role_permission` (`role_id`, `permission_id`)
SELECT rp.`role_id`, @wms_child_id FROM `role_permission` rp
WHERE rp.`permission_id` = @wms_parent_id AND @wms_new = 1;

INSERT IGNORE INTO `user_permission` (`user_id`, `permission_id`, `is_denied`)
SELECT up.`user_id`, @wms_child_id, up.`is_denied` FROM `user_permission` up
WHERE up.`permission_id` = @wms_parent_id AND @wms_new = 1;

-- 5.3 Duyệt/Từ Chối Tên VPP  (cha: materData_vpp_review)
SET @wms_parent_id := (SELECT `id` FROM `permissions` WHERE `name` = 'materData_vpp_review' LIMIT 1);

INSERT INTO `permissions` (`permission_group`, `parent_id`, `name`, `display_name`, `description`, `created_at`, `updated_at`)
SELECT p.`permission_group`, p.`id`, 'materData_vpp_review_vppName', 'Duyệt/Từ Chối Tên VPP',
    'Duyệt hoặc từ chối tên VPP đang Chờ duyệt ở màn Tên VPP. Tên chỉ chọn được ở Danh Mục VPP sau khi Đã duyệt.',
    @wms_now, @wms_now
FROM `permissions` p
WHERE p.`id` = @wms_parent_id
ON DUPLICATE KEY UPDATE
    `permission_group` = VALUES(`permission_group`),
    `parent_id`        = VALUES(`parent_id`),
    `display_name`     = VALUES(`display_name`),
    `description`      = VALUES(`description`),
    `updated_at`       = VALUES(`updated_at`);

SET @wms_new := (ROW_COUNT() = 1);
SET @wms_child_id := (SELECT `id` FROM `permissions` WHERE `name` = 'materData_vpp_review_vppName' LIMIT 1);

INSERT IGNORE INTO `role_permission` (`role_id`, `permission_id`)
SELECT rp.`role_id`, @wms_child_id FROM `role_permission` rp
WHERE rp.`permission_id` = @wms_parent_id AND @wms_new = 1;

INSERT IGNORE INTO `user_permission` (`user_id`, `permission_id`, `is_denied`)
SELECT up.`user_id`, @wms_child_id, up.`is_denied` FROM `user_permission` up
WHERE up.`permission_id` = @wms_parent_id AND @wms_new = 1;


-- -----------------------------------------------------------------------------
-- BƯỚC 6: Ghi nhận migration vào bảng migrations (để php artisan migrate không chạy lại)
-- -----------------------------------------------------------------------------
SET @wms_batch := (SELECT COALESCE(MAX(batch), 0) + 1 FROM `migrations`);

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_11_16_090000_create_vpp_names_and_split_from_material_names', @wms_batch
FROM DUAL
WHERE NOT EXISTS (
    SELECT 1 FROM `migrations` x
    WHERE x.migration = '2026_11_16_090000_create_vpp_names_and_split_from_material_names'
);


-- =============================================================================
-- HOÀN TÁC (tương đương down()) - CHỈ chạy khi cần gỡ bỏ, bỏ comment rồi chạy:
-- =============================================================================
-- ALTER TABLE `vpp_categories` CHANGE COLUMN `vpp_names_id` `material_names_id` BIGINT UNSIGNED NOT NULL;
-- ALTER TABLE `vpp_category_histories` CHANGE COLUMN `vpp_names_id` `material_names_id` BIGINT UNSIGNED NULL DEFAULT NULL;
-- -- Tên VPP trả về material_names (giữ id); trùng id hoặc trùng tên thì bỏ qua
-- CREATE TEMPORARY TABLE wms_tmp_vpp_back AS
--   SELECT v.* FROM `vpp_names` v
--   WHERE NOT EXISTS (SELECT 1 FROM `material_names` m WHERE m.`id` = v.`id` OR m.`name` = v.`name`);
-- INSERT INTO `material_names` SELECT * FROM wms_tmp_vpp_back;
-- UPDATE `datamaster_histories` h JOIN wms_tmp_vpp_back b ON b.`id` = h.`record_id`
--   SET h.`table_name` = 'material_names',
--       h.`snapshot` = REPLACE(h.`snapshot`, '"Tên VPP"', '"Tên vật tư"'),
--       h.`change_note` = REPLACE(h.`change_note`, 'Tên VPP', 'Tên vật tư')
--   WHERE h.`table_name` = 'vpp_names';
-- DELETE FROM `datamaster_histories` WHERE `table_name` = 'vpp_names';
-- DROP TABLE `vpp_names`;
-- DELETE up FROM `user_permission` up JOIN `permissions` p ON p.id = up.permission_id
--  WHERE p.name IN ('materData_vpp_review', 'materData_vpp_view_vppName', 'materData_vpp_manage_vppName', 'materData_vpp_review_vppName');
-- DELETE rp FROM `role_permission` rp JOIN `permissions` p ON p.id = rp.permission_id
--  WHERE p.name IN ('materData_vpp_review', 'materData_vpp_view_vppName', 'materData_vpp_manage_vppName', 'materData_vpp_review_vppName');
-- DELETE FROM `permissions`
--  WHERE `name` IN ('materData_vpp_review', 'materData_vpp_view_vppName', 'materData_vpp_manage_vppName', 'materData_vpp_review_vppName');
-- DELETE FROM `migrations` WHERE `migration` = '2026_11_16_090000_create_vpp_names_and_split_from_material_names';
