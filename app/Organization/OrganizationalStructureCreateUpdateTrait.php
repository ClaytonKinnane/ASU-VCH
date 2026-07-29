<?php

declare(strict_types=1);

trait OrganizationalStructureCreateUpdateTrait
{
    public function createStructure(
        string $code,
        string $displayName,
        ?string $shortName,
        int $rootTypeId,
        string $rootName,
        ?string $rootShortName,
        string $changeReason,
        int $actorUserId
    ): int {
        $code = strtolower(trim($code));
        $displayName = $this->requiredText($displayName, 255, 'Укажите название структуры.');
        $shortName = $this->nullableText($shortName, 128);
        $rootName = $this->requiredText($rootName, 255, 'Укажите официальное наименование воинской части.');
        $rootShortName = $this->nullableText($rootShortName, 128);
        $changeReason = $this->requiredText($changeReason, 1000, 'Укажите основание создания структуры.');
        if (preg_match('/\A[a-z0-9][a-z0-9-]{1,63}\z/D', $code) !== 1) {
            throw new DomainException('Код должен содержать 2–64 символа: строчные латинские буквы, цифры и дефис.');
        }

        return $this->transaction(function () use (
            $code,
            $displayName,
            $shortName,
            $rootTypeId,
            $rootName,
            $rootShortName,
            $changeReason,
            $actorUserId
        ): int {
            $catalogVersionId = $this->currentCatalogVersionId();
            $this->assertTypeInCatalog($rootTypeId, $catalogVersionId, 'military-unit');
            $now = $this->now();

            $stmt = $this->pdo->prepare(
                'INSERT INTO organizational_structures '
                . '(code, display_name, short_name, status, created_by, created_at, updated_by, updated_at) '
                . "VALUES (:code, :display_name, :short_name, 'active', :created_by, :created_at, :updated_by, :updated_at)"
            );
            $stmt->execute([
                'code' => $code,
                'display_name' => $displayName,
                'short_name' => $shortName,
                'created_by' => $actorUserId,
                'created_at' => $now,
                'updated_by' => $actorUserId,
                'updated_at' => $now,
            ]);
            $structureId = (int) $this->pdo->lastInsertId();

            $elementStmt = $this->pdo->prepare(
                'INSERT INTO organizational_structure_elements (organizational_structure_id, created_by, created_at) '
                . 'VALUES (:structure_id, :created_by, :created_at)'
            );
            $elementStmt->execute(['structure_id' => $structureId, 'created_by' => $actorUserId, 'created_at' => $now]);
            $elementId = (int) $this->pdo->lastInsertId();

            $versionStmt = $this->pdo->prepare(
                'INSERT INTO organizational_structure_versions '
                . '(organizational_structure_id, based_on_version_id, catalog_version_id, version_number, status, change_reason, revision, created_by, created_at, updated_by, updated_at) '
                . "VALUES (:structure_id, NULL, :catalog_version_id, 1, 'draft', :change_reason, 1, :created_by, :created_at, :updated_by, :updated_at)"
            );
            $versionStmt->execute([
                'structure_id' => $structureId,
                'catalog_version_id' => $catalogVersionId,
                'change_reason' => $changeReason,
                'created_by' => $actorUserId,
                'created_at' => $now,
                'updated_by' => $actorUserId,
                'updated_at' => $now,
            ]);
            $versionId = (int) $this->pdo->lastInsertId();

            $nodeStmt = $this->pdo->prepare(
                'INSERT INTO organizational_structure_nodes '
                . '(organizational_structure_id, structure_version_id, catalog_version_id, organizational_structure_element_id, parent_node_id, organizational_element_type_id, internal_code, name, short_name, sort_order, note, created_by, created_at, updated_by, updated_at) '
                . 'VALUES (:structure_id, :version_id, :catalog_version_id, :element_id, NULL, :type_id, NULL, :name, :short_name, 10, NULL, :created_by, :created_at, :updated_by, :updated_at)'
            );
            $nodeStmt->execute([
                'structure_id' => $structureId,
                'version_id' => $versionId,
                'catalog_version_id' => $catalogVersionId,
                'element_id' => $elementId,
                'type_id' => $rootTypeId,
                'name' => $rootName,
                'short_name' => $rootShortName,
                'created_by' => $actorUserId,
                'created_at' => $now,
                'updated_by' => $actorUserId,
                'updated_at' => $now,
            ]);

            $this->recordEvent($structureId, $versionId, $elementId, $actorUserId, 'structure.created', null, [
                'code' => $code,
                'display_name' => $displayName,
                'version_number' => 1,
                'root_name' => $rootName,
            ], $changeReason);

            return $structureId;
        });
    }

    public function updateStructure(int $structureId, string $displayName, ?string $shortName, int $actorUserId): void
    {
        $displayName = $this->requiredText($displayName, 255, 'Укажите название структуры.');
        $shortName = $this->nullableText($shortName, 128);

        $this->transaction(function () use ($structureId, $displayName, $shortName, $actorUserId): void {
            $structure = $this->lockStructure($structureId);
            if ((string) $structure['status'] !== 'active') {
                throw new DomainException('Архивная структура не может быть изменена.');
            }
            $before = [
                'display_name' => (string) $structure['display_name'],
                'short_name' => $structure['short_name'],
            ];
            if ($before['display_name'] === $displayName && $before['short_name'] === $shortName) {
                return;
            }
            $now = $this->now();
            $stmt = $this->pdo->prepare(
                'UPDATE organizational_structures SET display_name = :display_name, short_name = :short_name, '
                . 'updated_by = :actor, updated_at = :updated_at WHERE id = :id'
            );
            $stmt->execute([
                'display_name' => $displayName,
                'short_name' => $shortName,
                'actor' => $actorUserId,
                'updated_at' => $now,
                'id' => $structureId,
            ]);
            $this->recordEvent($structureId, null, null, $actorUserId, 'structure.updated', $before, [
                'display_name' => $displayName,
                'short_name' => $shortName,
            ]);
        });
    }

    public function createDraft(int $structureId, string $changeReason, int $actorUserId): int
    {
        $changeReason = $this->requiredText($changeReason, 1000, 'Укажите основание новой версии.');

        return $this->transaction(function () use ($structureId, $changeReason, $actorUserId): int {
            $structure = $this->lockStructure($structureId);
            if ((string) $structure['status'] !== 'active') {
                throw new DomainException('Архивная структура не может быть изменена.');
            }
            if ($this->pendingVersionExists($structureId)) {
                throw new DomainException('У структуры уже есть черновая или утверждённая версия.');
            }

            $sourceVersion = $this->lockActiveVersion($structureId) ?? $this->lockLatestCancelledVersion($structureId);
            if ($sourceVersion === null) {
                throw new DomainException('Версия-основание для нового черновика не найдена.');
            }

            $nextNumberStmt = $this->pdo->prepare(
                'SELECT COALESCE(MAX(version_number), 0) + 1 FROM organizational_structure_versions WHERE organizational_structure_id = :structure_id'
            );
            $nextNumberStmt->execute(['structure_id' => $structureId]);
            $nextNumber = (int) $nextNumberStmt->fetchColumn();
            $now = $this->now();

            $insert = $this->pdo->prepare(
                'INSERT INTO organizational_structure_versions '
                . '(organizational_structure_id, based_on_version_id, catalog_version_id, version_number, status, change_reason, revision, created_by, created_at, updated_by, updated_at) '
                . "VALUES (:structure_id, :based_on, :catalog, :version_number, 'draft', :reason, 1, :actor, :created_at, :updated_by, :updated_at)"
            );
            $insert->execute([
                'structure_id' => $structureId,
                'based_on' => (int) $sourceVersion['id'],
                'catalog' => (int) $sourceVersion['catalog_version_id'],
                'version_number' => $nextNumber,
                'reason' => $changeReason,
                'actor' => $actorUserId,
                'created_at' => $now,
                'updated_by' => $actorUserId,
                'updated_at' => $now,
            ]);
            $newVersionId = (int) $this->pdo->lastInsertId();

            $docCopy = $this->pdo->prepare(
                'INSERT INTO organizational_structure_version_documents '
                . '(structure_version_id, organizational_structure_id, document_id, document_role, sort_order, created_by, created_at) '
                . 'SELECT :new_version_id, organizational_structure_id, document_id, document_role, sort_order, :actor, :created_at '
                . 'FROM organizational_structure_version_documents WHERE structure_version_id = :source_version_id ORDER BY sort_order'
            );
            $docCopy->execute([
                'new_version_id' => $newVersionId,
                'actor' => $actorUserId,
                'created_at' => $now,
                'source_version_id' => (int) $sourceVersion['id'],
            ]);

            $nodes = $this->sourceNodesInTreeOrder((int) $sourceVersion['id']);
            if ($nodes === []) {
                throw new DomainException('Действующая версия не содержит корневого элемента.');
            }
            $nodeMap = [];
            $nodeInsert = $this->pdo->prepare(
                'INSERT INTO organizational_structure_nodes '
                . '(organizational_structure_id, structure_version_id, catalog_version_id, organizational_structure_element_id, parent_node_id, organizational_element_type_id, internal_code, name, short_name, sort_order, note, created_by, created_at, updated_by, updated_at) '
                . 'VALUES (:structure_id, :version_id, :catalog, :element_id, :parent_id, :type_id, :internal_code, :name, :short_name, :sort_order, :note, :actor, :created_at, :updated_by, :updated_at)'
            );
            foreach ($nodes as $node) {
                $oldParentId = $node['parent_node_id'] !== null ? (int) $node['parent_node_id'] : null;
                $newParentId = $oldParentId !== null ? ($nodeMap[$oldParentId] ?? null) : null;
                if ($oldParentId !== null && $newParentId === null) {
                    throw new DomainException('Нарушена последовательность копирования дерева.');
                }
                $nodeInsert->execute([
                    'structure_id' => $structureId,
                    'version_id' => $newVersionId,
                    'catalog' => (int) $sourceVersion['catalog_version_id'],
                    'element_id' => (int) $node['organizational_structure_element_id'],
                    'parent_id' => $newParentId,
                    'type_id' => (int) $node['organizational_element_type_id'],
                    'internal_code' => $node['internal_code'],
                    'name' => (string) $node['name'],
                    'short_name' => $node['short_name'],
                    'sort_order' => (int) $node['sort_order'],
                    'note' => $node['note'],
                    'actor' => $actorUserId,
                    'created_at' => $now,
                    'updated_by' => $actorUserId,
                    'updated_at' => $now,
                ]);
                $nodeMap[(int) $node['id']] = (int) $this->pdo->lastInsertId();
            }

            $this->recordEvent($structureId, $newVersionId, null, $actorUserId, 'version.draft_created', null, [
                'based_on_version_id' => (int) $sourceVersion['id'],
                'version_number' => $nextNumber,
                'node_count' => count($nodes),
            ], $changeReason);

            return $newVersionId;
        });
    }

}
