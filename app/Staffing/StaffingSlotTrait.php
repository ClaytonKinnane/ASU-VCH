<?php

declare(strict_types=1);

trait StaffingSlotTrait
{
    public function createSlot(int $registerId, int $versionId, array $input, int $actorId): int
    {
        $expectedRevision = $this->positiveInt($input['expected_revision'] ?? null, 'Некорректная ревизия версии.');
        $normalized = $this->normalizeSlotInput($input);
        $requirements = $this->normalizeVusInput($input['vus_requirements'] ?? []);

        return $this->transaction(function () use (
            $registerId,
            $versionId,
            $expectedRevision,
            $normalized,
            $requirements,
            $actorId
        ): int {
            $register = $this->lockRegister($registerId);
            $this->assertRegisterActive($register);
            $version = $this->lockVersion($registerId, $versionId);
            $this->assertDraft($version, $expectedRevision);

            $identity = $this->pdo->prepare(
                'INSERT INTO staffing_slot_identities (staffing_register_id,created_by,created_at) '
                . 'VALUES (:register_id,:actor,NOW())'
            );
            $identity->execute(['register_id' => $registerId, 'actor' => $actorId]);
            $identityId = (int) $this->pdo->lastInsertId();

            $insert = $this->pdo->prepare(
                'INSERT INTO staffing_slots '
                . '(staffing_register_id,staffing_version_id,staffing_slot_identity_id,organizational_structure_id,organizational_structure_version_id,'
                . 'organizational_structure_element_id,position_catalog_version_id,position_type_id,position_variant_id,rank_catalog_version_id,vus_catalog_version_id,'
                . 'minimum_rank_id,maximum_rank_id,preferred_rank_id,internal_code,display_name,normative_state,note,sort_order,created_by,created_at,updated_by,updated_at) '
                . 'VALUES (:register_id,:version_id,:identity_id,:structure_id,:organization_version_id,:element_id,:position_catalog_id,:position_type_id,'
                . ':position_variant_id,:rank_catalog_id,:vus_catalog_id,:minimum_rank_id,:maximum_rank_id,:preferred_rank_id,:internal_code,:display_name,'
                . ':normative_state,:note,:sort_order,:created_actor,NOW(),:updated_actor,NOW())'
            );
            $insert->execute([
                'register_id' => $registerId,
                'version_id' => $versionId,
                'identity_id' => $identityId,
                'structure_id' => (int) $version['organizational_structure_id'],
                'organization_version_id' => (int) $version['organizational_structure_version_id'],
                'element_id' => $normalized['organizational_structure_element_id'],
                'position_catalog_id' => (int) $version['position_catalog_version_id'],
                'position_type_id' => $normalized['position_type_id'],
                'position_variant_id' => $normalized['position_variant_id'],
                'rank_catalog_id' => (int) $version['rank_catalog_version_id'],
                'vus_catalog_id' => (int) $version['vus_catalog_version_id'],
                'minimum_rank_id' => $normalized['minimum_rank_id'],
                'maximum_rank_id' => $normalized['maximum_rank_id'],
                'preferred_rank_id' => $normalized['preferred_rank_id'],
                'internal_code' => $normalized['internal_code'],
                'display_name' => $normalized['display_name'],
                'normative_state' => $normalized['normative_state'],
                'note' => $normalized['note'],
                'sort_order' => $normalized['sort_order'],
                'created_actor' => $actorId,
                'updated_actor' => $actorId,
            ]);
            $slotId = (int) $this->pdo->lastInsertId();
            $this->replaceVusRequirements(
                $registerId,
                $versionId,
                $slotId,
                (int) $version['vus_catalog_version_id'],
                $requirements,
                $actorId
            );
            $this->incrementVersionRevision($registerId, $versionId, $expectedRevision, $actorId);
            $this->appendEvent(
                $registerId,
                $versionId,
                $identityId,
                $actorId,
                'slot.created',
                'slot',
                $slotId,
                null,
                $normalized + ['vus_requirements' => $requirements]
            );
            return $slotId;
        });
    }

    public function updateSlot(int $registerId, int $versionId, int $slotId, array $input, int $actorId): void
    {
        $expectedRevision = $this->positiveInt($input['expected_revision'] ?? null, 'Некорректная ревизия версии.');
        $normalized = $this->normalizeSlotInput($input);
        $requirements = $this->normalizeVusInput($input['vus_requirements'] ?? []);

        $this->transaction(function () use (
            $registerId,
            $versionId,
            $slotId,
            $expectedRevision,
            $normalized,
            $requirements,
            $actorId
        ): void {
            $register = $this->lockRegister($registerId);
            $this->assertRegisterActive($register);
            $version = $this->lockVersion($registerId, $versionId);
            $this->assertDraft($version, $expectedRevision);

            $slotStmt = $this->pdo->prepare(
                'SELECT * FROM staffing_slots WHERE id=:slot_id AND staffing_register_id=:register_id '
                . 'AND staffing_version_id=:version_id FOR UPDATE'
            );
            $slotStmt->execute([
                'slot_id' => $slotId,
                'register_id' => $registerId,
                'version_id' => $versionId,
            ]);
            $slot = $slotStmt->fetch();
            if (!is_array($slot)) {
                throw new DomainException('Штатная позиция не найдена.');
            }

            $update = $this->pdo->prepare(
                'UPDATE staffing_slots SET organizational_structure_element_id=:element_id,position_type_id=:position_type_id,'
                . 'position_variant_id=:position_variant_id,minimum_rank_id=:minimum_rank_id,maximum_rank_id=:maximum_rank_id,'
                . 'preferred_rank_id=:preferred_rank_id,internal_code=:internal_code,display_name=:display_name,normative_state=:normative_state,'
                . 'note=:note,sort_order=:sort_order,updated_by=:actor,updated_at=NOW() WHERE id=:slot_id'
            );
            $update->execute([
                'element_id' => $normalized['organizational_structure_element_id'],
                'position_type_id' => $normalized['position_type_id'],
                'position_variant_id' => $normalized['position_variant_id'],
                'minimum_rank_id' => $normalized['minimum_rank_id'],
                'maximum_rank_id' => $normalized['maximum_rank_id'],
                'preferred_rank_id' => $normalized['preferred_rank_id'],
                'internal_code' => $normalized['internal_code'],
                'display_name' => $normalized['display_name'],
                'normative_state' => $normalized['normative_state'],
                'note' => $normalized['note'],
                'sort_order' => $normalized['sort_order'],
                'actor' => $actorId,
                'slot_id' => $slotId,
            ]);
            $this->replaceVusRequirements(
                $registerId,
                $versionId,
                $slotId,
                (int) $version['vus_catalog_version_id'],
                $requirements,
                $actorId
            );
            $this->incrementVersionRevision($registerId, $versionId, $expectedRevision, $actorId);
            $this->appendEvent(
                $registerId,
                $versionId,
                (int) $slot['staffing_slot_identity_id'],
                $actorId,
                'slot.updated',
                'slot',
                $slotId,
                $slot,
                $normalized + ['vus_requirements' => $requirements]
            );
        });
    }

    public function removeSlot(int $registerId, int $versionId, int $slotId, int $expectedRevision, string $reason, int $actorId): void
    {
        $reason = $this->requiredString($reason, 1000, 'Укажите основание удаления позиции из черновика.');
        $this->transaction(function () use ($registerId, $versionId, $slotId, $expectedRevision, $reason, $actorId): void {
            $register = $this->lockRegister($registerId);
            $this->assertRegisterActive($register);
            $version = $this->lockVersion($registerId, $versionId);
            $this->assertDraft($version, $expectedRevision);
            $slotStmt = $this->pdo->prepare(
                'SELECT * FROM staffing_slots WHERE id=:slot_id AND staffing_register_id=:register_id '
                . 'AND staffing_version_id=:version_id FOR UPDATE'
            );
            $slotStmt->execute([
                'slot_id' => $slotId,
                'register_id' => $registerId,
                'version_id' => $versionId,
            ]);
            $slot = $slotStmt->fetch();
            if (!is_array($slot)) {
                throw new DomainException('Штатная позиция не найдена.');
            }
            $deleteRequirements = $this->pdo->prepare(
                'DELETE FROM staffing_slot_vus_requirements WHERE staffing_slot_id=:slot_id'
            );
            $deleteRequirements->execute(['slot_id' => $slotId]);
            $deleteSlot = $this->pdo->prepare('DELETE FROM staffing_slots WHERE id=:slot_id');
            $deleteSlot->execute(['slot_id' => $slotId]);
            $this->incrementVersionRevision($registerId, $versionId, $expectedRevision, $actorId);
            $this->appendEvent(
                $registerId,
                $versionId,
                (int) $slot['staffing_slot_identity_id'],
                $actorId,
                'slot.removed',
                'slot',
                $slotId,
                $slot,
                null,
                $reason
            );
        });
    }

    /** @return array<string,mixed> */
    private function normalizeSlotInput(array $input): array
    {
        return [
            'organizational_structure_element_id' => $this->positiveInt(
                $input['organizational_structure_element_id'] ?? null,
                'Выберите организационный элемент.'
            ),
            'position_type_id' => $this->positiveInt($input['position_type_id'] ?? null, 'Выберите тип воинской должности.'),
            'position_variant_id' => $this->nullablePositiveInt($input['position_variant_id'] ?? null, 'Некорректный вариант должности.'),
            'minimum_rank_id' => $this->nullablePositiveInt($input['minimum_rank_id'] ?? null, 'Некорректное минимальное звание.'),
            'maximum_rank_id' => $this->nullablePositiveInt($input['maximum_rank_id'] ?? null, 'Некорректное максимальное звание.'),
            'preferred_rank_id' => $this->nullablePositiveInt($input['preferred_rank_id'] ?? null, 'Некорректное предпочтительное звание.'),
            'internal_code' => $this->nullableString($input['internal_code'] ?? null, 64, 'Некорректный внутренний код позиции.'),
            'display_name' => $this->requiredString($input['display_name'] ?? null, 255, 'Укажите наименование штатной позиции.'),
            'normative_state' => $this->enum(
                $input['normative_state'] ?? null,
                ['active', 'suspended', 'closed'],
                'Некорректное нормативное состояние.'
            ),
            'note' => $this->nullableString($input['note'] ?? null, 5000, 'Примечание слишком длинное.'),
            'sort_order' => $this->positiveInt($input['sort_order'] ?? null, 'Укажите положительный порядок позиции.'),
        ];
    }

    /** @return list<array{public_disclosure_id:int,requirement_role:string,sort_order:int}> */
    private function normalizeVusInput(mixed $value): array
    {
        if ($value === null || $value === '') {
            return [];
        }
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            if (!is_array($decoded)) {
                throw new DomainException('Некорректный список требований ВУС.');
            }
            $value = $decoded;
        }
        if (!is_array($value)) {
            throw new DomainException('Некорректный список требований ВУС.');
        }
        $result = [];
        foreach ($value as $index => $row) {
            if (!is_array($row)) {
                throw new DomainException('Некорректный элемент требования ВУС.');
            }
            $result[] = [
                'public_disclosure_id' => $this->positiveInt($row['public_disclosure_id'] ?? null, 'Некорректный идентификатор ВУС.'),
                'requirement_role' => $this->enum($row['requirement_role'] ?? null, ['required', 'allowed', 'preferred'], 'Некорректная роль ВУС.'),
                'sort_order' => $this->positiveInt($row['sort_order'] ?? ($index + 1), 'Некорректный порядок ВУС.'),
            ];
        }
        return $result;
    }
}
