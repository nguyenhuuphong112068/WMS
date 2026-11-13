-- =============================================================================
-- CẬP NHẬT CSDL NGÀY 13/11/2026  (MySQL 5.7+ / MariaDB 10.2+)
-- Gộp 4 migration:
--   1. 2026_11_13_090000_move_material_price_to_department_categories.php
--   2. 2026_11_13_100000_renumber_location_horizontal_positions.php
--   3. 2026_11_13_110000_location_code_dot_separator.php
--   4. 2026_11_13_120000_add_timing_to_material_calibrations.php
--
-- Chạy trên TỪNG CSDL công ty (vd. wms và wms_nm1): chọn đúng database trước khi chạy.
-- Script chạy lại nhiều lần vẫn an toàn (có kiểm tra tồn tại / dữ liệu đã đổi thì bỏ qua).
-- NÊN BACKUP CSDL TRƯỚC KHI CHẠY.
-- =============================================================================


-- -----------------------------------------------------------------------------
-- BƯỚC 1 (migration 1): "Theo Giá Trị" chuyển từ danh mục vật tư công ty xuống
-- danh mục vật tư của phòng (material_department_categories.price_level)
-- -----------------------------------------------------------------------------

-- 1a. Thêm cột price_level (sau classification_id)
DELIMITER $$
DROP PROCEDURE IF EXISTS wms_20261113_add_price_level $$
CREATE PROCEDURE wms_20261113_add_price_level()
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'material_department_categories'
          AND COLUMN_NAME = 'price_level'
    ) THEN
        ALTER TABLE `material_department_categories`
            ADD COLUMN `price_level` VARCHAR(10) NULL DEFAULT NULL AFTER `classification_id`;
    END IF;
END $$
DELIMITER ;

CALL wms_20261113_add_price_level();
DROP PROCEDURE IF EXISTS wms_20261113_add_price_level;

-- 1b. Điền giá trị cũ ở danh mục công ty (classification JSON khoá "price") xuống mọi dòng của phòng
UPDATE `material_department_categories` d
JOIN `material_categories` c ON c.id = d.category_id
SET d.price_level = JSON_UNQUOTE(JSON_EXTRACT(c.classification, '$.price'))
WHERE d.price_level IS NULL
  AND c.classification LIKE '%"price"%'
  AND JSON_VALID(c.classification)
  AND JSON_UNQUOTE(JSON_EXTRACT(c.classification, '$.price')) IN ('low', 'high');

-- 1c. Gỡ khoá "price" khỏi JSON công ty (JSON rỗng -> NULL)
UPDATE `material_categories`
SET classification = CASE
        WHEN JSON_LENGTH(JSON_REMOVE(classification, '$.price')) = 0 THEN NULL
        ELSE JSON_REMOVE(classification, '$.price')
    END
WHERE classification LIKE '%"price"%'
  AND JSON_VALID(classification)
  AND JSON_CONTAINS_PATH(classification, 'one', '$.price');

-- 1d. Phân loại riêng của phòng (department_classification) - phòng đã có giữ nguyên
--     QC / KTCL / PTPT: 3 nhóm
INSERT IGNORE INTO `department_classification`
    (`name`, `department_id`, `status_id`, `created_by`, `created_at`, `updated_at`)
SELECT g.name, d.id, 1, 'Hệ thống', NOW(), NOW()
FROM `deparments` d
JOIN (
    SELECT 'Spare part của thiết bị và dụng cụ thủy tinh' AS name
    UNION ALL SELECT 'Vật tư tiêu hao dùng để phân tích mẫu'
    UNION ALL SELECT 'Khác (văn phòng phẩm, dụng cụ vệ sinh...)'
) g
WHERE UPPER(d.shortName) LIKE 'QC%'
   OR UPPER(d.shortName) LIKE 'KTCL%'
   OR UPPER(d.shortName) = 'PTPT';

--     Phân xưởng (PX...): 2 nhóm
INSERT IGNORE INTO `department_classification`
    (`name`, `department_id`, `status_id`, `created_by`, `created_at`, `updated_at`)
SELECT g.name, d.id, 1, 'Hệ thống', NOW(), NOW()
FROM `deparments` d
JOIN (
    SELECT 'Có tiếp xúc' AS name
    UNION ALL SELECT 'Không tiếp xúc'
) g
WHERE UPPER(d.shortName) LIKE 'PX%';


-- -----------------------------------------------------------------------------
-- BƯỚC 2 (migration 2 + 3): Mã vị trí định khu - đoạn cuối sau dấu "/" cuối cùng
--   <chữ><dãy sâu>   (vd. .../E2)   -> <số 2 chữ số>.<dãy sâu> (.../05.2)   A=1, Z=26, AA=27...
--   <số>-<dãy sâu>   (vd. .../05-2) -> <số>.<dãy sâu>           (.../05.2)
-- -----------------------------------------------------------------------------
DELIMITER $$
DROP FUNCTION IF EXISTS wms_20261113_location_code $$
CREATE FUNCTION wms_20261113_location_code(c VARCHAR(1000))
RETURNS VARCHAR(1000)
DETERMINISTIC NO SQL
BEGIN
    DECLARE seg VARCHAR(1000);
    DECLARE prefix VARCHAR(1000);
    DECLARE rest VARCHAR(1000);
    DECLARE len INT;
    DECLARE i INT DEFAULT 1;
    DECLARE n INT DEFAULT 0;

    IF c IS NULL OR LOCATE('/', c) = 0 THEN
        RETURN c;
    END IF;

    SET seg = SUBSTRING_INDEX(c, '/', -1);
    SET prefix = LEFT(c, CHAR_LENGTH(c) - CHAR_LENGTH(seg));
    SET len = CHAR_LENGTH(seg);

    -- Chữ in hoa A-Z ở đầu đoạn cuối
    WHILE i <= len AND ASCII(SUBSTRING(seg, i, 1)) BETWEEN 65 AND 90 DO
        SET n = n * 26 + ASCII(SUBSTRING(seg, i, 1)) - 64;
        SET i = i + 1;
    END WHILE;

    IF i > 1 THEN
        SET rest = SUBSTRING(seg, i);
        IF rest REGEXP '^[0-9]+$' THEN
            RETURN CONCAT(prefix, IF(n < 10, CONCAT('0', n), CAST(n AS CHAR)), '.', rest);
        END IF;
        RETURN c;
    END IF;

    IF seg REGEXP '^[0-9]{2,}-[0-9]+$' THEN
        RETURN CONCAT(prefix, REPLACE(seg, '-', '.'));
    END IF;

    RETURN c;
END $$
DELIMITER ;

UPDATE `locations`
SET code = wms_20261113_location_code(code)
WHERE code LIKE '%/%'
  AND BINARY wms_20261113_location_code(code) <> BINARY code;

DROP FUNCTION IF EXISTS wms_20261113_location_code;


-- -----------------------------------------------------------------------------
-- BƯỚC 3 (migration 4): Hiệu chuẩn vật tư - thêm timing (before/after) + export_id
-- -----------------------------------------------------------------------------
DELIMITER $$
DROP PROCEDURE IF EXISTS wms_20261113_calibration_timing $$
CREATE PROCEDURE wms_20261113_calibration_timing()
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'material_calibrations'
          AND COLUMN_NAME = 'timing'
    ) THEN
        ALTER TABLE `material_calibrations`
            ADD COLUMN `timing` VARCHAR(10) NOT NULL DEFAULT 'before' AFTER `code`;
    END IF;

    IF NOT EXISTS (
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'material_calibrations'
          AND COLUMN_NAME = 'export_id'
    ) THEN
        ALTER TABLE `material_calibrations`
            ADD COLUMN `export_id` BIGINT UNSIGNED NULL DEFAULT NULL AFTER `import_id`;
    END IF;

    IF NOT EXISTS (
        SELECT 1 FROM information_schema.STATISTICS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'material_calibrations'
          AND INDEX_NAME = 'material_calibrations_timing_index'
    ) THEN
        ALTER TABLE `material_calibrations`
            ADD INDEX `material_calibrations_timing_index` (`timing`);
    END IF;
END $$
DELIMITER ;

CALL wms_20261113_calibration_timing();
DROP PROCEDURE IF EXISTS wms_20261113_calibration_timing;


-- -----------------------------------------------------------------------------
-- BƯỚC 4: Ghi nhận 4 migration vào bảng migrations (để php artisan migrate không chạy lại)
-- -----------------------------------------------------------------------------
SET @wms_batch := (SELECT COALESCE(MAX(batch), 0) + 1 FROM `migrations`);

INSERT INTO `migrations` (`migration`, `batch`)
SELECT m.migration, @wms_batch
FROM (
    SELECT '2026_11_13_090000_move_material_price_to_department_categories' AS migration
    UNION ALL SELECT '2026_11_13_100000_renumber_location_horizontal_positions'
    UNION ALL SELECT '2026_11_13_110000_location_code_dot_separator'
    UNION ALL SELECT '2026_11_13_120000_add_timing_to_material_calibrations'
) m
WHERE NOT EXISTS (SELECT 1 FROM `migrations` x WHERE x.migration = m.migration);
