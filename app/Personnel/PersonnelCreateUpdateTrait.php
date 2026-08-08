<?php

declare(strict_types=1);

trait PersonnelCreateUpdateTrait
{
    public function createPerson(array $input, int $actorUserId): int
    {
        $core = $this->coreInput($input);
        $now = $this->now();
        $this->pdo->beginTransaction();
        try {
            $stmt = $this->pdo->prepare(
                'INSERT INTO personnel_records '
                . '(last_name,first_name,middle_name,birth_date,birth_place,citizenship,nationality,religion,record_status,revision,created_by,created_at,updated_by,updated_at) '
                . "VALUES (:last_name,:first_name,:middle_name,:birth_date,:birth_place,:citizenship,:nationality,:religion,'active',1,:created_by,:created_at,:updated_by,:updated_at)"
            );
            $stmt->execute($core + [
                'created_by' => $actorUserId,
                'created_at' => $now,
                'updated_by' => $actorUserId,
                'updated_at' => $now,
            ]);
            $id = (int) $this->pdo->lastInsertId();
            $this->appendEvent($id, $actorUserId, 'personnel.created', 'personnel_record', $id, null, null, null, $core + ['record_status' => 'active'], null);
            $this->pdo->commit();
            return $id;
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $exception;
        }
    }

    public function updatePerson(int $id, array $input, int $expectedRevision, ?string $reason, int $actorUserId): void
    {
        $core = $this->coreInput($input);
        $reason = $this->nullableText($reason, 500, 'Причина изменения');
        $this->pdo->beginTransaction();
        try {
            $record = $this->lockRecord($id);
            $this->assertActive($record);
            $this->assertRevision($record, $expectedRevision);
            $before = $this->safeCoreState($record);
            $after = $core + ['record_status' => 'active'];
            if ($before === $after) {
                throw new DomainException('Изменения отсутствуют.');
            }
            $newRevision = $expectedRevision + 1;
            $stmt = $this->pdo->prepare(
                'UPDATE personnel_records SET last_name=:last_name,first_name=:first_name,middle_name=:middle_name,birth_date=:birth_date,'
                . 'birth_place=:birth_place,citizenship=:citizenship,nationality=:nationality,religion=:religion,'
                . 'revision=:new_revision,updated_by=:updated_by,updated_at=:updated_at WHERE id=:id AND revision=:old_revision'
            );
            $stmt->execute($core + [
                'new_revision' => $newRevision,
                'updated_by' => $actorUserId,
                'updated_at' => $this->now(),
                'id' => $id,
                'old_revision' => $expectedRevision,
            ]);
            if ($stmt->rowCount() !== 1) {
                throw new DomainException('Карточка была изменена другим действием. Обновите страницу и повторите операцию.');
            }
            $this->appendEvent($id, $actorUserId, 'personnel.core_updated', 'personnel_record', $id, $expectedRevision, $newRevision, $before, $after, $reason);
            $this->pdo->commit();
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $exception;
        }
    }
}
