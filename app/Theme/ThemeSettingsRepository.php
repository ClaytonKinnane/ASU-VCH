<?php

declare(strict_types=1);

final class ThemeSettingsRepository
{
    private const ACTIVE_THEME_KEY = 'ui.active_theme';

    public function __construct(private PDO $pdo)
    {
    }

    public function activeTheme(): ?string
    {
        $stmt = $this->pdo->prepare('SELECT setting_value FROM system_settings WHERE setting_key = :setting_key LIMIT 1');
        $stmt->execute(['setting_key' => self::ACTIVE_THEME_KEY]);
        $value = $stmt->fetchColumn();
        return is_string($value) && $value !== '' ? $value : null;
    }

    /** @return array{setting_value:string,updated_at:string,updated_by:?int,actor_name:?string,actor_username:?string}|null */
    public function activeThemeAudit(): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT s.setting_value, s.updated_at, s.updated_by, u.display_name AS actor_name, u.username AS actor_username '
            . 'FROM system_settings s LEFT JOIN users u ON u.id = s.updated_by '
            . 'WHERE s.setting_key = :setting_key LIMIT 1'
        );
        $stmt->execute(['setting_key' => self::ACTIVE_THEME_KEY]);
        $row = $stmt->fetch();
        return is_array($row) ? $row : null;
    }

    public function lockActiveTheme(): void
    {
        $stmt = $this->pdo->prepare('SELECT id FROM system_settings WHERE setting_key = :setting_key FOR UPDATE');
        $stmt->execute(['setting_key' => self::ACTIVE_THEME_KEY]);
        $stmt->fetchColumn();
    }

    public function saveActiveTheme(string $slug, int $actorId, DateTimeImmutable $now): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO system_settings (setting_key, setting_value, created_at, updated_at, updated_by) '
            . 'VALUES (:setting_key, :setting_value, :created_at, :updated_at, :updated_by) '
            . 'ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = VALUES(updated_at), updated_by = VALUES(updated_by)'
        );
        $formatted = $now->format('Y-m-d H:i:s');
        $stmt->execute([
            'setting_key' => self::ACTIVE_THEME_KEY,
            'setting_value' => $slug,
            'created_at' => $formatted,
            'updated_at' => $formatted,
            'updated_by' => $actorId,
        ]);
    }
}
