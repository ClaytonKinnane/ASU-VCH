<?php

declare(strict_types=1);

trait OrganizationalStructureNodeTrait
{
    public function addNode(
        int $versionId,
        int $parentNodeId,
        int $typeId,
        ?string $internalCode,
        string $name,
        ?string $shortName,
        ?string $note,
        int $expectedRevision,
        int $actorUserId
    ): int {
        $internalCode = $this->nullableCode($internalCode);
        $name = $this->requiredText($name, 255, 'Укажите наименование элемента.');
        $shortName = $this->nullableText($shortName, 128);
        $note = $this->nullableText($note, 4000);

        return $this->transaction(function () use (
            $versionId,
            $parentNodeId,
            $typeId,
            $internalCode,
            $name,
            $shortName,
            $note,
            $expectedRevision,
            $actorUserId
        ): int {
            $version = $this->lockDraftVersion($versionId, $expectedRevision);
            $parent = $this->lockNodeInVersion($parentNodeId, $versionId);
            $this->assertTypeInCatalog($typeId, (int) $version['catalog_version_id']);
            $now = $this->now();

            $elementStmt = $this->pdo->prepare(
                'INSERT INTO organizational_structure_elements (organizational_structure_id, created_by, created_at) '
                . 'VALUES (:structure_id, :actor, :created_at)'
            );
            $elementStmt->execute([
                'structure_id' => (int) $version['organizational_structure_id'],
                'actor' => $actorUserId,
                'created_at' => $now,
            ]);
            $elementId = (int) $this->pdo->lastInsertId();

            $orderStmt = $this->pdo->prepare(
                'SELECT COALESCE(MAX(sort_order), 0) + 10 FROM organizational_structure_nodes '
                . 'WHERE structure_version_id = :version_id AND parent_node_id = :parent_id'
            );
            $orderStmt->execute(['version_id' => $versionId, 'parent_id' => $parentNodeId]);
            $sortOrder = (int) $orderStmt->fetchColumn();

            $insert = $this->pdo->prepare(
                'INSERT INTO organizational_structure_nodes '
                . '(organizational_structure_id, structure_version_id, catalog_version_id, organizational_structure_element_id, parent_node_id, organizational_element_type_id, internal_code, name, short_name, sort_order, note, created_by, created_at, updated_by, updated_at) '
                . 'VALUES (:structure_id, :version_id, :catalog, :element_id, :parent_id, :type_id, :internal_code, :name, :short_name, :sort_order, :note, :actor, :created_at, :updated_by, :updated_at)'
            );
            $insert->execute([
                'structure_id' => (int) $version['organizational_structure_id'],
                'version_id' => $versionId,
                'catalog' => (int) $version['catalog_version_id'],
                'element_id' => $elementId,
                'parent_id' => (int) $parent['id'],
                'type_id' => $typeId,
                'internal_code' => $internalCode,
                'name' => $name,
                'short_name' => $shortName,
                'sort_order' => $sortOrder,
                'note' => $note,
                'actor' => $actorUserId,
                'created_at' => $now,
                'updated_by' => $actorUserId,
                'updated_at' => $now,
            ]);
            $nodeId = (int) $this->pdo->lastInsertId();
            $this->bumpRevision($versionId, $actorUserId);
            $this->recordEvent((int) $version['organizational_structure_id'], $versionId, $elementId, $actorUserId, 'node.created', null, [
                'node_id' => $nodeId,
                'parent_node_id' => $parentNodeId,
                'type_id' => $typeId,
                'name' => $name,
            ]);
            return $nodeId;
        });
    }

    public function updateNode(
        int $nodeId,
        int $typeId,
        ?string $internalCode,
        string $name,
        ?string $shortName,
        ?string $note,
        int $expectedRevision,
        int $actorUserId
    ): void {
        $internalCode = $this->nullableCode($internalCode);
        $name = $this->requiredText($name, 255, 'Укажите наименование элемента.');
        $shortName = $this->nullableText($shortName, 128);
        $note = $this->nullableText($note, 4000);

        $this->transaction(function () use ($nodeId, $typeId, $internalCode, $name, $shortName, $note, $expectedRevision, $actorUserId): void {
            $versionId = $this->nodeVersionId($nodeId);
            $version = $this->lockDraftVersion($versionId, $expectedRevision);
            $node = $this->lockNodeInVersion($nodeId, $versionId);
            $this->assertTypeInCatalog($typeId, (int) $version['catalog_version_id'], $node['parent_node_id'] === null ? 'military-unit' : null);
            $before = $this->nodeState($node);
            $after = [
                'node_id' => $nodeId,
                'parent_node_id' => $node['parent_node_id'] !== null ? (int) $node['parent_node_id'] : null,
                'type_id' => $typeId,
                'internal_code' => $internalCode,
                'name' => $name,
                'short_name' => $shortName,
                'sort_order' => (int) $node['sort_order'],
                'note' => $note,
            ];
            if ($before === $after) {
                return;
            }
            $now = $this->now();

            $stmt = $this->pdo->prepare(
                'UPDATE organizational_structure_nodes SET organizational_element_type_id = :type_id, internal_code = :internal_code, '
                . 'name = :name, short_name = :short_name, note = :note, updated_by = :actor, updated_at = :updated_at WHERE id = :id'
            );
            $stmt->execute([
                'type_id' => $typeId,
                'internal_code' => $internalCode,
                'name' => $name,
                'short_name' => $shortName,
                'note' => $note,
                'actor' => $actorUserId,
                'updated_at' => $now,
                'id' => $nodeId,
            ]);
            $this->bumpRevision((int) $node['structure_version_id'], $actorUserId);
            $this->recordEvent((int) $node['organizational_structure_id'], (int) $node['structure_version_id'], (int) $node['organizational_structure_element_id'], $actorUserId, 'node.updated', $before, $after);
        });
    }

    public function moveNode(int $nodeId, int $newParentNodeId, int $expectedRevision, int $actorUserId): void
    {
        $this->transaction(function () use ($nodeId, $newParentNodeId, $expectedRevision, $actorUserId): void {
            $versionId = $this->nodeVersionId($nodeId);
            $this->lockDraftVersion($versionId, $expectedRevision);
            $node = $this->lockNodeInVersion($nodeId, $versionId);
            if ($node['parent_node_id'] === null) {
                throw new DomainException('Корневой элемент нельзя переместить.');
            }
            $newParent = $this->lockNodeInVersion($newParentNodeId, $versionId);
            $oldParentId = (int) $node['parent_node_id'];
            if ($oldParentId === $newParentNodeId) {
                return;
            }
            if ($nodeId === $newParentNodeId || $this->isNodeInSubtree($versionId, $nodeId, $newParentNodeId)) {
                throw new DomainException('Нельзя переместить элемент в собственное поддерево.');
            }
            $orderStmt = $this->pdo->prepare(
                'SELECT COALESCE(MAX(sort_order), 0) + 10 FROM organizational_structure_nodes WHERE structure_version_id = :version_id AND parent_node_id = :parent_id'
            );
            $orderStmt->execute(['version_id' => $versionId, 'parent_id' => (int) $newParent['id']]);
            $newOrder = (int) $orderStmt->fetchColumn();
            $update = $this->pdo->prepare(
                'UPDATE organizational_structure_nodes SET parent_node_id = :parent_id, sort_order = :sort_order, updated_by = :actor, updated_at = :updated_at WHERE id = :id'
            );
            $update->execute([
                'parent_id' => (int) $newParent['id'],
                'sort_order' => $newOrder,
                'actor' => $actorUserId,
                'updated_at' => $this->now(),
                'id' => $nodeId,
            ]);
            $this->normalizeSiblingOrder($versionId, $oldParentId, null);
            $this->normalizeSiblingOrder($versionId, (int) $newParent['id'], null);
            $this->bumpRevision($versionId, $actorUserId);
            $this->recordEvent((int) $node['organizational_structure_id'], $versionId, (int) $node['organizational_structure_element_id'], $actorUserId, 'node.moved', [
                'parent_node_id' => $oldParentId,
            ], [
                'parent_node_id' => (int) $newParent['id'],
            ]);
        });
    }

    public function reorderNode(int $nodeId, string $direction, int $expectedRevision, int $actorUserId): void
    {
        if (!in_array($direction, ['up', 'down'], true)) {
            throw new DomainException('Некорректное направление перемещения.');
        }
        $this->transaction(function () use ($nodeId, $direction, $expectedRevision, $actorUserId): void {
            $versionId = $this->nodeVersionId($nodeId);
            $this->lockDraftVersion($versionId, $expectedRevision);
            $node = $this->lockNodeInVersion($nodeId, $versionId);
            if ($node['parent_node_id'] === null) {
                throw new DomainException('Порядок корневого элемента не изменяется.');
            }
            $siblings = $this->lockSiblings($versionId, (int) $node['parent_node_id']);
            $ids = array_map(static fn (array $row): int => (int) $row['id'], $siblings);
            $index = array_search($nodeId, $ids, true);
            if ($index === false) {
                throw new DomainException('Элемент не найден среди соседних элементов.');
            }
            $targetIndex = $direction === 'up' ? $index - 1 : $index + 1;
            if ($targetIndex < 0 || $targetIndex >= count($ids)) {
                return;
            }
            [$ids[$index], $ids[$targetIndex]] = [$ids[$targetIndex], $ids[$index]];
            $this->normalizeSiblingOrder($versionId, (int) $node['parent_node_id'], $ids);
            $this->bumpRevision($versionId, $actorUserId);
            $this->recordEvent((int) $node['organizational_structure_id'], $versionId, (int) $node['organizational_structure_element_id'], $actorUserId, 'node.reordered', [
                'direction' => $direction === 'up' ? 'down' : 'up',
            ], [
                'direction' => $direction,
            ]);
        });
    }

    public function deleteNode(int $nodeId, bool $confirmSubtree, ?string $reason, int $expectedRevision, int $actorUserId): void
    {
        $reason = $this->nullableText($reason, 1000);
        $this->transaction(function () use ($nodeId, $confirmSubtree, $reason, $expectedRevision, $actorUserId): void {
            $versionId = $this->nodeVersionId($nodeId);
            $this->lockDraftVersion($versionId, $expectedRevision);
            $node = $this->lockNodeInVersion($nodeId, $versionId);
            if ($node['parent_node_id'] === null) {
                throw new DomainException('Корневой элемент нельзя удалить.');
            }
            $subtree = $this->subtreeNodes($versionId, $nodeId);
            if (count($subtree) > 1 && !$confirmSubtree) {
                throw new DomainException('Элемент содержит дочерние элементы. Подтвердите удаление всего поддерева.');
            }
            if (count($subtree) > 1 && ($reason === null || $reason === '')) {
                throw new DomainException('Для удаления поддерева требуется основание.');
            }
            $delete = $this->pdo->prepare('DELETE FROM organizational_structure_nodes WHERE id = :id');
            foreach ($subtree as $subtreeNode) {
                $delete->execute(['id' => (int) $subtreeNode['id']]);
                $this->recordEvent(
                    (int) $subtreeNode['organizational_structure_id'],
                    $versionId,
                    (int) $subtreeNode['organizational_structure_element_id'],
                    $actorUserId,
                    'node.deleted',
                    $this->nodeState($subtreeNode),
                    null,
                    $reason
                );
            }
            $this->normalizeSiblingOrder($versionId, (int) $node['parent_node_id'], null);
            $this->bumpRevision($versionId, $actorUserId);
            if (count($subtree) > 1) {
                $this->recordEvent((int) $node['organizational_structure_id'], $versionId, (int) $node['organizational_structure_element_id'], $actorUserId, 'node.subtree_deleted', [
                    'root_node_id' => $nodeId,
                    'node_count' => count($subtree),
                ], null, $reason);
            }
        });
    }

}
