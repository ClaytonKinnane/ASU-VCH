<?php

declare(strict_types=1);

/** @return array<string,mixed> */
function military_positions_require_action(string $permission): array
{
    return require_permission($permission);
}

/** @param callable():string $operation */
function military_positions_handle_action(callable $operation): never
{
    require_csrf();
    $fallback = military_positions_safe_return_path($_POST['return_to'] ?? null);
    try {
        $target = military_positions_safe_return_path($operation(), $fallback);
        flash('success', 'Операция со справочником воинских должностей выполнена.');
        redirect($target);
    } catch (DomainException|OutOfBoundsException $exception) {
        flash('military_positions_error', $exception->getMessage());
        redirect($fallback);
    } catch (Throwable $exception) {
        error_log('Military positions action failed: ' . $exception->getMessage());
        flash('military_positions_error', 'Операция не выполнена из-за серверной ошибки.');
        redirect($fallback);
    }
}

function military_positions_safe_return_path(mixed $value, string $fallback = '/admin/directories/military-positions.php'): string
{
    if (!is_string($value) || $value === '' || !str_starts_with($value, '/')
        || str_starts_with($value, '//') || str_contains($value, "\r") || str_contains($value, "\n")) {
        return $fallback;
    }
    $path = parse_url($value, PHP_URL_PATH);
    if (!is_string($path)
        || ($path !== '/admin/directories/military-positions.php'
            && !str_starts_with($path, '/admin/directories/military-positions/'))) {
        return $fallback;
    }
    return $value;
}

function military_positions_positive_int(mixed $value, string $message = 'Некорректный идентификатор.'): int
{
    if ((!is_int($value) && !is_string($value)) || preg_match('/\A[1-9][0-9]*\z/D', (string) $value) !== 1) {
        throw new DomainException($message);
    }
    $normalized = (string) $value;
    $maximum = (string) PHP_INT_MAX;
    if (strlen($normalized) > strlen($maximum)
        || (strlen($normalized) === strlen($maximum) && strcmp($normalized, $maximum) > 0)) {
        throw new DomainException($message);
    }
    return (int) $normalized;
}

function military_positions_date(?string $value): string
{
    if ($value === null || $value === '') {
        return '—';
    }
    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
    return $date instanceof DateTimeImmutable ? $date->format('d.m.Y') : $value;
}

function military_positions_datetime(?string $value): string
{
    if ($value === null || $value === '') {
        return '—';
    }
    try {
        return (new DateTimeImmutable($value))->format('d.m.Y H:i:s');
    } catch (Throwable) {
        return $value;
    }
}

function military_positions_status_label(string $status): string
{
    return match ($status) {
        'draft' => 'Черновик',
        'published' => 'Опубликована',
        'superseded' => 'Заменена',
        'cancelled' => 'Отменена',
        'active' => 'Действующая',
        'archived' => 'Архивная',
        default => 'Неизвестно',
    };
}

function military_positions_source_label(string $sourceType): string
{
    return match ($sourceType) {
        'official' => 'Официальный источник',
        'local' => 'Локальная синтетическая запись',
        'imported' => 'Импортированная запись',
        default => 'Не указан',
    };
}

function military_positions_event_label(string $eventType): string
{
    return match ($eventType) {
        'catalog.version.created' => 'Создана версия справочника',
        'catalog.version.published' => 'Опубликована версия справочника',
        'catalog.version.cancelled' => 'Отменена версия справочника',
        'position.created' => 'Создана воинская должность',
        'position.updated' => 'Изменена воинская должность',
        'position.archived' => 'Воинская должность архивирована',
        'position.restored' => 'Воинская должность восстановлена',
        default => 'Изменение справочника',
    };
}

function military_positions_field_label(string $field): string
{
    return match ($field) {
        'name' => 'Наименование',
        'full_name' => 'Полное наименование',
        'short_name' => 'Краткое наименование',
        'is_combined' => 'Составная должность',
        'source_type' => 'Источник',
        'source_reference' => 'Реквизит источника',
        'note' => 'Примечание',
        'status' => 'Состояние',
        'sort_order' => 'Порядок',
        'revision' => 'Редакция',
        'version_number' => 'Номер версии',
        'entry_count' => 'Количество должностей',
        'catalog_kind' => 'Вид версии',
        default => 'Значение',
    };
}

/** @return array<string,mixed> */
function military_positions_history_state(mixed $json): array
{
    if (!is_string($json) || $json === '') {
        return [];
    }
    try {
        $value = json_decode($json, true, 32, JSON_THROW_ON_ERROR);
        return is_array($value) ? $value : [];
    } catch (JsonException) {
        return [];
    }
}

function military_positions_history_value(string $field, mixed $value): string
{
    if ($value === null || $value === '') {
        return 'Не указано';
    }
    if ($field === 'is_combined') {
        return (int) $value === 1 ? 'Да' : 'Нет';
    }
    if ($field === 'source_type') {
        return military_positions_source_label((string) $value);
    }
    if ($field === 'status') {
        return military_positions_status_label((string) $value);
    }
    if ($field === 'catalog_kind') {
        return (string) $value === 'canonical' ? 'Каноническая' : 'Историческая';
    }
    if (is_bool($value)) {
        return $value ? 'Да' : 'Нет';
    }
    if (is_scalar($value)) {
        return (string) $value;
    }
    return 'Изменено';
}
