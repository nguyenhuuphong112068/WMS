-- =============================================================================
-- CẬP NHẬT CSDL NGÀY 16/11/2026  (MySQL 5.7+ / MariaDB 10.2+)
-- Tương đương migration: 2026_11_16_100000_drop_material_prep_print_from_permission_description.php
--
-- BỎ CHỨC NĂNG "IN PHIẾU SOẠN HÀNG" Ở TAB CHỜ CẤP PHÁT (SỬ DỤNG VẬT TƯ)
--   - Gỡ câu "in phiếu soạn hàng" khỏi diễn giải quyền export_material_issue:
--     'Sổ cấp phát; in phiếu soạn hàng. Gồm cả'  ->  'Sổ cấp phát. Gồm cả'
--
-- Chạy trên TỪNG CSDL công ty (vd. wms và wms_nm1): chọn đúng database trước khi chạy.
-- Script chạy lại nhiều lần vẫn an toàn (diễn giải đã đổi thì bỏ qua).
-- NÊN BACKUP CSDL TRƯỚC KHI CHẠY.
-- =============================================================================

SET NAMES utf8mb4;


-- -----------------------------------------------------------------------------
-- BƯỚC 1: Sửa diễn giải quyền export_material_issue
-- -----------------------------------------------------------------------------
UPDATE `permissions`
SET `description` = REPLACE(`description`, 'Sổ cấp phát; in phiếu soạn hàng. Gồm cả', 'Sổ cấp phát. Gồm cả')
WHERE `name` = 'export_material_issue'
  AND `description` LIKE BINARY '%Sổ cấp phát; in phiếu soạn hàng. Gồm cả%';


-- -----------------------------------------------------------------------------
-- BƯỚC 2: Ghi nhận migration vào bảng migrations (để php artisan migrate không chạy lại)
-- -----------------------------------------------------------------------------
SET @wms_batch := (SELECT COALESCE(MAX(batch), 0) + 1 FROM `migrations`);

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_11_16_100000_drop_material_prep_print_from_permission_description', @wms_batch
FROM DUAL
WHERE NOT EXISTS (
    SELECT 1 FROM `migrations` x
    WHERE x.migration = '2026_11_16_100000_drop_material_prep_print_from_permission_description'
);


-- =============================================================================
-- HOÀN TÁC (tương đương down()) - CHỈ chạy khi cần gỡ bỏ, bỏ comment rồi chạy:
-- =============================================================================
-- UPDATE `permissions`
-- SET `description` = REPLACE(`description`, 'Sổ cấp phát. Gồm cả', 'Sổ cấp phát; in phiếu soạn hàng. Gồm cả')
-- WHERE `name` = 'export_material_issue'
--   AND `description` LIKE BINARY '%Sổ cấp phát. Gồm cả%';
-- DELETE FROM `migrations` WHERE `migration` = '2026_11_16_100000_drop_material_prep_print_from_permission_description';
