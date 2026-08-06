<?php

declare(strict_types=1);

trait StaffingLifecycleTrait
{
    public function approveVersion(int $registerId, int $versionId, int $expectedRevision, int $actorId): void
    {
        $this->transaction(function () use ($registerId, $versionId, $expectedRevision, $actorId): void {
            $register = $this->lockRegister($registerId);
            $this->assertRegisterActive($register);
            $version = $this->lockVersion($registerId, $versionId);
            $this->assertDraft($version, $expectedRevision);

            $primary = $this->pdo->prepare(
                "SELECT COUNT(*) FROM staffing_version_documents WHERE staffing_version_id=:id AND document_role='primary_basis'"
            );
            $primary->execute(['id' => $versionId]);
            if ((int) $primary->fetchColumn() !== 1) {
                throw new DomainException('Для утверждения требуется ровно один основной документ-основание.');
            }

            $slots = $this->pdo->prepare(
                "SELECT COUNT(*) FROM staffing_slots WHERE staffing_version_id=:id AND normative_state='active'"
            );
            $slots->execute(['id' => $versionId]);
            if ((int) $slots->fetchColumn() < 1) {
                throw new DomainException('Для утверждения требуется хотя бы одна действующая нормативная позиция.');
            }

            $stmt = $this->pdo->prepare(
                "UPDATE staffing_versions SET status='approved',approved_by=:actor,approved_at=NOW(),updated_by=:actor,updated_at=NOW() "
                . "WHERE id=:version_id AND staffing_register_id=:register_id AND status='draft' AND revision=:revision"
            );
            $stmt->execute([
                'actor' => $actorId,
                'version_id' => $versionId,
                'register_id' => $registerId,
                'revision' => $expectedRevision,
            ]);
            if ($stmt->rowCount() !== 1) {
                throw new DomainException('Версия не утверждена из-за изменения состояния.');
            }
            $this->appendEvent(
                $registerId,
                $versionId,
                null,
                $actorId,
                'version.approved',
                'version',
                $versionId,
                ['status' => 'draft'],
                ['status' => 'approved']
            );
        });
    }

    public function cancelVersion(
        int $registerId,
        int $versionId,
        int $expectedRevision,
        string $reason,
        int $actorId
    ): void {
        $reason = $this->requiredString($reason, 1000, 'Укажите основание отмены версии.');
        $this->transaction(function () use ($registerId, $versionId, $expectedRevision, $reason, $actorId): void {
            $register = $this->lockRegister($registerId);
            $this->assertRegisterActive($register);
            $version = $this->lockVersion($registerId, $versionId);
            if (!in_array((string) $version['status'], ['draft', 'approved'], true)) {
                throw new DomainException('Отменить можно только черновую или утверждённую версию.');
            }
            if ((int) $version['revision'] !== $expectedRevision) {
                throw new DomainException('Версия была изменена другим пользователем. Обновите страницу.');
            }
            $stmt = $this->pdo->prepare(
                "UPDATE staffing_versions SET status='cancelled',cancelled_by=:actor,cancelled_at=NOW(),cancellation_reason=:reason,updated_by=:actor,updated_at=NOW() "
                . "WHERE id=:version_id AND staffing_register_id=:register_id AND status IN ('draft','approved') AND revision=:revision"
            );
            $stmt->execute([
                'actor' => $actorId,
                'reason' => $reason,
                'version_id' => $versionId,
                'register_id' => $registerId,
                'revision' => $expectedRevision,
            ]);
            if ($stmt->rowCount() !== 1) {
                throw new DomainException('Версия не отменена из-за изменения состояния.');
            }
            $this->appendEvent(
                $registerId,
                $versionId,
                null,
                $actorId,
                'version.cancelled',
                'version',
                $versionId,
                ['status' => $version['status']],
                ['status' => 'cancelled'],
                $reason
            );
        });
    }

    public function activateVersion(int $registerId, int $versionId, int $expectedRevision, int $actorId): void
    {
        $this->transaction(function () use ($registerId, $versionId, $expectedRevision, $actorId): void {
            $register = $this->lockRegister($registerId);
            $this->assertRegisterActive($register);
            $version = $this->lockVersion($registerId, $versionId);
            if (($version['status'] ?? null) !== 'approved') {
                throw new DomainException('Активировать можно только утверждённую версию.');
            }
            if ((int) $version['revision'] !== $expectedRevision) {
                throw new DomainException('Версия была изменена другим пользователем. Обновите страницу.');
            }

            $activeStmt = $this->pdo->prepare(
                "SELECT * FROM staffing_versions WHERE staffing_register_id=:register_id AND status='active' FOR UPDATE"
            );
            $activeStmt->execute(['register_id' => $registerId]);
            $active = $activeStmt->fetch();
            if (is_array($active)) {
                if ((string) $active['effective_from'] >= (string) $version['effective_from']) {
                    throw new DomainException('Дата новой версии должна быть позже даты действующей версии.');
                }
                $supersede = $this->pdo->prepare(
                    "UPDATE staffing_versions SET status='superseded',effective_to=:effective_to,updated_by=:actor,updated_at=NOW() "
                    . "WHERE id=:id AND status='active'"
                );
                $supersede->execute([
                    'effective_to' => $version['effective_from'],
                    'actor' => $actorId,
                    'id' => (int) $active['id'],
                ]);
                if ($supersede->rowCount() !== 1) {
                    throw new DomainException('Действующая версия не переведена в историческое состояние.');
                }
                $this->appendEvent(
                    $registerId,
                    (int) $active['id'],
                    null,
                    $actorId,
                    'version.superseded',
                    'version',
                    (int) $active['id'],
                    ['status' => 'active', 'effective_to' => null],
                    ['status' => 'superseded', 'effective_to' => $version['effective_from']]
                );
            }

            $activate = $this->pdo->prepare(
                "UPDATE staffing_versions SET status='active',activated_by=:actor,activated_at=NOW(),updated_by=:actor,updated_at=NOW() "
                . "WHERE id=:version_id AND staffing_register_id=:register_id AND status='approved' AND revision=:revision"
            );
            $activate->execute([
                'actor' => $actorId,
                'version_id' => $versionId,
                'register_id' => $registerId,
                'revision' => $expectedRevision,
            ]);
            if ($activate->rowCount() !== 1) {
                throw new DomainException('Версия не активирована из-за изменения состояния.');
            }
            $this->appendEvent(
                $registerId,
                $versionId,
                null,
                $actorId,
                'version.activated',
                'version',
                $versionId,
                ['status' => 'approved'],
                ['status' => 'active']
            );
        });
    }
}
