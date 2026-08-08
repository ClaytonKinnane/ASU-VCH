<?php

declare(strict_types=1);

trait PersonnelLifecycleTrait
{
    public function archivePerson(int $id, int $expectedRevision, mixed $reasonInput, int $actorUserId): void
    {
        $reason = $this->requiredText($reasonInput, 500, 'Основание архивирования');
        $this->pdo->beginTransaction();
        try {
            $record = $this->lockRecord($id);
            $this->assertActive($record);
            $this->assertRevision($record, $expectedRevision);
            $newRevision = $expectedRevision + 1;
            $stmt = $this->pdo->prepare(
                "UPDATE personnel_records SET record_status='archived',revision=:new_revision,updated_by=:updated_by,updated_at=:updated_at,"
                . 'archived_by=:archived_by,archived_at=:archived_at,archive_reason=:archive_reason WHERE id=:id AND revision=:old_revision'
            );
            $now = $this->now();
            $stmt->execute([
                'new_revision' => $newRevision,
                'updated_by' => $actorUserId,
                'updated_at' => $now,
                'archived_by' => $actorUserId,
                'archived_at' => $now,
                'archive_reason' => $reason,
                'id' => $id,
                'old_revision' => $expectedRevision,
            ]);
            if ($stmt->rowCount() !== 1) {
                throw new DomainException('Карточка была изменена другим действием. Обновите страницу.');
            }
            $this->appendEvent(
                $id,
                $actorUserId,
                'personnel.archived',
                'personnel_record',
                $id,
                $expectedRevision,
                $newRevision,
                ['record_status' => 'active'],
                ['record_status' => 'archived'],
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

    public function restorePerson(int $id, int $expectedRevision, int $actorUserId): void
    {
        $this->pdo->beginTransaction();
        try {
            $record = $this->lockRecord($id);
            $this->assertArchived($record);
            $this->assertRevision($record, $expectedRevision);
            $newRevision = $expectedRevision + 1;
            $stmt = $this->pdo->prepare(
                "UPDATE personnel_records SET record_status='active',revision=:new_revision,updated_by=:updated_by,updated_at=:updated_at,"
                . 'archived_by=NULL,archived_at=NULL,archive_reason=NULL WHERE id=:id AND revision=:old_revision'
            );
            $stmt->execute([
                'new_revision' => $newRevision,
                'updated_by' => $actorUserId,
                'updated_at' => $this->now(),
                'id' => $id,
                'old_revision' => $expectedRevision,
            ]);
            if ($stmt->rowCount() !== 1) {
                throw new DomainException('Карточка была изменена другим действием. Обновите страницу.');
            }
            $this->appendEvent(
                $id,
                $actorUserId,
                'personnel.restored',
                'personnel_record',
                $id,
                $expectedRevision,
                $newRevision,
                ['record_status' => 'archived'],
                ['record_status' => 'active'],
                null
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
