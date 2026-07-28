<?php

declare(strict_types=1);

return static function (
    PDO $pdo,
    callable $assert,
    int $actorId,
    array $catalog,
    array $rootTypes,
    array $allTypes
): void {
    $pdo->beginTransaction();
    try {
        $service = organizational_structure_service();
        $repository = organizational_structure_repository();
        $code = 'checker-' . substr(bin2hex(random_bytes(8)), 0, 12);
        $structureId = $service->createStructure(
            $code,
            'Синтетическая структура checker',
            'Checker',
            (int) $rootTypes[0]['id'],
            'Синтетическая воинская часть',
            'СВЧ',
            'Автоматическая интеграционная проверка',
            $actorId
        );
        $service->updateStructure($structureId, 'Синтетическая структура checker — обновлена', 'Checker v1', $actorId);
        $updatedStructure = $repository->findStructure($structureId);
        $assert(is_array($updatedStructure) && (string) $updatedStructure['short_name'] === 'Checker v1', 'карточка структуры изменяется через aggregate root');

        $codeMutationRejected = false;
        try {
            $codeMutation = $pdo->prepare('UPDATE organizational_structures SET code = :code WHERE id = :id');
            $codeMutation->execute(['code' => 'changed-code', 'id' => $structureId]);
        } catch (PDOException) {
            $codeMutationRejected = true;
        }
        $assert($codeMutationRejected, 'DB trigger защищает неизменяемый код структуры');

        $uppercaseCodeRejected = false;
        try {
            $uppercaseInsert = $pdo->prepare(
                "INSERT INTO organizational_structures (code, display_name, status, created_at, updated_at) VALUES (:code, 'Недопустимая структура', 'active', NOW(), NOW())"
            );
            $uppercaseInsert->execute(['code' => 'UPPER-' . substr(bin2hex(random_bytes(4)), 0, 6)]);
        } catch (PDOException) {
            $uppercaseCodeRejected = true;
        }
        $assert($uppercaseCodeRejected, 'DB ограничение запрещает uppercase в машинном коде структуры');

        $cancelledStructureId = $service->createStructure(
            'checker-cancel-' . substr(bin2hex(random_bytes(6)), 0, 8),
            'Синтетическая структура с отменённой первой версией',
            null,
            (int) $rootTypes[0]['id'],
            'Синтетическая воинская часть для отмены',
            null,
            'Проверка повторного черновика',
            $actorId
        );
        $cancelledInitial = $repository->pendingVersion($cancelledStructureId);
        $service->cancelVersion((int) $cancelledInitial['id'], 'Отмена первоначального черновика', (int) $cancelledInitial['revision'], $actorId);
        $replacementDraftId = $service->createDraft($cancelledStructureId, 'Новый черновик после отмены', $actorId);
        $replacementDraft = $repository->findVersion($replacementDraftId);
        $assert(is_array($replacementDraft) && (string) $replacementDraft['status'] === 'draft' && (int) $replacementDraft['based_on_version_id'] === (int) $cancelledInitial['id'], 'после отмены первой версии можно создать новый черновик');

        $pending = $repository->pendingVersion($structureId);
        $assert(is_array($pending) && (string) $pending['status'] === 'draft', 'создан первоначальный черновик');

        $directArchiveWithDraftRejected = false;
        try {
            $directArchiveWithDraft = $pdo->prepare(
                "UPDATE organizational_structures SET status = 'archived', archived_by = :actor, archived_at = NOW(), "
                . "archive_reason = 'Недопустимое прямое архивирование', updated_by = :updated_by, updated_at = NOW() WHERE id = :id"
            );
            $directArchiveWithDraft->execute(['actor' => $actorId, 'updated_by' => $actorId, 'id' => $structureId]);
        } catch (PDOException) {
            $directArchiveWithDraftRejected = true;
        }
        $assert($directArchiveWithDraftRejected, 'DB trigger запрещает архивирование при незавершённой версии');

        $versionId = (int) $pending['id'];
        $nodes = $repository->nodesForVersion($versionId);
        $assert(count($nodes) === 1 && $nodes[0]['parent_node_id'] === null, 'создан единственный корень');
        $rootNodeId = (int) $nodes[0]['id'];

        $directRootDeleteRejected = false;
        try {
            $directRootDelete = $pdo->prepare('DELETE FROM organizational_structure_nodes WHERE id = :id');
            $directRootDelete->execute(['id' => $rootNodeId]);
        } catch (PDOException) {
            $directRootDeleteRejected = true;
        }
        $assert($directRootDeleteRejected, 'DB trigger запрещает удаление корневого элемента черновика');

        $childId = $service->addNode(
            $versionId,
            $rootNodeId,
            (int) $allTypes[0]['id'],
            'SYN-1',
            'Синтетический элемент 1',
            'СЭ-1',
            null,
            (int) $pending['revision'],
            $actorId
        );
        $pending = $repository->findVersion($versionId);
        $assert(is_array($pending) && (int) $pending['revision'] === 2, 'изменение дерева увеличивает revision');

        $directRootMoveRejected = false;
        try {
            $directRootMove = $pdo->prepare(
                'UPDATE organizational_structure_nodes SET parent_node_id = :parent_id WHERE id = :id'
            );
            $directRootMove->execute(['parent_id' => $childId, 'id' => $rootNodeId]);
        } catch (PDOException) {
            $directRootMoveRejected = true;
        }
        $assert($directRootMoveRejected, 'DB trigger запрещает перемещение корневого элемента черновика');

        $directApprovalRejected = false;
        try {
            $directApproval = $pdo->prepare(
                "UPDATE organizational_structure_versions SET status = 'approved', effective_from = :effective_from, "
                . 'approved_by = :actor, approved_at = NOW(), updated_by = :updated_by, updated_at = NOW() WHERE id = :id'
            );
            $directApproval->execute([
                'effective_from' => date('Y-m-d'),
                'actor' => $actorId,
                'updated_by' => $actorId,
                'id' => $versionId,
            ]);
        } catch (PDOException) {
            $directApprovalRejected = true;
        }
        $assert($directApprovalRejected, 'DB trigger не допускает утверждение без основного документа');

        $documentId = $service->addDocument(
            $versionId,
            'Синтетический документ',
            date('Y-m-d'),
            'CHECKER-1',
            'Основание синтетической структуры',
            null,
            'primary_basis',
            (int) $pending['revision'],
            $actorId
        );
        $pending = $repository->findVersion($versionId);
        $service->approveVersion($versionId, date('Y-m-d'), (int) $pending['revision'], $actorId);
        $service->activateVersion($versionId, $actorId);
        $active = $repository->activeVersion($structureId);
        $assert(is_array($active) && (int) $active['id'] === $versionId, 'версия утверждена и введена в действие');

        $directMutationRejected = false;
        try {
            $direct = $pdo->prepare('UPDATE organizational_structure_nodes SET name = :name WHERE id = :id');
            $direct->execute(['name' => 'Недопустимое изменение', 'id' => $rootNodeId]);
        } catch (PDOException) {
            $directMutationRejected = true;
        }
        $assert($directMutationRejected, 'DB trigger запрещает изменение опубликованного узла');

        $directDocumentMutationRejected = false;
        try {
            $directDocument = $pdo->prepare('UPDATE organizational_structure_documents SET title = :title WHERE id = :id');
            $directDocument->execute(['title' => 'Недопустимое изменение документа', 'id' => $documentId]);
        } catch (PDOException) {
            $directDocumentMutationRejected = true;
        }
        $assert($directDocumentMutationRejected, 'DB trigger запрещает изменение документа опубликованной версии');

        $directLifecycleMutationRejected = false;
        try {
            $directLifecycle = $pdo->prepare('UPDATE organizational_structure_versions SET updated_at = NOW() WHERE id = :id');
            $directLifecycle->execute(['id' => $versionId]);
        } catch (PDOException) {
            $directLifecycleMutationRejected = true;
        }
        $assert($directLifecycleMutationRejected, 'DB trigger запрещает произвольное изменение active-версии');

        $newVersionId = $service->createDraft($structureId, 'Синтетическое изменение', $actorId);
        $newVersion = $repository->findVersion($newVersionId);
        $replacementDocumentId = $service->updateDocument(
            $newVersionId,
            $documentId,
            'Синтетический документ',
            date('Y-m-d'),
            'CHECKER-2',
            'Изменённое основание синтетической структуры',
            'Copy-on-write проверка',
            'primary_basis',
            (int) $newVersion['revision'],
            $actorId
        );
        $activeDocuments = $repository->documentsForVersion($versionId);
        $draftDocuments = $repository->documentsForVersion($newVersionId);
        $assert($replacementDocumentId !== $documentId, 'редактирование опубликованного документа создаёт copy-on-write запись');
        $assert((int) $activeDocuments[0]['id'] === $documentId && (string) $activeDocuments[0]['document_number'] === 'CHECKER-1', 'опубликованная версия сохраняет прежний документ');
        $assert((int) $draftDocuments[0]['id'] === $replacementDocumentId && (string) $draftDocuments[0]['document_number'] === 'CHECKER-2', 'черновик использует заменяющий документ');
        $newVersion = $repository->findVersion($newVersionId);
        $newNodes = $repository->nodesForVersion($newVersionId);
        $activeNodes = $repository->nodesForVersion($versionId);
        $activeElementIds = array_map(static fn (array $row): int => (int) $row['organizational_structure_element_id'], $activeNodes);
        $newElementIds = array_map(static fn (array $row): int => (int) $row['organizational_structure_element_id'], $newNodes);
        sort($activeElementIds);
        sort($newElementIds);
        $assert($activeElementIds === $newElementIds, 'клонирование сохраняет стабильные element IDs');

        $activeChild = $repository->findNode($childId);
        $clonedChild = array_values(array_filter($newNodes, static fn (array $row): bool => (int) $row['organizational_structure_element_id'] === (int) $activeChild['organizational_structure_element_id']))[0];
        $revisionBeforeNoop = (int) $newVersion['revision'];
        $service->moveNode((int) $clonedChild['id'], (int) $clonedChild['parent_node_id'], $revisionBeforeNoop, $actorId);
        $newVersion = $repository->findVersion($newVersionId);
        $assert((int) $newVersion['revision'] === $revisionBeforeNoop, 'перемещение к тому же родителю является no-op');

        $newRoot = array_values(array_filter($newNodes, static fn (array $row): bool => $row['parent_node_id'] === null))[0];
        $nodeA = $service->addNode(
            $newVersionId,
            (int) $newRoot['id'],
            (int) $allTypes[0]['id'],
            'SYN-A',
            'Синтетический элемент A',
            null,
            null,
            (int) $newVersion['revision'],
            $actorId
        );
        $newVersion = $repository->findVersion($newVersionId);
        $nodeB = $service->addNode(
            $newVersionId,
            $nodeA,
            (int) $allTypes[0]['id'],
            'SYN-B',
            'Синтетический элемент B',
            null,
            null,
            (int) $newVersion['revision'],
            $actorId
        );
        $newVersion = $repository->findVersion($newVersionId);
        $cycleRejected = false;
        try {
            $service->moveNode($nodeA, $nodeB, (int) $newVersion['revision'], $actorId);
        } catch (DomainException) {
            $cycleRejected = true;
        }
        $assert($cycleRejected, 'перемещение в собственное поддерево запрещено');

        $staleRejected = false;
        try {
            $service->addNode(
                $newVersionId,
                (int) $newRoot['id'],
                (int) $allTypes[0]['id'],
                'SYN-STALE',
                'Устаревшая операция',
                null,
                null,
                1,
                $actorId
            );
        } catch (DomainException) {
            $staleRejected = true;
        }
        $assert($staleRejected, 'устаревший expected_revision отклоняется');

        $newVersion = $repository->findVersion($newVersionId);
        $service->cancelVersion($newVersionId, 'Завершение синтетического сценария', (int) $newVersion['revision'], $actorId);
        $service->archiveStructure($structureId, 'Синтетическое архивирование', $actorId);
        $archived = $repository->findStructure($structureId);
        $assert(is_array($archived) && (string) $archived['status'] === 'archived', 'структура архивируется с основанием');

        $directRestoreRejected = false;
        try {
            $directRestore = $pdo->prepare("UPDATE organizational_structures SET status = 'active' WHERE id = :id");
            $directRestore->execute(['id' => $structureId]);
        } catch (PDOException) {
            $directRestoreRejected = true;
        }
        $assert($directRestoreRejected, 'DB trigger запрещает восстановление без обязательных реквизитов');

        $service->restoreStructure($structureId, 'Синтетическое восстановление', $actorId);
        $restored = $repository->findStructure($structureId);
        $assert(is_array($restored) && (string) $restored['status'] === 'active', 'структура восстанавливается с основанием');

        $eventId = (int) $pdo->query(
            'SELECT id FROM organizational_structure_change_events WHERE organizational_structure_id = ' . $structureId . ' ORDER BY id LIMIT 1'
        )->fetchColumn();
        $historyDeleteRejected = false;
        try {
            $deleteEvent = $pdo->prepare('DELETE FROM organizational_structure_change_events WHERE id = :id');
            $deleteEvent->execute(['id' => $eventId]);
        } catch (PDOException) {
            $historyDeleteRejected = true;
        }
        $assert($historyDeleteRejected, 'предметная история является append-only');
    } finally {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
    }

    $assert(true, 'синтетический сценарий полностью откачен');
};
