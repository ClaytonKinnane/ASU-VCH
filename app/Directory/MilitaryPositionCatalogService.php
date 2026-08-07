<?php

declare(strict_types=1);

final class MilitaryPositionCatalogService
{
    public function __construct(
        private readonly PDO $pdo,
        private readonly MilitaryPositionCatalogRepository $repository
    ) {
    }

    /** @param array<string,mixed> $input */
    public function createDraft(array $input, int $actorId): int
    {
        $label = $this->requiredString($input['version_label'] ?? null, 255, 'Укажите название новой версии.');
        $effectiveFrom = $this->date($input['effective_from'] ?? null, 'Укажите корректную дату начала действия.');
        $reason = $this->requiredString($input['change_reason'] ?? null, 1000, 'Укажите основание создания версии.');
        $expectedRevision = $this->positiveInt($input['expected_catalog_revision'] ?? null, 'Некорректная редакция исходной версии.');

        return $this->transaction(function () use ($label, $effectiveFrom, $reason, $expectedRevision, $actorId): int {
            $current = $this->lockPublishedVersion();
            if ((int) $current['revision'] !== $expectedRevision) {
                throw new DomainException('Исходная версия была изменена. Обновите страницу.');
            }
            if ($effectiveFrom < (string) $current['valid_from']) {
                throw new DomainException('Дата начала новой версии не может быть раньше текущей опубликованной версии.');
            }
            if ($this->draftExists()) {
                throw new DomainException('Черновая версия справочника уже существует.');
            }
            $copySource = $current;
            if (($current['catalog_kind'] ?? null) !== 'canonical') {
                $copySource = $this->latestCancelledCanonicalVersion();
            }

            $versionNumber = (int) $this->pdo->query(
                'SELECT COALESCE(MAX(version_number),0)+1 FROM military_position_catalog_versions'
            )->fetchColumn();
            $code = sprintf('asu-canonical-military-positions-v%d-%s', $versionNumber, bin2hex(random_bytes(4)));
            $stmt = $this->pdo->prepare(
                'INSERT INTO military_position_catalog_versions '
                . '(version_number,code,name,version_label,coverage_note,catalog_kind,status,valid_from,valid_to,'
                . 'revision,change_reason,verified_at,rank_catalog_version_id,organizational_element_catalog_version_id,'
                . 'created_by,created_at,updated_by,updated_at) '
                . "VALUES (:number,:code,:name,:label,'Управляемая каноническая версия справочника.','canonical','draft',"
                . ':effective_from,NULL,1,:reason,CURRENT_DATE(),:rank_id,:org_id,:actor,NOW(6),:actor,NOW(6))'
            );
            $stmt->execute([
                'number' => $versionNumber,
                'code' => $code,
                'name' => $label,
                'label' => $label,
                'effective_from' => $effectiveFrom,
                'reason' => $reason,
                'rank_id' => (int) $current['rank_catalog_version_id'],
                'org_id' => (int) $current['organizational_element_catalog_version_id'],
                'actor' => $actorId,
            ]);
            $versionId = (int) $this->pdo->lastInsertId();

            $copy = $this->pdo->prepare(
                'INSERT INTO military_position_types '
                . '(catalog_version_id,stable_key,code,name,normalized_name,full_name,short_name,is_combined,'
                . 'source_type,source_reference,note,description,applicability_note,status,sort_order,revision,'
                . 'created_at,created_by,updated_by,updated_at) '
                . 'SELECT :target_id,stable_key,code,name,normalized_name,full_name,short_name,is_combined,'
                . 'source_type,source_reference,note,description,applicability_note,status,sort_order,1,'
                . 'NOW(6),:actor,:actor,NOW(6) FROM military_position_types '
                . 'WHERE catalog_version_id=:source_id ORDER BY sort_order,id'
            );
            $copy->execute(['target_id' => $versionId, 'source_id' => (int) $copySource['id'], 'actor' => $actorId]);

            $this->appendEvent(
                $versionId,
                $actorId,
                'catalog.version.created',
                'catalog_version',
                $versionId,
                null,
                ['status' => 'draft', 'version_number' => $versionNumber, 'entry_count' => $copy->rowCount()],
                $reason
            );
            return $versionId;
        });
    }

    /** @param array<string,mixed> $input */
    public function createEntry(int $versionId, array $input, int $expectedCatalogRevision, int $actorId): int
    {
        $data = $this->entryInput($input);
        $reason = $this->requiredString($input['change_reason'] ?? null, 1000, 'Укажите основание создания должности.');

        return $this->transaction(function () use ($versionId, $data, $reason, $expectedCatalogRevision, $actorId): int {
            $this->assertDraft($this->lockVersion($versionId), $expectedCatalogRevision);
            $code = 'canonical-' . bin2hex(random_bytes(8));
            $stableKey = 'mp-' . bin2hex(random_bytes(16));
            try {
                $stmt = $this->pdo->prepare(
                    'INSERT INTO military_position_types '
                    . '(catalog_version_id,stable_key,code,name,normalized_name,full_name,short_name,is_combined,'
                    . 'source_type,source_reference,note,description,applicability_note,status,sort_order,revision,'
                    . 'created_at,created_by,updated_by,updated_at) '
                    . "VALUES (:version_id,:stable_key,:code,:name,:normalized_name,:full_name,:short_name,:is_combined,"
                    . ":source_type,:source_reference,:note,:description,'Управляемая каноническая запись.','active',"
                    . ':sort_order,1,NOW(6),:actor,:actor,NOW(6))'
                );
                $stmt->execute([
                    'version_id' => $versionId,
                    'stable_key' => $stableKey,
                    'code' => $code,
                    'name' => $data['name'],
                    'normalized_name' => $data['normalized_name'],
                    'full_name' => $data['full_name'],
                    'short_name' => $data['short_name'],
                    'is_combined' => $data['is_combined'],
                    'source_type' => $data['source_type'],
                    'source_reference' => $data['source_reference'],
                    'note' => $data['note'],
                    'description' => 'Каноническое наименование: ' . $data['name'],
                    'sort_order' => $data['sort_order'],
                    'actor' => $actorId,
                ]);
            } catch (PDOException $exception) {
                $this->rethrowEntryConstraint($exception);
            }
            $entryId = (int) $this->pdo->lastInsertId();
            $this->incrementCatalogRevision($versionId, $expectedCatalogRevision, $actorId);
            $after = $data + ['id' => $entryId, 'status' => 'active', 'revision' => 1];
            $this->appendEvent($versionId, $actorId, 'position.created', 'position', $entryId, null, $after, $reason);
            return $entryId;
        });
    }

    /** @param array<string,mixed> $input */
    public function updateEntry(
        int $versionId,
        int $entryId,
        array $input,
        int $expectedCatalogRevision,
        int $expectedEntryRevision,
        int $actorId
    ): void {
        $data = $this->entryInput($input);
        $reason = $this->requiredString($input['change_reason'] ?? null, 1000, 'Укажите основание изменения должности.');

        $this->transaction(function () use ($versionId, $entryId, $data, $reason, $expectedCatalogRevision, $expectedEntryRevision, $actorId): void {
            $this->assertDraft($this->lockVersion($versionId), $expectedCatalogRevision);
            $entry = $this->lockEntry($versionId, $entryId);
            if ((int) $entry['revision'] !== $expectedEntryRevision) {
                throw new DomainException('Запись должности была изменена. Обновите страницу.');
            }
            try {
                $stmt = $this->pdo->prepare(
                    'UPDATE military_position_types SET name=:name,normalized_name=:normalized_name,'
                    . 'full_name=:full_name,short_name=:short_name,is_combined=:is_combined,source_type=:source_type,'
                    . 'source_reference=:source_reference,note=:note,description=:description,sort_order=:sort_order,'
                    . 'revision=revision+1,updated_by=:actor,updated_at=NOW(6) '
                    . 'WHERE id=:entry_id AND catalog_version_id=:version_id AND revision=:entry_revision'
                );
                $stmt->execute([
                    'name' => $data['name'],
                    'normalized_name' => $data['normalized_name'],
                    'full_name' => $data['full_name'],
                    'short_name' => $data['short_name'],
                    'is_combined' => $data['is_combined'],
                    'source_type' => $data['source_type'],
                    'source_reference' => $data['source_reference'],
                    'note' => $data['note'],
                    'description' => 'Каноническое наименование: ' . $data['name'],
                    'sort_order' => $data['sort_order'],
                    'actor' => $actorId,
                    'entry_id' => $entryId,
                    'version_id' => $versionId,
                    'entry_revision' => $expectedEntryRevision,
                ]);
            } catch (PDOException $exception) {
                $this->rethrowEntryConstraint($exception);
            }
            if ($stmt->rowCount() !== 1) {
                throw new DomainException('Запись должности была изменена. Обновите страницу.');
            }
            $this->incrementCatalogRevision($versionId, $expectedCatalogRevision, $actorId);
            $this->appendEvent(
                $versionId,
                $actorId,
                'position.updated',
                'position',
                $entryId,
                $this->entryState($entry),
                $data + ['status' => (string) $entry['status'], 'revision' => $expectedEntryRevision + 1],
                $reason
            );
        });
    }

    public function archiveEntry(
        int $versionId,
        int $entryId,
        int $expectedCatalogRevision,
        int $expectedEntryRevision,
        string $reason,
        int $actorId
    ): void {
        $this->setEntryStatus($versionId, $entryId, 'active', 'archived', 'position.archived', $expectedCatalogRevision, $expectedEntryRevision, $reason, $actorId);
    }

    public function restoreEntry(
        int $versionId,
        int $entryId,
        int $expectedCatalogRevision,
        int $expectedEntryRevision,
        string $reason,
        int $actorId
    ): void {
        $this->setEntryStatus($versionId, $entryId, 'archived', 'active', 'position.restored', $expectedCatalogRevision, $expectedEntryRevision, $reason, $actorId);
    }

    public function publish(int $versionId, int $expectedRevision, string $reason, int $actorId): void
    {
        $reason = $this->requiredString($reason, 1000, 'Укажите основание публикации версии.');
        $this->transaction(function () use ($versionId, $expectedRevision, $reason, $actorId): void {
            $draft = $this->lockVersion($versionId);
            $this->assertDraft($draft, $expectedRevision);
            $this->assertPublishable($versionId);
            $current = $this->lockPublishedVersion();
            if ((int) $current['id'] === $versionId) {
                throw new DomainException('Опубликованная версия не может быть черновиком.');
            }
            $supersede = $this->pdo->prepare(
                "UPDATE military_position_catalog_versions SET status='superseded',valid_to=:valid_to,"
                . 'revision=revision+1,updated_by=:actor,updated_at=NOW(6),superseded_by=:actor,superseded_at=NOW(6) '
                . "WHERE id=:id AND status='published' AND revision=:revision"
            );
            $supersede->execute([
                'valid_to' => (string) $draft['valid_from'],
                'actor' => $actorId,
                'id' => (int) $current['id'],
                'revision' => (int) $current['revision'],
            ]);
            if ($supersede->rowCount() !== 1) {
                throw new DomainException('Текущая опубликованная версия была изменена. Обновите страницу.');
            }
            $publish = $this->pdo->prepare(
                "UPDATE military_position_catalog_versions SET status='published',revision=revision+1,"
                . 'updated_by=:actor,updated_at=NOW(6),published_by=:actor,published_at=NOW(6) '
                . "WHERE id=:id AND status='draft' AND revision=:revision"
            );
            $publish->execute(['actor' => $actorId, 'id' => $versionId, 'revision' => $expectedRevision]);
            if ($publish->rowCount() !== 1) {
                throw new DomainException('Черновая версия была изменена. Обновите страницу.');
            }
            $this->appendEvent(
                $versionId,
                $actorId,
                'catalog.version.published',
                'catalog_version',
                $versionId,
                ['status' => 'draft', 'revision' => $expectedRevision],
                ['status' => 'published', 'revision' => $expectedRevision + 1],
                $reason
            );
        });
    }

    public function cancel(int $versionId, int $expectedRevision, string $reason, int $actorId): void
    {
        $reason = $this->requiredString($reason, 1000, 'Укажите причину отмены черновика.');
        $this->transaction(function () use ($versionId, $expectedRevision, $reason, $actorId): void {
            $draft = $this->lockVersion($versionId);
            $this->assertDraft($draft, $expectedRevision);
            $stmt = $this->pdo->prepare(
                "UPDATE military_position_catalog_versions SET status='cancelled',revision=revision+1,"
                . 'updated_by=:actor,updated_at=NOW(6),cancelled_by=:actor,cancelled_at=NOW(6),cancellation_reason=:reason '
                . "WHERE id=:id AND status='draft' AND revision=:revision"
            );
            $stmt->execute(['actor' => $actorId, 'reason' => $reason, 'id' => $versionId, 'revision' => $expectedRevision]);
            if ($stmt->rowCount() !== 1) {
                throw new DomainException('Черновая версия была изменена. Обновите страницу.');
            }
            $this->appendEvent(
                $versionId,
                $actorId,
                'catalog.version.cancelled',
                'catalog_version',
                $versionId,
                ['status' => 'draft', 'revision' => $expectedRevision],
                ['status' => 'cancelled', 'revision' => $expectedRevision + 1],
                $reason
            );
        });
    }

    private function setEntryStatus(
        int $versionId,
        int $entryId,
        string $fromStatus,
        string $toStatus,
        string $eventType,
        int $expectedCatalogRevision,
        int $expectedEntryRevision,
        string $reason,
        int $actorId
    ): void {
        $reason = $this->requiredString($reason, 1000, 'Укажите основание изменения состояния.');
        $this->transaction(function () use ($versionId, $entryId, $fromStatus, $toStatus, $eventType, $expectedCatalogRevision, $expectedEntryRevision, $reason, $actorId): void {
            $this->assertDraft($this->lockVersion($versionId), $expectedCatalogRevision);
            $entry = $this->lockEntry($versionId, $entryId);
            if ((int) $entry['revision'] !== $expectedEntryRevision || ($entry['status'] ?? null) !== $fromStatus) {
                throw new DomainException('Состояние записи изменилось. Обновите страницу.');
            }
            $stmt = $this->pdo->prepare(
                'UPDATE military_position_types SET status=:status,revision=revision+1,updated_by=:actor,updated_at=NOW(6) '
                . 'WHERE id=:entry_id AND catalog_version_id=:version_id AND status=:from_status AND revision=:revision'
            );
            $stmt->execute([
                'status' => $toStatus,
                'actor' => $actorId,
                'entry_id' => $entryId,
                'version_id' => $versionId,
                'from_status' => $fromStatus,
                'revision' => $expectedEntryRevision,
            ]);
            if ($stmt->rowCount() !== 1) {
                throw new DomainException('Состояние записи изменилось. Обновите страницу.');
            }
            $this->incrementCatalogRevision($versionId, $expectedCatalogRevision, $actorId);
            $after = $this->entryState($entry);
            $after['status'] = $toStatus;
            $after['revision'] = $expectedEntryRevision + 1;
            $this->appendEvent($versionId, $actorId, $eventType, 'position', $entryId, $this->entryState($entry), $after, $reason);
        });
    }

    /** @return array<string,mixed> */
    private function lockVersion(int $versionId): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM military_position_catalog_versions WHERE id=:id FOR UPDATE');
        $stmt->execute(['id' => $versionId]);
        $row = $stmt->fetch();
        if (!is_array($row)) {
            throw new DomainException('Версия справочника не найдена.');
        }
        return $row;
    }

    /** @return array<string,mixed> */
    private function lockPublishedVersion(): array
    {
        $rows = $this->pdo->query(
            "SELECT * FROM military_position_catalog_versions WHERE status='published' FOR UPDATE"
        )->fetchAll();
        if (count($rows) !== 1) {
            throw new DomainException('Текущая опубликованная версия не определена однозначно.');
        }
        return $rows[0];
    }

    /** @return array<string,mixed> */
    private function lockEntry(int $versionId, int $entryId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM military_position_types WHERE id=:entry_id AND catalog_version_id=:version_id FOR UPDATE'
        );
        $stmt->execute(['entry_id' => $entryId, 'version_id' => $versionId]);
        $row = $stmt->fetch();
        if (!is_array($row)) {
            throw new DomainException('Воинская должность не найдена в выбранной версии.');
        }
        return $row;
    }

    private function draftExists(): bool
    {
        return (int) $this->pdo->query(
            "SELECT COUNT(*) FROM military_position_catalog_versions WHERE status='draft'"
        )->fetchColumn() > 0;
    }

    /** @return array<string,mixed> */
    private function latestCancelledCanonicalVersion(): array
    {
        $row = $this->pdo->query(
            "SELECT * FROM military_position_catalog_versions "
            . "WHERE catalog_kind='canonical' AND status='cancelled' ORDER BY version_number DESC,id DESC LIMIT 1 FOR UPDATE"
        )->fetch();
        if (!is_array($row)) {
            throw new DomainException('Каноническая основа для нового черновика не найдена.');
        }
        return $row;
    }

    /** @param array<string,mixed> $version */
    private function assertDraft(array $version, int $expectedRevision): void
    {
        if (($version['status'] ?? null) !== 'draft' || ($version['catalog_kind'] ?? null) !== 'canonical') {
            throw new DomainException('Изменения разрешены только в канонической черновой версии.');
        }
        if ((int) $version['revision'] !== $expectedRevision) {
            throw new DomainException('Версия справочника была изменена. Обновите страницу.');
        }
    }

    private function assertPublishable(int $versionId): void
    {
        $active = $this->pdo->prepare(
            "SELECT COUNT(*) FROM military_position_types WHERE catalog_version_id=:id AND status='active'"
        );
        $active->execute(['id' => $versionId]);
        if ((int) $active->fetchColumn() < 1) {
            throw new DomainException('Для публикации нужна хотя бы одна действующая должность.');
        }
        $duplicates = $this->pdo->prepare(
            'SELECT COUNT(*) FROM (SELECT normalized_name FROM military_position_types '
            . 'WHERE catalog_version_id=:id GROUP BY normalized_name HAVING COUNT(*)>1) duplicate_names'
        );
        $duplicates->execute(['id' => $versionId]);
        if ((int) $duplicates->fetchColumn() !== 0) {
            throw new DomainException('В версии есть повторяющиеся канонические наименования.');
        }
        $stable = $this->pdo->prepare(
            'SELECT COUNT(*) FROM (SELECT stable_key FROM military_position_types '
            . 'WHERE catalog_version_id=:id GROUP BY stable_key HAVING COUNT(*)>1) duplicate_keys'
        );
        $stable->execute(['id' => $versionId]);
        if ((int) $stable->fetchColumn() !== 0) {
            throw new DomainException('В версии нарушена стабильная идентичность должностей.');
        }
    }

    private function incrementCatalogRevision(int $versionId, int $expectedRevision, int $actorId): void
    {
        $stmt = $this->pdo->prepare(
            "UPDATE military_position_catalog_versions SET revision=revision+1,updated_by=:actor,updated_at=NOW(6) "
            . "WHERE id=:id AND status='draft' AND revision=:revision"
        );
        $stmt->execute(['actor' => $actorId, 'id' => $versionId, 'revision' => $expectedRevision]);
        if ($stmt->rowCount() !== 1) {
            throw new DomainException('Версия справочника была изменена. Обновите страницу.');
        }
    }

    /** @param array<string,mixed> $input @return array<string,mixed> */
    private function entryInput(array $input): array
    {
        $name = $this->requiredString($input['name'] ?? null, 255, 'Укажите каноническое наименование до 255 символов.');
        return [
            'name' => $name,
            'normalized_name' => $this->normalizeName($name),
            'full_name' => $this->nullableString($input['full_name'] ?? null, 255, 'Полное наименование превышает 255 символов.'),
            'short_name' => $this->nullableString($input['short_name'] ?? null, 128, 'Краткое наименование превышает 128 символов.'),
            'is_combined' => $this->boolean($input['is_combined'] ?? 0),
            'source_type' => $this->enum($input['source_type'] ?? null, ['official','local','imported'], 'Выберите допустимый тип источника.'),
            'source_reference' => $this->nullableString($input['source_reference'] ?? null, 1000, 'Реквизит источника превышает 1000 символов.'),
            'note' => $this->nullableString($input['note'] ?? null, 5000, 'Примечание превышает 5000 символов.'),
            'sort_order' => $this->positiveInt($input['sort_order'] ?? null, 'Порядок должен быть положительным числом.'),
        ];
    }

    private function normalizeName(string $name): string
    {
        $normalized = preg_replace('/[\p{Z}\s]+/u', ' ', trim($name));
        if (!is_string($normalized) || $normalized === '') {
            throw new DomainException('Не удалось нормализовать наименование должности.');
        }
        return mb_strtolower($normalized, 'UTF-8');
    }

    private function positiveInt(mixed $value, string $message): int
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

    private function boolean(mixed $value): int
    {
        if ($value === 1 || $value === '1' || $value === true) {
            return 1;
        }
        if ($value === 0 || $value === '0' || $value === false || $value === null || $value === '') {
            return 0;
        }
        throw new DomainException('Некорректное значение признака составной должности.');
    }

    private function enum(mixed $value, array $allowed, string $message): string
    {
        if (!is_string($value) || !in_array($value, $allowed, true)) {
            throw new DomainException($message);
        }
        return $value;
    }

    private function requiredString(mixed $value, int $maxLength, string $message): string
    {
        if (!is_string($value)) {
            throw new DomainException($message);
        }
        $value = trim($value);
        if ($value === '' || mb_strlen($value, 'UTF-8') > $maxLength) {
            throw new DomainException($message);
        }
        return $value;
    }

    private function nullableString(mixed $value, int $maxLength, string $message): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (!is_string($value)) {
            throw new DomainException($message);
        }
        $value = trim($value);
        if ($value === '') {
            return null;
        }
        if (mb_strlen($value, 'UTF-8') > $maxLength) {
            throw new DomainException($message);
        }
        return $value;
    }

    private function date(mixed $value, string $message): string
    {
        if (!is_string($value) || preg_match('/\A\d{4}-\d{2}-\d{2}\z/D', $value) !== 1) {
            throw new DomainException($message);
        }
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        if (!$date instanceof DateTimeImmutable || $date->format('Y-m-d') !== $value) {
            throw new DomainException($message);
        }
        return $value;
    }

    /** @param array<string,mixed> $entry @return array<string,mixed> */
    private function entryState(array $entry): array
    {
        return [
            'name' => (string) $entry['name'],
            'full_name' => $entry['full_name'],
            'short_name' => $entry['short_name'],
            'is_combined' => (int) $entry['is_combined'],
            'source_type' => (string) $entry['source_type'],
            'source_reference' => $entry['source_reference'],
            'note' => $entry['note'],
            'status' => (string) $entry['status'],
            'sort_order' => (int) $entry['sort_order'],
            'revision' => (int) $entry['revision'],
        ];
    }

    private function appendEvent(
        int $versionId,
        ?int $actorId,
        string $eventType,
        string $targetType,
        int $targetId,
        ?array $before,
        ?array $after,
        ?string $reason
    ): void {
        $stmt = $this->pdo->prepare(
            'INSERT INTO military_position_change_events '
            . '(catalog_version_id,actor_user_id,event_type,target_type,target_id,before_state,after_state,reason,created_at) '
            . 'VALUES (:version_id,:actor,:event_type,:target_type,:target_id,:before_state,:after_state,:reason,NOW(6))'
        );
        $stmt->execute([
            'version_id' => $versionId,
            'actor' => $actorId,
            'event_type' => $eventType,
            'target_type' => $targetType,
            'target_id' => $targetId,
            'before_state' => $before === null ? null : $this->json($before),
            'after_state' => $after === null ? null : $this->json($after),
            'reason' => $reason,
        ]);
    }

    private function json(array $value): string
    {
        return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    }

    private function rethrowEntryConstraint(PDOException $exception): never
    {
        if ((string) $exception->getCode() === '23000') {
            throw new DomainException('В этой версии уже есть должность с таким наименованием или порядком.', 0, $exception);
        }
        throw $exception;
    }

    private function transaction(callable $callback): mixed
    {
        $this->pdo->beginTransaction();
        try {
            $result = $callback();
            $this->pdo->commit();
            return $result;
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $exception;
        }
    }
}
