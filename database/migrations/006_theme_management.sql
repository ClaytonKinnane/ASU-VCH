ALTER TABLE system_settings
    ADD COLUMN updated_by BIGINT UNSIGNED NULL AFTER updated_at,
    ADD CONSTRAINT fk_system_settings_updated_by
        FOREIGN KEY (updated_by) REFERENCES users(id)
        ON UPDATE RESTRICT ON DELETE SET NULL;

INSERT INTO system_settings (setting_key, setting_value, created_at, updated_at, updated_by)
VALUES ('ui.active_theme', 'asu-blue', NOW(), NOW(), NULL)
ON DUPLICATE KEY UPDATE setting_key = VALUES(setting_key);
