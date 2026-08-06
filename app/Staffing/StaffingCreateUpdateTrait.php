<?php

declare(strict_types=1);

trait StaffingCreateUpdateTrait
{
    public function createRegister(array $input, int $actorId): int
    {
        $code = $this->requiredString($input['code'] ?? null, 64, 'Укажите корректный код штатного реестра.');
        if (preg_match('/\A[a-z0-9][a-z0-9._-]{1,63}\z/D', $code) !== 1) {
            throw new DomainException('Код должен содержать только строчные латинские буквы, цифры, точки, дефисы и подчёркивания.');
        }
        $name = $this->requiredString($input['name'] ?? null, 255, 'Укажите название штатного реестра.');
        $structureId = $this->positiveInt($input['organizational_structure_id'] ?? null, 'Выберите организационную структуру.');
        $note = $this->nullableString($input['note'] ?? null, 5000, 'Примечание слишком длинное.');

        return $this->transaction(function () use ($code, $name, $structureId, $note, $actorId): int {
            $structure = $this->pdo->prepare(
                "SELECT id FROM organizational_structures WHERE id=:id AND status='active' FOR UPDATE"
            );
            $structure->execute(['id' => $structureId]);
            if ($structure->fetchColumn() === false) {
                throw new DomainException('Организационная структура не найдена или архивирована.');
            }
            $stmt = $this->pdo->prepare(
                'INSERT INTO staffing_registers '
                . '(code,name,organizational_structure_id,note,status,revision,created_by,created_at,updated_by,updated_at) '
                . "VALUES (:code,:name,:structure_id,:note,'active',1,:actor,NOW(),:actor,NOW())"
            );
            $stmt->execute([
                'code' => $code,
                'name' => $name,
                'structure_id' => $structureId,
                'note' => $note,
                'actor' => $actorId,
            ]);
            $id = (int) $this->pdo->lastInsertId();
            $this->appendEvent(
                $id,
                null,
                null,
                $actorId,
                'register.created',
                'register',
                $id,
                null,
                ['code' => $code, 'name' => $name, 'organizational_structure_id' => $structureId]
            );
            return $id;
        });
    }

    public function updateRegister(int $registerId, array $input, int $actorId): void
    {
        $name = $this->requiredString($input['name'] ?? null, 255, 'Укажите название штатного реестра.');
        $note = $this->nullableString($input['note'] ?? null, 5000, 'Примечание слишком длинное.');
        $expectedRevision = $this->positiveInt($input['expected_revision'] ?? null, 'Некорректная версия карточки.');

        $this->transaction(function () use ($registerId, $name, $note, $expectedRevision, $actorId): void {
            $register = $this->lockRegister($registerId);
            $this->assertRegisterActive($register);
            if ((int) $register['revision'] !== $expectedRevision) {
                throw new DomainException('Карточка была изменена другим пользователем. Обновите страницу.');
            }
            $stmt = $this->pdo->prepare(
                'UPDATE staffing_registers SET name=:name,note=:note,revision=revision+1,updated_by=:actor,updated_at=NOW() '
                . "WHERE id=:id AND status='active' AND revision=:revision"
            );
            $stmt->execute([
                'name' => $name,
                'note' => $note,
                'actor' => $actorId,
                'id' => $registerId,
                'revision' => $expectedRevision,
            ]);
            if ($stmt->rowCount() !== 1) {
                throw new DomainException('Карточка была изменена другим пользователем. Обновите страницу.');
            }
            $this->appendEvent(
                $registerId,
                null,
                null,
                $actorId,
                'register.updated',
                'register',
                $registerId,
                ['name' => $register['name'], 'note' => $register['note']],
                ['name' => $name, 'note' => $note]
            );
        });
    }

    public function archiveRegister(int $registerId, int $expectedRevision, string $reason, int $actorId): void
    {
        $reason = $this->requiredString($reason, 1000, 'Укажите основание архивирования.');
        $this->transaction(function () use ($registerId, $expectedRevision, $reason, $actorId): void {
            $register = $this->lockRegister($registerId);
            $this->assertRegisterActive($register);
            if ((int) $register['revision'] !== $expectedRevision) {
                throw new DomainException('Карточка была изменена другим пользователем. Обновите страницу.');
            }
            $stmt = $this->pdo->prepare(
                "UPDATE staffing_registers SET status='archived',revision=revision+1,archived_by=:actor,archived_at=NOW(),archive_reason=:reason,updated_by=:actor,updated_at=NOW() "
                . "WHERE id=:id AND status='active' AND revision=:revision"
            );
            $stmt->execute([
                'actor' => $actorId,
                'reason' => $reason,
                'id' => $registerId,
                'revision' => $expectedRevision,
            ]);
            if ($stmt->rowCount() !== 1) {
                throw new DomainException('Штатный реестр не архивирован.');
            }
            $this->appendEvent($registerId, null, null, $actorId, 'register.archived', 'register', $registerId, ['status' => 'active'], ['status' => 'archived'], $reason);
        });
    }

    public function restoreRegister(int $registerId, int $expectedRevision, string $reason, int $actorId): void
    {
        $reason = $this->requiredString($reason, 1000, 'Укажите основание восстановления.');
        $this->transaction(function () use ($registerId, $expectedRevision, $reason, $actorId): void {
            $register = $this->lockRegister($registerId);
            if (($register['status'] ?? null) !== 'archived') {
                throw new DomainException('Штатный реестр не находится в архиве.');
            }
            if ((int) $register['revision'] !== $expectedRevision) {
                throw new DomainException('Карточка была изменена другим пользователем. Обновите страницу.');
            }
            $stmt = $this->pdo->prepare(
                "UPDATE staffing_registers SET status='active',revision=revision+1,restored_by=:actor,restored_at=NOW(),restore_reason=:reason,updated_by=:actor,updated_at=NOW() "
                . "WHERE id=:id AND status='archived' AND revision=:revision"
            );
            $stmt->execute([
                'actor' => $actorId,
                'reason' => $reason,
                'id' => $registerId,
                'revision' => $expectedRevision,
            ]);
            if ($stmt->rowCount() !== 1) {
                throw new DomainException('Штатный реестр не восстановлен.');
            }
            $this->appendEvent($registerId, null, null, $actorId, 'register.restored', 'register', $registerId, ['status' => 'archived'], ['status' => 'active'], $reason);
        });
    }

    public function createVersion(int $registerId, array $input, int $actorId): int
    {
        $organizationVersionId = $this->positiveInt($input['organizational_structure_version_id'] ?? null, 'Выберите версию организационной структуры.');
        $basedOnVersionId = $this->nullablePositiveInt($input['based_on_version_id'] ?? null, 'Некорректная базовая версия.');
        $label = $this->requiredString($input['version_label'] ?? null, 255, 'Укажите обозначение версии.');
        $effectiveFrom = $this->date($input['effective_from'] ?? null, 'Укажите корректную дату начала действия.');
        $reason = $this->requiredString($input['change_reason'] ?? null, 1000, 'Укажите основание создания версии.');

        return $this->transaction(function () use (
            $registerId,
            $organizationVersionId,
            $basedOnVersionId,
            $label,
            $effectiveFrom,
            $reason,
            $actorId
        ): int {
            $register = $this->lockRegister($registerId);
            $this->assertRegisterActive($register);
            $this->organizationVersion((int) $register['organizational_structure_id'], $organizationVersionId);

            $pending = $this->pdo->prepare(
                "SELECT id FROM staffing_versions WHERE staffing_register_id=:id AND status IN ('draft','approved') FOR UPDATE"
            );
            $pending->execute(['id' => $registerId]);
            if ($pending->fetchColumn() !== false) {
                throw new DomainException('У реестра уже существует незавершённая версия.');
            }

            $activeStmt = $this->pdo->prepare(
                "SELECT * FROM staffing_versions WHERE staffing_register_id=:id AND status='active' FOR UPDATE"
            );
            $activeStmt->execute(['id' => $registerId]);
            $active = $activeStmt->fetch();

            $basedOn = null;
            if (is_array($active)) {
                if ($basedOnVersionId === null || $basedOnVersionId !== (int) $active['id']) {
                    throw new DomainException(
                        'При наличии действующей версии новый черновик должен копировать именно её; перенос между версиями справочников отложен.'
                    );
                }
                $basedOn = $active;
                if ((int) $basedOn['organizational_structure_version_id'] !== $organizationVersionId) {
                    throw new DomainException('В v1 копирование требует ту же версию организационной структуры.');
                }
                $positionCatalogId = (int) $basedOn['position_catalog_version_id'];
                $rankCatalogId = (int) $basedOn['rank_catalog_version_id'];
                $vusCatalogId = (int) $basedOn['vus_catalog_version_id'];
            } else {
                if ($basedOnVersionId !== null) {
                    throw new DomainException('Без действующей версии разрешён только пустой первоначальный черновик.');
                }
                $positionCatalog = $this->currentPositionCatalog();
                $vusCatalog = $this->currentVusCatalog();
                $positionCatalogId = (int) $positionCatalog['id'];
                $rankCatalogId = (int) $positionCatalog['rank_catalog_version_id'];
                $vusCatalogId = (int) $vusCatalog['id'];
            }

            $numberStmt = $this->pdo->prepare(
                'SELECT COALESCE(MAX(version_number),0)+1 FROM staffing_versions WHERE staffing_register_id=:id'
            );
            $numberStmt->execute(['id' => $registerId]);
            $versionNumber = (int) $numberStmt->fetchColumn();

            $insert = $this->pdo->prepare(
                'INSERT INTO staffing_versions '
                . '(staffing_register_id,based_on_version_id,organizational_structure_id,organizational_structure_version_id,'
                . 'position_catalog_version_id,rank_catalog_version_id,vus_catalog_version_id,version_number,version_label,status,'
                . 'effective_from,effective_to,change_reason,revision,created_by,created_at,updated_by,updated_at) '
                . "VALUES (:register_id,:based_on,:structure_id,:organization_version_id,:position_catalog_id,:rank_catalog_id,:vus_catalog_id,:version_number,:label,'draft',:effective_from,NULL,:reason,1,:actor,NOW(),:actor,NOW())"
            );
            $insert->execute([
                'register_id' => $registerId,
                'based_on' => $basedOnVersionId,
                'structure_id' => (int) $register['organizational_structure_id'],
                'organization_version_id' => $organizationVersionId,
                'position_catalog_id' => $positionCatalogId,
                'rank_catalog_id' => $rankCatalogId,
                'vus_catalog_id' => $vusCatalogId,
                'version_number' => $versionNumber,
                'label' => $label,
                'effective_from' => $effectiveFrom,
                'reason' => $reason,
                'actor' => $actorId,
            ]);
            $versionId = (int) $this->pdo->lastInsertId();

            if ($basedOn !== null) {
                $copyDocuments = $this->pdo->prepare(
                    'INSERT INTO staffing_version_documents '
                    . '(staffing_version_id,staffing_register_id,document_id,document_role,sort_order,created_by,created_at) '
                    . 'SELECT :new_version,staffing_register_id,document_id,document_role,sort_order,:actor,NOW() '
                    . 'FROM staffing_version_documents WHERE staffing_version_id=:source_version'
                );
                $copyDocuments->execute([
                    'new_version' => $versionId,
                    'actor' => $actorId,
                    'source_version' => $basedOnVersionId,
                ]);

                $copySlots = $this->pdo->prepare(
                    'INSERT INTO staffing_slots '
                    . '(staffing_register_id,staffing_version_id,staffing_slot_identity_id,organizational_structure_id,organizational_structure_version_id,'
                    . 'organizational_structure_element_id,position_catalog_version_id,position_type_id,position_variant_id,rank_catalog_version_id,vus_catalog_version_id,'
                    . 'minimum_rank_id,maximum_rank_id,preferred_rank_id,internal_code,display_name,normative_state,note,sort_order,created_by,created_at,updated_by,updated_at) '
                    . 'SELECT staffing_register_id,:new_version,staffing_slot_identity_id,organizational_structure_id,organizational_structure_version_id,'
                    . 'organizational_structure_element_id,position_catalog_version_id,position_type_id,position_variant_id,rank_catalog_version_id,vus_catalog_version_id,'
                    . 'minimum_rank_id,maximum_rank_id,preferred_rank_id,internal_code,display_name,normative_state,note,sort_order,:actor,NOW(),:actor,NOW() '
                    . 'FROM staffing_slots WHERE staffing_version_id=:source_version ORDER BY id'
                );
                $copySlots->execute([
                    'new_version' => $versionId,
                    'actor' => $actorId,
                    'source_version' => $basedOnVersionId,
                ]);

                $copyVus = $this->pdo->prepare(
                    'INSERT INTO staffing_slot_vus_requirements '
                    . '(staffing_version_id,staffing_register_id,staffing_slot_id,vus_catalog_version_id,public_disclosure_id,requirement_role,sort_order,created_by,created_at) '
                    . 'SELECT :new_version,new_slot.staffing_register_id,new_slot.id,old_req.vus_catalog_version_id,old_req.public_disclosure_id,'
                    . 'old_req.requirement_role,old_req.sort_order,:actor,NOW() '
                    . 'FROM staffing_slot_vus_requirements old_req '
                    . 'JOIN staffing_slots old_slot ON old_slot.id=old_req.staffing_slot_id '
                    . 'JOIN staffing_slots new_slot ON new_slot.staffing_version_id=:new_version '
                    . 'AND new_slot.staffing_slot_identity_id=old_slot.staffing_slot_identity_id '
                    . 'WHERE old_req.staffing_version_id=:source_version'
                );
                $copyVus->execute([
                    'new_version' => $versionId,
                    'actor' => $actorId,
                    'source_version' => $basedOnVersionId,
                ]);
            }

            $this->appendEvent(
                $registerId,
                $versionId,
                null,
                $actorId,
                'version.created',
                'version',
                $versionId,
                null,
                [
                    'version_number' => $versionNumber,
                    'version_label' => $label,
                    'based_on_version_id' => $basedOnVersionId,
                    'organizational_structure_version_id' => $organizationVersionId,
                    'position_catalog_version_id' => $positionCatalogId,
                    'rank_catalog_version_id' => $rankCatalogId,
                    'vus_catalog_version_id' => $vusCatalogId,
                ],
                $reason
            );
            return $versionId;
        });
    }
}
