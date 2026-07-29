<?php

declare(strict_types=1);

trait OrganizationalStructureSupportTrait
{
    private function lockDraftVersion(int $versionId, int $expectedRevision): array
    {
        if ($expectedRevision < 1) {
            throw new DomainException('Некорректная версия формы.');
        }
        $structureId = $this->versionStructureId($versionId);
        $structure = $this->lockStructure($structureId);
        if ((string) $structure['status'] !== 'active') {
            throw new DomainException('Архивная структура не может быть изменена.');
        }
        $stmt = $this->pdo->prepare('SELECT * FROM organizational_structure_versions WHERE id = :id FOR UPDATE');
        $stmt->execute(['id' => $versionId]);
        $version = $stmt->fetch();
        if (!is_array($version) || (int) $version['organizational_structure_id'] !== $structureId) {
            throw new DomainException('Версия структуры не найдена.');
        }
        if ((string) $version['status'] !== 'draft') {
            throw new DomainException('Изменять можно только черновую версию.');
        }
        if ((int) $version['revision'] !== $expectedRevision) {
            throw new DomainException('Структура была изменена другим пользователем. Обновите страницу и повторите действие.');
        }
        return $version;
    }

    private function versionStructureId(int $versionId): int
    {
        $stmt = $this->pdo->prepare('SELECT organizational_structure_id FROM organizational_structure_versions WHERE id = :id');
        $stmt->execute(['id' => $versionId]);
        $value = $stmt->fetchColumn();
        if ($value === false) {
            throw new DomainException('Версия структуры не найдена.');
        }
        return (int) $value;
    }

    /** @return array<string,mixed> */
    private function lockStructure(int $structureId): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM organizational_structures WHERE id = :id FOR UPDATE');
        $stmt->execute(['id' => $structureId]);
        $structure = $stmt->fetch();
        if (!is_array($structure)) {
            throw new DomainException('Организационная структура не найдена.');
        }
        return $structure;
    }

    private function pendingVersionExists(int $structureId): bool
    {
        $stmt = $this->pdo->prepare(
            "SELECT 1 FROM organizational_structure_versions WHERE organizational_structure_id = :structure_id AND status IN ('draft', 'approved') LIMIT 1"
        );
        $stmt->execute(['structure_id' => $structureId]);
        return $stmt->fetchColumn() !== false;
    }

    private function lockActiveVersion(int $structureId): ?array
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM organizational_structure_versions WHERE organizational_structure_id = :structure_id AND status = 'active' LIMIT 1 FOR UPDATE"
        );
        $stmt->execute(['structure_id' => $structureId]);
        $row = $stmt->fetch();
        return is_array($row) ? $row : null;
    }

    private function lockLatestCancelledVersion(int $structureId): ?array
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM organizational_structure_versions WHERE organizational_structure_id = :structure_id "
            . "AND status = 'cancelled' ORDER BY version_number DESC, id DESC LIMIT 1 FOR UPDATE"
        );
        $stmt->execute(['structure_id' => $structureId]);
        $row = $stmt->fetch();
        return is_array($row) ? $row : null;
    }

    private function nodeVersionId(int $nodeId): int
    {
        $stmt = $this->pdo->prepare('SELECT structure_version_id FROM organizational_structure_nodes WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $nodeId]);
        $versionId = $stmt->fetchColumn();
        if ($versionId === false) {
            throw new DomainException('Элемент структуры не найден.');
        }
        return (int) $versionId;
    }

    /** @return array<string,mixed> */
    private function lockNodeInVersion(int $nodeId, int $versionId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM organizational_structure_nodes WHERE id = :id AND structure_version_id = :version_id FOR UPDATE'
        );
        $stmt->execute(['id' => $nodeId, 'version_id' => $versionId]);
        $node = $stmt->fetch();
        if (!is_array($node)) {
            throw new DomainException('Элемент не принадлежит редактируемой версии.');
        }
        return $node;
    }

    private function currentCatalogVersionId(): int
    {
        $rows = $this->pdo->query(
            'SELECT id FROM organizational_element_catalog_versions WHERE is_current = 1 ORDER BY valid_from DESC, id DESC LIMIT 2'
        )->fetchAll(PDO::FETCH_COLUMN);
        if (count($rows) !== 1) {
            throw new DomainException('Текущая версия справочника типов не определена однозначно.');
        }
        return (int) $rows[0];
    }

    private function assertTypeInCatalog(int $typeId, int $catalogVersionId, ?string $requiredClass = null): void
    {
        $sql = 'SELECT 1 FROM organizational_element_types t WHERE t.id = :type_id AND t.catalog_version_id = :catalog_version_id';
        $params = ['type_id' => $typeId, 'catalog_version_id' => $catalogVersionId];
        if ($requiredClass !== null) {
            $sql .= ' AND EXISTS ('
                . 'SELECT 1 FROM organizational_element_type_classes tc '
                . 'JOIN organizational_element_classes c ON c.id = tc.class_id AND c.catalog_version_id = tc.catalog_version_id '
                . 'WHERE tc.type_id = t.id AND tc.catalog_version_id = t.catalog_version_id AND c.code = :class_code)';
            $params['class_code'] = $requiredClass;
        }
        $stmt = $this->pdo->prepare($sql . ' LIMIT 1');
        $stmt->execute($params);
        if ($stmt->fetchColumn() === false) {
            throw new DomainException($requiredClass === null
                ? 'Выбранный тип не относится к закреплённой версии справочника.'
                : 'Для корневого элемента требуется тип класса «Воинская часть».');
        }
    }

    private function isNodeInSubtree(int $versionId, int $rootNodeId, int $candidateNodeId): bool
    {
        $stmt = $this->pdo->prepare(
            'WITH RECURSIVE subtree AS ('
            . 'SELECT id FROM organizational_structure_nodes WHERE id = :root_id AND structure_version_id = :version_id_1 '
            . 'UNION ALL '
            . 'SELECT n.id FROM organizational_structure_nodes n JOIN subtree s ON n.parent_node_id = s.id '
            . 'WHERE n.structure_version_id = :version_id_2'
            . ') SELECT 1 FROM subtree WHERE id = :candidate_id LIMIT 1'
        );
        $stmt->execute([
            'root_id' => $rootNodeId,
            'version_id_1' => $versionId,
            'version_id_2' => $versionId,
            'candidate_id' => $candidateNodeId,
        ]);
        return $stmt->fetchColumn() !== false;
    }

    /** @return list<array<string,mixed>> */
    private function sourceNodesInTreeOrder(int $versionId): array
    {
        $stmt = $this->pdo->prepare(
            'WITH RECURSIVE tree AS ('
            . 'SELECT n.*, 0 AS tree_depth FROM organizational_structure_nodes n '
            . 'WHERE n.structure_version_id = :version_id_1 AND n.parent_node_id IS NULL '
            . 'UNION ALL '
            . 'SELECT n.*, tree.tree_depth + 1 FROM organizational_structure_nodes n '
            . 'JOIN tree ON n.parent_node_id = tree.id WHERE n.structure_version_id = :version_id_2'
            . ') SELECT * FROM tree ORDER BY tree_depth, parent_node_id, sort_order, id'
        );
        $stmt->execute(['version_id_1' => $versionId, 'version_id_2' => $versionId]);
        return $stmt->fetchAll();
    }

    /** @return list<array<string,mixed>> */
    private function subtreeNodes(int $versionId, int $nodeId): array
    {
        $stmt = $this->pdo->prepare(
            'WITH RECURSIVE subtree AS ('
            . 'SELECT n.*, 0 AS tree_depth FROM organizational_structure_nodes n '
            . 'WHERE n.id = :node_id AND n.structure_version_id = :version_id_1 '
            . 'UNION ALL '
            . 'SELECT n.*, subtree.tree_depth + 1 FROM organizational_structure_nodes n '
            . 'JOIN subtree ON n.parent_node_id = subtree.id WHERE n.structure_version_id = :version_id_2'
            . ') SELECT * FROM subtree ORDER BY tree_depth DESC, id DESC'
        );
        $stmt->execute(['node_id' => $nodeId, 'version_id_1' => $versionId, 'version_id_2' => $versionId]);
        return $stmt->fetchAll();
    }

    /** @return list<array<string,mixed>> */
    private function lockSiblings(int $versionId, int $parentNodeId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, sort_order FROM organizational_structure_nodes '
            . 'WHERE structure_version_id = :version_id AND parent_node_id = :parent_id '
            . 'ORDER BY sort_order, id FOR UPDATE'
        );
        $stmt->execute(['version_id' => $versionId, 'parent_id' => $parentNodeId]);
        return $stmt->fetchAll();
    }

    /** @param list<int>|null $orderedIds */
    private function normalizeSiblingOrder(int $versionId, int $parentNodeId, ?array $orderedIds): void
    {
        $siblings = $this->lockSiblings($versionId, $parentNodeId);
        if ($siblings === []) {
            return;
        }
        if ($orderedIds === null) {
            $orderedIds = array_map(static fn (array $row): int => (int) $row['id'], $siblings);
        }
        $max = max(array_map(static fn (array $row): int => (int) $row['sort_order'], $siblings));
        $offset = $max + (count($siblings) * 10) + 100;
        if ($max + $offset > 4294967295) {
            throw new DomainException('Невозможно безопасно изменить порядок элементов.');
        }
        $temporary = $this->pdo->prepare(
            'UPDATE organizational_structure_nodes SET sort_order = sort_order + :offset '
            . 'WHERE structure_version_id = :version_id AND parent_node_id = :parent_id'
        );
        $temporary->execute(['offset' => $offset, 'version_id' => $versionId, 'parent_id' => $parentNodeId]);
        $update = $this->pdo->prepare('UPDATE organizational_structure_nodes SET sort_order = :sort_order WHERE id = :id');
        $position = 10;
        foreach ($orderedIds as $id) {
            $update->execute(['sort_order' => $position, 'id' => $id]);
            $position += 10;
        }
    }

    private function assertPrimaryDocumentAvailable(int $versionId, string $role, ?int $excludeDocumentId): void
    {
        if ($role !== 'primary_basis') {
            return;
        }
        $sql = "SELECT 1 FROM organizational_structure_version_documents WHERE structure_version_id = :version_id "
            . "AND document_role = 'primary_basis'";
        $params = ['version_id' => $versionId];
        if ($excludeDocumentId !== null) {
            $sql .= ' AND document_id <> :exclude_document_id';
            $params['exclude_document_id'] = $excludeDocumentId;
        }
        $stmt = $this->pdo->prepare($sql . ' LIMIT 1');
        $stmt->execute($params);
        if ($stmt->fetchColumn() !== false) {
            throw new DomainException('У версии уже указан основной документ-основание.');
        }
    }

    private function bumpRevision(int $versionId, int $actorUserId): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE organizational_structure_versions SET revision = revision + 1, updated_by = :actor, updated_at = :updated_at WHERE id = :id'
        );
        $stmt->execute(['actor' => $actorUserId, 'updated_at' => $this->now(), 'id' => $versionId]);
    }

    private function validatePublishableVersion(int $versionId, int $catalogVersionId): void
    {
        $rootStmt = $this->pdo->prepare(
            'SELECT n.id, n.organizational_element_type_id FROM organizational_structure_nodes n '
            . 'WHERE n.structure_version_id = :version_id AND n.parent_node_id IS NULL'
        );
        $rootStmt->execute(['version_id' => $versionId]);
        $roots = $rootStmt->fetchAll();
        if (count($roots) !== 1) {
            throw new DomainException('Версия должна содержать ровно один корневой элемент.');
        }
        $this->assertTypeInCatalog((int) $roots[0]['organizational_element_type_id'], $catalogVersionId, 'military-unit');

        $countStmt = $this->pdo->prepare('SELECT COUNT(*) FROM organizational_structure_nodes WHERE structure_version_id = :version_id');
        $countStmt->execute(['version_id' => $versionId]);
        $nodeCount = (int) $countStmt->fetchColumn();
        if ($nodeCount < 1) {
            throw new DomainException('Версия не содержит элементов.');
        }
        try {
            $reachableStmt = $this->pdo->prepare(
                'WITH RECURSIVE tree AS ('
                . 'SELECT id FROM organizational_structure_nodes WHERE structure_version_id = :version_id_1 AND parent_node_id IS NULL '
                . 'UNION ALL '
                . 'SELECT n.id FROM organizational_structure_nodes n JOIN tree ON n.parent_node_id = tree.id '
                . 'WHERE n.structure_version_id = :version_id_2'
                . ') SELECT COUNT(*) FROM tree'
            );
            $reachableStmt->execute(['version_id_1' => $versionId, 'version_id_2' => $versionId]);
            $reachableCount = (int) $reachableStmt->fetchColumn();
        } catch (Throwable $exception) {
            throw new DomainException('В дереве обнаружен цикл или превышена допустимая глубина.', 0, $exception);
        }
        if ($reachableCount !== $nodeCount) {
            throw new DomainException('Дерево содержит недостижимые элементы или цикл.');
        }

        $primaryStmt = $this->pdo->prepare(
            "SELECT COUNT(*) FROM organizational_structure_version_documents WHERE structure_version_id = :version_id AND document_role = 'primary_basis'"
        );
        $primaryStmt->execute(['version_id' => $versionId]);
        if ((int) $primaryStmt->fetchColumn() !== 1) {
            throw new DomainException('Для утверждения требуется ровно один основной документ-основание.');
        }
    }

    private function recordEvent(
        int $structureId,
        ?int $versionId,
        ?int $elementId,
        ?int $actorUserId,
        string $eventType,
        ?array $beforeState,
        ?array $afterState,
        ?string $reason = null
    ): void {
        $stmt = $this->pdo->prepare(
            'INSERT INTO organizational_structure_change_events '
            . '(organizational_structure_id, structure_version_id, organizational_structure_element_id, actor_user_id, event_type, before_state, after_state, reason, created_at) '
            . 'VALUES (:structure_id, :version_id, :element_id, :actor_id, :event_type, :before_state, :after_state, :reason, :created_at)'
        );
        $stmt->execute([
            'structure_id' => $structureId,
            'version_id' => $versionId,
            'element_id' => $elementId,
            'actor_id' => $actorUserId,
            'event_type' => $eventType,
            'before_state' => $beforeState !== null ? json_encode($beforeState, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) : null,
            'after_state' => $afterState !== null ? json_encode($afterState, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) : null,
            'reason' => $reason,
            'created_at' => $this->now(),
        ]);
    }

    /** @return array<string,mixed> */
    private function nodeState(array $node): array
    {
        return [
            'node_id' => (int) $node['id'],
            'parent_node_id' => $node['parent_node_id'] !== null ? (int) $node['parent_node_id'] : null,
            'type_id' => (int) $node['organizational_element_type_id'],
            'internal_code' => $node['internal_code'],
            'name' => (string) $node['name'],
            'short_name' => $node['short_name'],
            'sort_order' => (int) $node['sort_order'],
            'note' => $node['note'],
        ];
    }

    private function requiredText(string $value, int $maxLength, string $error): string
    {
        $value = trim($value);
        if ($value === '' || mb_strlen($value) > $maxLength) {
            throw new DomainException($error);
        }
        return $value;
    }

    private function nullableText(?string $value, int $maxLength): ?string
    {
        if ($value === null) {
            return null;
        }
        $value = trim($value);
        if ($value === '') {
            return null;
        }
        if (mb_strlen($value) > $maxLength) {
            throw new DomainException('Значение превышает допустимую длину.');
        }
        return $value;
    }

    private function nullableCode(?string $value): ?string
    {
        $value = $this->nullableText($value, 64);
        if ($value !== null && preg_match('/\A[A-Za-z0-9][A-Za-z0-9._\/-]{0,63}\z/D', $value) !== 1) {
            throw new DomainException('Внутренний код содержит недопустимые символы.');
        }
        return $value;
    }

    private function validDate(string $date): bool
    {
        $parsed = DateTimeImmutable::createFromFormat('!Y-m-d', $date);
        return $parsed instanceof DateTimeImmutable && $parsed->format('Y-m-d') === $date;
    }

    private function now(): string
    {
        return (new DateTimeImmutable())->format('Y-m-d H:i:s');
    }

    /** @template T
     *  @param callable():T $callback
     *  @return T
     */
    private function transaction(callable $callback): mixed
    {
        $ownsTransaction = !$this->pdo->inTransaction();
        if ($ownsTransaction) {
            $this->pdo->beginTransaction();
        }
        try {
            $result = $callback();
            if ($ownsTransaction) {
                $this->pdo->commit();
            }
            return $result;
        } catch (Throwable $exception) {
            if ($ownsTransaction && $this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $exception;
        }
    }
}
