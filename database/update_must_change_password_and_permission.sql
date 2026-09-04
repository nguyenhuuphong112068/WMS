-- =============================================================================
-- CẬP NHẬT CSDL: THÊM CỘT must_change_password VÀ QUYỀN user_resetPassword
-- Nguồn từ 2 migration:
--   1. 2026_09_04_160000_add_must_change_password_to_user_management_table.php
--   2. 2026_09_04_160100_seed_user_reset_password_permission.php
-- =============================================================================

-- BƯỚC 1: Thêm cột must_change_password vào bảng user_management (sau cột changePWdate)
-- (Nếu chạy trên MySQL 8.0.29+ / MariaDB 10.x có thể dùng: ADD COLUMN IF NOT EXISTS)
DELIMITER $$
DROP PROCEDURE IF EXISTS AddMustChangePasswordColumn $$
CREATE PROCEDURE AddMustChangePasswordColumn()
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.COLUMNS 
        WHERE TABLE_SCHEMA = DATABASE() 
          AND TABLE_NAME = 'user_management' 
          AND COLUMN_NAME = 'must_change_password'
    ) THEN
        ALTER TABLE `user_management` 
        ADD COLUMN `must_change_password` TINYINT(1) NOT NULL DEFAULT 0 AFTER `changePWdate`;
    END IF;
END $$
DELIMITER ;

CALL AddMustChangePasswordColumn();
DROP PROCEDURE IF EXISTS AddMustChangePasswordColumn;


-- BƯỚC 2: Thêm quyền 'user_resetPassword' vào bảng permissions (nhóm 8 - Quản Trị)
INSERT INTO `permissions` (`permission_group`, `name`, `display_name`, `description`, `created_at`, `updated_at`)
VALUES (
    8, 
    'user_resetPassword', 
    'Reset Mật Khẩu User', 
    'Đặt lại mật khẩu tạm cho user, buộc user đổi ở lần đăng nhập kế tiếp', 
    NOW(), 
    NOW()
)
ON DUPLICATE KEY UPDATE 
    `permission_group` = VALUES(`permission_group`),
    `display_name`     = VALUES(`display_name`),
    `description`      = VALUES(`description`),
    `updated_at`       = NOW();


-- BƯỚC 3: Gán quyền 'user_resetPassword' cho tất cả role có tên 'Admin' trong role_permission
INSERT IGNORE INTO `role_permission` (`role_id`, `permission_id`)
SELECT r.`id`, p.`id`
FROM `roles` r
CROSS JOIN `permissions` p
WHERE r.`name` = 'Admin'
  AND p.`name` = 'user_resetPassword';


-- BƯỚC 4: Ghi nhận 2 migration vào bảng migrations (nếu chưa có) để tránh lỗi khi migrate sau này
INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_09_04_160000_add_must_change_password_to_user_management_table', 
       COALESCE((SELECT MAX(`batch`) FROM `migrations` m), 0) + 1
WHERE NOT EXISTS (
    SELECT 1 FROM `migrations` 
    WHERE `migration` = '2026_09_04_160000_add_must_change_password_to_user_management_table'
);

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_09_04_160100_seed_user_reset_password_permission', 
       COALESCE((SELECT MAX(`batch`) FROM `migrations` m), 0) + 1
WHERE NOT EXISTS (
    SELECT 1 FROM `migrations` 
    WHERE `migration` = '2026_09_04_160100_seed_user_reset_password_permission'
);
