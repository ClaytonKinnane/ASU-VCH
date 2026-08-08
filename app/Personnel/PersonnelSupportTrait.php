<?php

declare(strict_types=1);

trait PersonnelSupportTrait
{
    /** @return array<string,mixed> */
    private function lockRecord(int $id): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM personnel_records WHERE id=:id FOR UPDATE');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        if (!$row) {
            throw new DomainException('Карточка военнослужащего не найдена.');
        }
        return $row;
    }

    private function assertRevision(array $record, int $expectedRevision): void
    {
        if ((int) $record['revision'] !== $expectedRevision) {
            throw new DomainException('Карточка была изменена другим действием. Обновите страницу и повторите операцию.');
        }
    }

    private function assertActive(array $record): void
    {
        if ((string) $record['record_status'] !== 'active') {
            throw new DomainException('Архивная карточка доступна только для чтения.');
        }
    }

    private function assertArchived(array $record): void
    {
        if ((string) $record['record_status'] !== 'archived') {
            throw new DomainException('Карточка не находится в архиве.');
        }
    }

    private function requiredText(mixed $value, int $maxLength, string $label): string
    {
        if (!is_string($value)) {
            throw new DomainException($label . ': некорректное значение.');
        }
        $value = trim($value);
        if ($value === '' || mb_strlen($value) > $maxLength || $this->containsControlCharacters($value)) {
            throw new DomainException($label . ': заполните поле корректно.');
        }
        return $value;
    }

    private function nullableText(mixed $value, int $maxLength, string $label): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (!is_string($value)) {
            throw new DomainException($label . ': некорректное значение.');
        }
        $value = trim($value);
        if ($value === '') {
            return null;
        }
        if (mb_strlen($value) > $maxLength || $this->containsControlCharacters($value)) {
            throw new DomainException($label . ': заполните поле корректно.');
        }
        return $value;
    }

    private function containsControlCharacters(string $value): bool
    {
        return preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', $value) === 1;
    }

    private function dateValue(mixed $value, string $label, bool $nullable = false): ?string
    {
        if (($value === null || $value === '') && $nullable) {
            return null;
        }
        if (!is_string($value) || preg_match('/\A\d{4}-\d{2}-\d{2}\z/D', $value) !== 1) {
            throw new DomainException($label . ': укажите корректную дату.');
        }
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        if (!$date || $date->format('Y-m-d') !== $value) {
            throw new DomainException($label . ': укажите корректную дату.');
        }
        return $value;
    }

    /** @return array{last_name:string,first_name:string,middle_name:?string,birth_date:string,birth_place:?string,citizenship:?string,nationality:?string,religion:?string} */
    private function coreInput(array $input): array
    {
        $birthDate = $this->dateValue($input['birth_date'] ?? null, 'Дата рождения');
        if ($birthDate === null || $birthDate > (new DateTimeImmutable('today'))->format('Y-m-d')) {
            throw new DomainException('Дата рождения не может быть в будущем.');
        }
        return [
            'last_name' => $this->requiredText($input['last_name'] ?? null, 100, 'Фамилия'),
            'first_name' => $this->requiredText($input['first_name'] ?? null, 100, 'Имя'),
            'middle_name' => $this->nullableText($input['middle_name'] ?? null, 100, 'Отчество'),
            'birth_date' => $birthDate,
            'birth_place' => $this->nullableText($input['birth_place'] ?? null, 255, 'Место рождения'),
            'citizenship' => $this->nullableText($input['citizenship'] ?? null, 100, 'Гражданство'),
            'nationality' => $this->nullableText($input['nationality'] ?? null, 100, 'Национальность'),
            'religion' => $this->nullableText($input['religion'] ?? null, 150, 'Вероисповедание'),
        ];
    }

    /** @return array<string,mixed> */
    private function safeCoreState(array $record): array
    {
        return [
            'last_name' => $record['last_name'],
            'first_name' => $record['first_name'],
            'middle_name' => $record['middle_name'],
            'birth_date' => $record['birth_date'],
            'birth_place' => $record['birth_place'],
            'citizenship' => $record['citizenship'],
            'nationality' => $record['nationality'],
            'religion' => $record['religion'],
            'record_status' => $record['record_status'] ?? 'active',
        ];
    }

    private function encodeState(?array $state): ?string
    {
        if ($state === null) {
            return null;
        }
        return json_encode($state, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    }

    private function appendEvent(
        int $personnelId,
        int $actorUserId,
        string $eventType,
        string $targetType,
        ?int $targetId,
        ?int $revisionFrom,
        ?int $revisionTo,
        ?array $beforeState,
        ?array $afterState,
        ?string $reason
    ): void {
        $stmt = $this->pdo->prepare(
            'INSERT INTO personnel_change_events '
            . '(personnel_id,actor_user_id,event_type,target_type,target_id,revision_from,revision_to,before_state,after_state,reason,occurred_at) '
            . 'VALUES (:personnel_id,:actor_user_id,:event_type,:target_type,:target_id,:revision_from,:revision_to,:before_state,:after_state,:reason,:occurred_at)'
        );
        $stmt->execute([
            'personnel_id' => $personnelId,
            'actor_user_id' => $actorUserId,
            'event_type' => $eventType,
            'target_type' => $targetType,
            'target_id' => $targetId,
            'revision_from' => $revisionFrom,
            'revision_to' => $revisionTo,
            'before_state' => $this->encodeState($beforeState),
            'after_state' => $this->encodeState($afterState),
            'reason' => $reason,
            'occurred_at' => $this->now(),
        ]);
    }

    private function now(): string
    {
        return (new DateTimeImmutable())->format('Y-m-d H:i:s');
    }

    private function bumpRevision(int $personnelId, int $oldRevision, int $actorUserId): int
    {
        $newRevision = $oldRevision + 1;
        $stmt = $this->pdo->prepare(
            'UPDATE personnel_records SET revision=:new_revision,updated_by=:updated_by,updated_at=:updated_at '
            . 'WHERE id=:id AND revision=:old_revision'
        );
        $stmt->execute([
            'new_revision' => $newRevision,
            'updated_by' => $actorUserId,
            'updated_at' => $this->now(),
            'id' => $personnelId,
            'old_revision' => $oldRevision,
        ]);
        if ($stmt->rowCount() !== 1) {
            throw new DomainException('Карточка была изменена другим действием. Обновите страницу и повторите операцию.');
        }
        return $newRevision;
    }

    /** @return array<string,mixed> */
    private function identifierType(int $identifierTypeId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id,code,name,description,enforce_global_unique FROM personnel_identifier_types WHERE id=:id LIMIT 1'
        );
        $stmt->execute(['id' => $identifierTypeId]);
        $row = $stmt->fetch();
        if (!$row) {
            throw new DomainException('Тип идентификатора не найден.');
        }
        return $row;
    }

    /** @return array<string,mixed> */
    private function activeIdentifier(int $personnelId, int $identifierTypeId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM personnel_identifiers WHERE personnel_id=:personnel_id AND identifier_type_id=:identifier_type_id AND valid_to IS NULL FOR UPDATE'
        );
        $stmt->execute(['personnel_id' => $personnelId, 'identifier_type_id' => $identifierTypeId]);
        $rows = $stmt->fetchAll();
        if (count($rows) !== 1) {
            throw new DomainException(count($rows) === 0 ? 'Действующий идентификатор этого типа отсутствует.' : 'Обнаружено некорректное состояние идентификаторов.');
        }
        return $rows[0];
    }

    private function assertIdentifierValueAvailable(array $type, string $value, ?int $ignoreIdentifierId = null): void
    {
        if ((int) $type['enforce_global_unique'] !== 1) {
            return;
        }
        $sql = 'SELECT id FROM personnel_identifiers WHERE identifier_type_id=:type_id AND value=:value';
        $params = ['type_id' => (int) $type['id'], 'value' => $value];
        if ($ignoreIdentifierId !== null) {
            $sql .= ' AND id<>:ignore_id';
            $params['ignore_id'] = $ignoreIdentifierId;
        }
        $sql .= ' LIMIT 1 FOR UPDATE';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        if ($stmt->fetch()) {
            throw new DomainException((string) $type['name'] . ': это значение уже использовалось и не может быть назначено повторно.');
        }
    }
}
