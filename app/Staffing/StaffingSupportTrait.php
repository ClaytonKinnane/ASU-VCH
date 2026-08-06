<?php

declare(strict_types=1);

trait StaffingSupportTrait
{
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

    private function positiveInt(mixed $value, string $message): int
    {
        if ((!is_int($value) && !is_string($value)) || preg_match('/\A[1-9][0-9]*\z/D', (string) $value) !== 1) {
            throw new DomainException($message);
        }
        $normalized = (string) $value;
        $max = (string) PHP_INT_MAX;
        if (strlen($normalized) > strlen($max)
            || (strlen($normalized) === strlen($max) && strcmp($normalized, $max) > 0)) {
            throw new DomainException($message);
        }
        $result = (int) $normalized;
        if ($result < 1) {
            throw new DomainException($message);
        }
        return $result;
    }

    private function nullablePositiveInt(mixed $value, string $message): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }
        return $this->positiveInt($value, $message);
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

    private function enum(mixed $value, array $allowed, string $message): string
    {
        if (!is_string($value) || !in_array($value, $allowed, true)) {
            throw new DomainException($message);
        }
        return $value;
    }

    private function date(mixed $value, string $message): string
    {
        if (!is_string($value) || preg_match('/\A\d{4}-\d{2}-\d{2}\z/D', $value) !== 1) {
            throw new DomainException($message);
        }
        $parsed = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        if (!$parsed instanceof DateTimeImmutable || $parsed->format('Y-m-d') !== $value) {
            throw new DomainException($message);
        }
        return $value;
    }

    /** @return array<string,mixed> */
    private function lockRegister(int $registerId): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM staffing_registers WHERE id=:id FOR UPDATE');
        $stmt->execute(['id' => $registerId]);
        $row = $stmt->fetch();
        if (!is_array($row)) {
            throw new DomainException('Штатный реестр не найден.');
        }
        return $row;
    }

    /** @return array<string,mixed> */
    private function lockVersion(int $registerId, int $versionId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM staffing_versions WHERE id=:version_id AND staffing_register_id=:register_id FOR UPDATE'
        );
        $stmt->execute(['version_id' => $versionId, 'register_id' => $registerId]);
        $row = $stmt->fetch();
        if (!is_array($row)) {
            throw new DomainException('Версия штатной структуры не найдена.');
        }
        return $row;
    }

    private function assertRegisterActive(array $register): void
    {
        if (($register['status'] ?? null) !== 'active') {
            throw new DomainException('Архивный штатный реестр доступен только для чтения.');
        }
    }

    private function assertDraft(array $version, int $expectedRevision): void
    {
        if (($version['status'] ?? null) !== 'draft') {
            throw new DomainException('Изменение содержимого разрешено только в черновой версии.');
        }
        if ((int) $version['revision'] !== $expectedRevision) {
            throw new DomainException('Версия была изменена другим пользователем. Обновите страницу.');
        }
    }

    private function incrementVersionRevision(int $registerId, int $versionId, int $expectedRevision, int $actorId): void
    {
        $stmt = $this->pdo->prepare(
            "UPDATE staffing_versions SET revision=revision+1,updated_by=:actor,updated_at=NOW() "
            . "WHERE id=:version_id AND staffing_register_id=:register_id AND status='draft' AND revision=:revision"
        );
        $stmt->execute([
            'actor' => $actorId,
            'version_id' => $versionId,
            'register_id' => $registerId,
            'revision' => $expectedRevision,
        ]);
        if ($stmt->rowCount() !== 1) {
            throw new DomainException('Версия была изменена другим пользователем. Обновите страницу.');
        }
    }

    private function appendEvent(
        int $registerId,
        ?int $versionId,
        ?int $slotIdentityId,
        int $actorId,
        string $eventType,
        string $targetType,
        ?int $targetId,
        ?array $before,
        ?array $after,
        ?string $reason = null
    ): void {
        $stmt = $this->pdo->prepare(
            'INSERT INTO staffing_change_events '
            . '(staffing_register_id,staffing_version_id,staffing_slot_identity_id,actor_user_id,event_type,target_type,target_id,before_state,after_state,reason,created_at) '
            . 'VALUES (:register_id,:version_id,:identity_id,:actor_id,:event_type,:target_type,:target_id,:before_state,:after_state,:reason,NOW())'
        );
        $stmt->execute([
            'register_id' => $registerId,
            'version_id' => $versionId,
            'identity_id' => $slotIdentityId,
            'actor_id' => $actorId,
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
        return json_encode(
            $value,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR
        );
    }

    /** @return array<string,mixed> */
    private function currentPositionCatalog(): array
    {
        $rows = $this->pdo->query(
            "SELECT id,code,name,rank_catalog_version_id,organizational_element_catalog_version_id "
            . "FROM military_position_catalog_versions WHERE status='published' "
            . 'ORDER BY valid_from DESC,id DESC LIMIT 2'
        )->fetchAll();
        if (count($rows) !== 1) {
            throw new DomainException('Текущая версия справочника должностей не определена однозначно.');
        }
        return $rows[0];
    }

    /** @return array<string,mixed> */
    private function currentVusCatalog(): array
    {
        $rows = $this->pdo->query(
            "SELECT id,code,name FROM military_occupational_specialty_catalog_versions "
            . "WHERE status='published' AND valid_to IS NULL ORDER BY valid_from DESC,id DESC LIMIT 2"
        )->fetchAll();
        if (count($rows) !== 1) {
            throw new DomainException('Текущая версия справочника публичных сведений о ВУС не определена однозначно.');
        }
        return $rows[0];
    }

    /** @return array<string,mixed> */
    private function organizationVersion(int $structureId, int $versionId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT id,organizational_structure_id,version_number,status FROM organizational_structure_versions "
            . "WHERE id=:version_id AND organizational_structure_id=:structure_id AND status IN ('active','superseded') LIMIT 1"
        );
        $stmt->execute(['version_id' => $versionId, 'structure_id' => $structureId]);
        $row = $stmt->fetch();
        if (!is_array($row)) {
            throw new DomainException('Выбранная версия организационной структуры недоступна.');
        }
        return $row;
    }

    /** @param list<array{public_disclosure_id:int,requirement_role:string,sort_order:int}> $requirements */
    private function replaceVusRequirements(
        int $registerId,
        int $versionId,
        int $slotId,
        int $catalogVersionId,
        array $requirements,
        int $actorId
    ): void {
        $delete = $this->pdo->prepare('DELETE FROM staffing_slot_vus_requirements WHERE staffing_slot_id=:id');
        $delete->execute(['id' => $slotId]);
        if ($requirements === []) {
            return;
        }
        $seenDisclosure = [];
        $seenOrder = [];
        $insert = $this->pdo->prepare(
            'INSERT INTO staffing_slot_vus_requirements '
            . '(staffing_version_id,staffing_register_id,staffing_slot_id,vus_catalog_version_id,public_disclosure_id,requirement_role,sort_order,created_by,created_at) '
            . 'VALUES (:version_id,:register_id,:slot_id,:catalog_id,:disclosure_id,:role,:sort_order,:actor,NOW())'
        );
        foreach ($requirements as $requirement) {
            $disclosureId = $this->positiveInt($requirement['public_disclosure_id'] ?? null, 'Некорректный идентификатор ВУС.');
            $role = $this->enum($requirement['requirement_role'] ?? null, ['required', 'allowed', 'preferred'], 'Некорректная роль требования ВУС.');
            $sortOrder = $this->positiveInt($requirement['sort_order'] ?? null, 'Некорректный порядок требования ВУС.');
            if (isset($seenDisclosure[$disclosureId]) || isset($seenOrder[$sortOrder])) {
                throw new DomainException('Требования ВУС не должны повторяться.');
            }
            $seenDisclosure[$disclosureId] = true;
            $seenOrder[$sortOrder] = true;
            $insert->execute([
                'version_id' => $versionId,
                'register_id' => $registerId,
                'slot_id' => $slotId,
                'catalog_id' => $catalogVersionId,
                'disclosure_id' => $disclosureId,
                'role' => $role,
                'sort_order' => $sortOrder,
                'actor' => $actorId,
            ]);
        }
    }
}
