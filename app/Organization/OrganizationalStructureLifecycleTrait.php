<?php

declare(strict_types=1);

trait OrganizationalStructureLifecycleTrait
{
    public function approveVersion(int $versionId, string $effectiveFrom, int $expectedRevision, int $actorUserId): void
    {
        if (!$this->validDate($effectiveFrom)) {
            throw new DomainException('Укажите дату вступления версии в действие.');
        }
        $this->transaction(function () use ($versionId, $effectiveFrom, $expectedRevision, $actorUserId): void {
            $version = $this->lockDraftVersion($versionId, $expectedRevision);
            $active = $this->lockActiveVersion((int) $version['organizational_structure_id']);
            if ($active !== null && $active['effective_from'] !== null && $effectiveFrom <= (string) $active['effective_from']) {
                throw new DomainException('Дата новой версии должна быть позже даты начала действующей версии.');
            }
            $this->validatePublishableVersion($versionId, (int) $version['catalog_version_id']);
            $now = $this->now();
            $stmt = $this->pdo->prepare(
                "UPDATE organizational_structure_versions SET status = 'approved', effective_from = :effective_from, approved_by = :actor, approved_at = :approved_at, updated_by = :updated_by, updated_at = :updated_at WHERE id = :id"
            );
            $stmt->execute([
                'effective_from' => $effectiveFrom,
                'actor' => $actorUserId,
                'approved_at' => $now,
                'updated_by' => $actorUserId,
                'updated_at' => $now,
                'id' => $versionId,
            ]);
            $this->recordEvent((int) $version['organizational_structure_id'], $versionId, null, $actorUserId, 'version.approved', [
                'status' => 'draft',
            ], [
                'status' => 'approved',
                'effective_from' => $effectiveFrom,
            ]);
        });
    }

    public function activateVersion(int $versionId, int $actorUserId): void
    {
        $this->transaction(function () use ($versionId, $actorUserId): void {
            $structureId = $this->versionStructureId($versionId);
            $structure = $this->lockStructure($structureId);
            if ((string) $structure['status'] !== 'active') {
                throw new DomainException('Архивная структура не может быть опубликована.');
            }
            $stmt = $this->pdo->prepare('SELECT * FROM organizational_structure_versions WHERE id = :id FOR UPDATE');
            $stmt->execute(['id' => $versionId]);
            $version = $stmt->fetch();
            if (!is_array($version) || (int) $version['organizational_structure_id'] !== $structureId || (string) $version['status'] !== 'approved') {
                throw new DomainException('Активировать можно только утверждённую версию.');
            }
            $effectiveFrom = (string) $version['effective_from'];
            if ($effectiveFrom > date('Y-m-d')) {
                throw new DomainException('Версия не может быть введена в действие раньше установленной даты.');
            }
            $active = $this->lockActiveVersion($structureId);
            if ($active !== null && $active['effective_from'] !== null && $effectiveFrom <= (string) $active['effective_from']) {
                throw new DomainException('Дата новой версии должна быть позже даты начала действующей версии.');
            }
            $now = $this->now();
            if ($active !== null) {
                $supersede = $this->pdo->prepare(
                    "UPDATE organizational_structure_versions SET status = 'superseded', effective_to = :effective_to, updated_by = :actor, updated_at = :updated_at WHERE id = :id"
                );
                $supersede->execute([
                    'effective_to' => $effectiveFrom,
                    'actor' => $actorUserId,
                    'updated_at' => $now,
                    'id' => (int) $active['id'],
                ]);
                $this->recordEvent($structureId, (int) $active['id'], null, $actorUserId, 'version.superseded', [
                    'status' => 'active',
                    'effective_to' => null,
                ], [
                    'status' => 'superseded',
                    'effective_to' => $effectiveFrom,
                ]);
            }
            $activate = $this->pdo->prepare(
                "UPDATE organizational_structure_versions SET status = 'active', effective_to = NULL, activated_by = :actor, activated_at = :activated_at, updated_by = :updated_by, updated_at = :updated_at WHERE id = :id"
            );
            $activate->execute([
                'actor' => $actorUserId,
                'activated_at' => $now,
                'updated_by' => $actorUserId,
                'updated_at' => $now,
                'id' => $versionId,
            ]);
            $this->recordEvent($structureId, $versionId, null, $actorUserId, 'version.activated', [
                'status' => 'approved',
            ], [
                'status' => 'active',
                'effective_from' => $effectiveFrom,
            ]);
        });
    }

    public function cancelVersion(int $versionId, string $reason, int $expectedRevision, int $actorUserId): void
    {
        $reason = $this->requiredText($reason, 1000, 'Укажите основание отмены версии.');
        $this->transaction(function () use ($versionId, $reason, $expectedRevision, $actorUserId): void {
            $structureId = $this->versionStructureId($versionId);
            $this->lockStructure($structureId);
            $stmt = $this->pdo->prepare('SELECT * FROM organizational_structure_versions WHERE id = :id FOR UPDATE');
            $stmt->execute(['id' => $versionId]);
            $version = $stmt->fetch();
            if (!is_array($version) || (int) $version['organizational_structure_id'] !== $structureId || !in_array((string) $version['status'], ['draft', 'approved'], true)) {
                throw new DomainException('Отменить можно только черновую или утверждённую версию.');
            }
            if ((int) $version['revision'] !== $expectedRevision) {
                throw new DomainException('Структура была изменена другим пользователем. Обновите страницу и повторите действие.');
            }
            $oldStatus = (string) $version['status'];
            $now = $this->now();
            $update = $this->pdo->prepare(
                "UPDATE organizational_structure_versions SET status = 'cancelled', cancelled_by = :actor, cancelled_at = :cancelled_at, cancellation_reason = :reason, updated_by = :updated_by, updated_at = :updated_at WHERE id = :id"
            );
            $update->execute([
                'actor' => $actorUserId,
                'cancelled_at' => $now,
                'reason' => $reason,
                'updated_by' => $actorUserId,
                'updated_at' => $now,
                'id' => $versionId,
            ]);
            $this->recordEvent((int) $version['organizational_structure_id'], $versionId, null, $actorUserId, 'version.cancelled', [
                'status' => $oldStatus,
            ], [
                'status' => 'cancelled',
            ], $reason);
        });
    }

    public function archiveStructure(int $structureId, string $reason, int $actorUserId): void
    {
        $reason = $this->requiredText($reason, 1000, 'Укажите основание архивирования.');
        $this->transaction(function () use ($structureId, $reason, $actorUserId): void {
            $structure = $this->lockStructure($structureId);
            if ((string) $structure['status'] === 'archived') {
                throw new DomainException('Структура уже архивирована.');
            }
            if ($this->pendingVersionExists($structureId)) {
                throw new DomainException('Перед архивированием отмените черновую или утверждённую версию.');
            }
            $now = $this->now();
            $update = $this->pdo->prepare(
                "UPDATE organizational_structures SET status = 'archived', archived_by = :actor, archived_at = :archived_at, archive_reason = :reason, updated_by = :updated_by, updated_at = :updated_at WHERE id = :id"
            );
            $update->execute([
                'actor' => $actorUserId,
                'archived_at' => $now,
                'reason' => $reason,
                'updated_by' => $actorUserId,
                'updated_at' => $now,
                'id' => $structureId,
            ]);
            $this->recordEvent($structureId, null, null, $actorUserId, 'structure.archived', ['status' => 'active'], ['status' => 'archived'], $reason);
        });
    }

    public function restoreStructure(int $structureId, string $reason, int $actorUserId): void
    {
        $reason = $this->requiredText($reason, 1000, 'Укажите основание восстановления.');
        $this->transaction(function () use ($structureId, $reason, $actorUserId): void {
            $structure = $this->lockStructure($structureId);
            if ((string) $structure['status'] !== 'archived') {
                throw new DomainException('Структура не находится в архиве.');
            }
            $now = $this->now();
            $update = $this->pdo->prepare(
                "UPDATE organizational_structures SET status = 'active', restored_by = :actor, restored_at = :restored_at, restore_reason = :reason, updated_by = :updated_by, updated_at = :updated_at WHERE id = :id"
            );
            $update->execute([
                'actor' => $actorUserId,
                'restored_at' => $now,
                'reason' => $reason,
                'updated_by' => $actorUserId,
                'updated_at' => $now,
                'id' => $structureId,
            ]);
            $this->recordEvent($structureId, null, null, $actorUserId, 'structure.restored', ['status' => 'archived'], ['status' => 'active'], $reason);
        });
    }

    /** @return array<string,mixed> */
}
