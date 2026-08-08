<?php

declare(strict_types=1);

trait PersonnelIdentifierTrait
{
    public function addIdentifier(int $personnelId, int $identifierTypeId, mixed $valueInput, mixed $validFromInput, mixed $noteInput, int $expectedRevision, int $actorUserId): int
    {
        $value = $this->requiredText($valueInput, 255, 'Значение идентификатора');
        $validFrom = $this->dateValue($validFromInput, 'Дата начала действия', true);
        $note = $this->nullableText($noteInput, 500, 'Примечание');
        $this->pdo->beginTransaction();
        try {
            $record = $this->lockRecord($personnelId);
            $this->assertActive($record);
            $this->assertRevision($record, $expectedRevision);
            $type = $this->identifierType($identifierTypeId);
            $existing = $this->pdo->prepare(
                'SELECT id FROM personnel_identifiers WHERE personnel_id=:personnel_id AND identifier_type_id=:type_id AND valid_to IS NULL LIMIT 1 FOR UPDATE'
            );
            $existing->execute(['personnel_id' => $personnelId, 'type_id' => $identifierTypeId]);
            if ($existing->fetch()) {
                throw new DomainException('Действующий идентификатор этого типа уже существует. Используйте замену.');
            }
            $this->assertIdentifierValueAvailable($type, $value);
            $stmt = $this->pdo->prepare(
                'INSERT INTO personnel_identifiers '
                . '(personnel_id,identifier_type_id,enforce_global_unique,value,valid_from,valid_to,note,created_by,created_at) '
                . 'VALUES (:personnel_id,:identifier_type_id,:enforce_global_unique,:value,:valid_from,NULL,:note,:created_by,:created_at)'
            );
            $stmt->execute([
                'personnel_id' => $personnelId,
                'identifier_type_id' => $identifierTypeId,
                'enforce_global_unique' => (int) $type['enforce_global_unique'],
                'value' => $value,
                'valid_from' => $validFrom,
                'note' => $note,
                'created_by' => $actorUserId,
                'created_at' => $this->now(),
            ]);
            $identifierId = (int) $this->pdo->lastInsertId();
            $newRevision = $this->bumpRevision($personnelId, $expectedRevision, $actorUserId);
            $this->appendEvent(
                $personnelId,
                $actorUserId,
                'identifier.added',
                'personnel_identifier',
                $identifierId,
                $expectedRevision,
                $newRevision,
                null,
                ['type_code' => $type['code'], 'value' => $value, 'valid_from' => $validFrom],
                $note
            );
            $this->pdo->commit();
            return $identifierId;
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $exception;
        }
    }

    public function replaceIdentifier(int $personnelId, int $identifierTypeId, mixed $newValueInput, mixed $effectiveDateInput, mixed $reasonInput, int $expectedRevision, int $actorUserId): int
    {
        $newValue = $this->requiredText($newValueInput, 255, 'Новое значение');
        $effectiveDate = $this->dateValue($effectiveDateInput, 'Дата замены');
        $reason = $this->nullableText($reasonInput, 500, 'Причина замены');
        if ($effectiveDate === null) {
            throw new DomainException('Дата замены обязательна.');
        }
        $this->pdo->beginTransaction();
        try {
            $record = $this->lockRecord($personnelId);
            $this->assertActive($record);
            $this->assertRevision($record, $expectedRevision);
            $type = $this->identifierType($identifierTypeId);
            $old = $this->activeIdentifier($personnelId, $identifierTypeId);
            if ((string) $old['value'] === $newValue) {
                throw new DomainException('Новое значение совпадает с действующим.');
            }
            if ($old['valid_from'] !== null && $effectiveDate < (string) $old['valid_from']) {
                throw new DomainException('Дата замены не может быть раньше начала действия текущего идентификатора.');
            }
            $this->assertIdentifierValueAvailable($type, $newValue);
            $endedAt = $this->now();
            $end = $this->pdo->prepare(
                'UPDATE personnel_identifiers SET valid_to=:valid_to,ended_by=:ended_by,ended_at=:ended_at WHERE id=:id AND valid_to IS NULL'
            );
            $end->execute(['valid_to' => $effectiveDate, 'ended_by' => $actorUserId, 'ended_at' => $endedAt, 'id' => (int) $old['id']]);
            if ($end->rowCount() !== 1) {
                throw new DomainException('Идентификатор уже был изменён. Обновите страницу.');
            }
            $insert = $this->pdo->prepare(
                'INSERT INTO personnel_identifiers '
                . '(personnel_id,identifier_type_id,enforce_global_unique,value,valid_from,valid_to,note,created_by,created_at) '
                . 'VALUES (:personnel_id,:identifier_type_id,:enforce_global_unique,:value,:valid_from,NULL,:note,:created_by,:created_at)'
            );
            $insert->execute([
                'personnel_id' => $personnelId,
                'identifier_type_id' => $identifierTypeId,
                'enforce_global_unique' => (int) $type['enforce_global_unique'],
                'value' => $newValue,
                'valid_from' => $effectiveDate,
                'note' => $reason,
                'created_by' => $actorUserId,
                'created_at' => $endedAt,
            ]);
            $newIdentifierId = (int) $this->pdo->lastInsertId();
            $newRevision = $this->bumpRevision($personnelId, $expectedRevision, $actorUserId);
            $this->appendEvent(
                $personnelId,
                $actorUserId,
                'identifier.replaced',
                'personnel_identifier',
                $newIdentifierId,
                $expectedRevision,
                $newRevision,
                ['identifier_id' => (int) $old['id'], 'type_code' => $type['code'], 'value' => $old['value'], 'valid_to' => $effectiveDate],
                ['identifier_id' => $newIdentifierId, 'type_code' => $type['code'], 'value' => $newValue, 'valid_from' => $effectiveDate],
                $reason
            );
            $this->pdo->commit();
            return $newIdentifierId;
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $exception;
        }
    }

    public function endIdentifier(int $personnelId, int $identifierTypeId, mixed $effectiveDateInput, mixed $reasonInput, int $expectedRevision, int $actorUserId): void
    {
        $effectiveDate = $this->dateValue($effectiveDateInput, 'Дата окончания действия');
        $reason = $this->nullableText($reasonInput, 500, 'Причина');
        if ($effectiveDate === null) {
            throw new DomainException('Дата окончания действия обязательна.');
        }
        $this->pdo->beginTransaction();
        try {
            $record = $this->lockRecord($personnelId);
            $this->assertActive($record);
            $this->assertRevision($record, $expectedRevision);
            $type = $this->identifierType($identifierTypeId);
            $old = $this->activeIdentifier($personnelId, $identifierTypeId);
            if ($old['valid_from'] !== null && $effectiveDate < (string) $old['valid_from']) {
                throw new DomainException('Дата окончания не может быть раньше начала действия идентификатора.');
            }
            $end = $this->pdo->prepare(
                'UPDATE personnel_identifiers SET valid_to=:valid_to,ended_by=:ended_by,ended_at=:ended_at WHERE id=:id AND valid_to IS NULL'
            );
            $end->execute(['valid_to' => $effectiveDate, 'ended_by' => $actorUserId, 'ended_at' => $this->now(), 'id' => (int) $old['id']]);
            if ($end->rowCount() !== 1) {
                throw new DomainException('Идентификатор уже был изменён. Обновите страницу.');
            }
            $newRevision = $this->bumpRevision($personnelId, $expectedRevision, $actorUserId);
            $this->appendEvent(
                $personnelId,
                $actorUserId,
                'identifier.ended',
                'personnel_identifier',
                (int) $old['id'],
                $expectedRevision,
                $newRevision,
                ['type_code' => $type['code'], 'value' => $old['value'], 'valid_from' => $old['valid_from']],
                ['type_code' => $type['code'], 'value' => $old['value'], 'valid_to' => $effectiveDate],
                $reason
            );
            $this->pdo->commit();
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $exception;
        }
    }
}
