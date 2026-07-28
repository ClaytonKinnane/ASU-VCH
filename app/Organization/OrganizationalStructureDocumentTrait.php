<?php

declare(strict_types=1);

trait OrganizationalStructureDocumentTrait
{
    public function addDocument(
        int $versionId,
        string $documentType,
        string $documentDate,
        string $documentNumber,
        string $title,
        ?string $note,
        string $role,
        int $expectedRevision,
        int $actorUserId
    ): int {
        $documentType = $this->requiredText($documentType, 128, 'Укажите вид документа.');
        $documentNumber = $this->requiredText($documentNumber, 128, 'Укажите номер документа или «Без номера».');
        $title = $this->requiredText($title, 255, 'Укажите наименование документа.');
        $note = $this->nullableText($note, 4000);
        if (!$this->validDate($documentDate)) {
            throw new DomainException('Укажите корректную дату документа.');
        }
        if (!in_array($role, ['primary_basis', 'additional_basis', 'amendment'], true)) {
            throw new DomainException('Некорректная роль документа.');
        }

        return $this->transaction(function () use ($versionId, $documentType, $documentDate, $documentNumber, $title, $note, $role, $expectedRevision, $actorUserId): int {
            $version = $this->lockDraftVersion($versionId, $expectedRevision);
            $this->assertPrimaryDocumentAvailable($versionId, $role, null);
            $now = $this->now();
            $insert = $this->pdo->prepare(
                'INSERT INTO organizational_structure_documents '
                . '(organizational_structure_id, document_type, document_date, document_number, title, note, created_by, created_at, updated_by, updated_at) '
                . 'VALUES (:structure_id, :document_type, :document_date, :document_number, :title, :note, :actor, :created_at, :updated_by, :updated_at)'
            );
            $insert->execute([
                'structure_id' => (int) $version['organizational_structure_id'],
                'document_type' => $documentType,
                'document_date' => $documentDate,
                'document_number' => $documentNumber,
                'title' => $title,
                'note' => $note,
                'actor' => $actorUserId,
                'created_at' => $now,
                'updated_by' => $actorUserId,
                'updated_at' => $now,
            ]);
            $documentId = (int) $this->pdo->lastInsertId();
            $orderStmt = $this->pdo->prepare(
                'SELECT COALESCE(MAX(sort_order), 0) + 10 FROM organizational_structure_version_documents WHERE structure_version_id = :version_id'
            );
            $orderStmt->execute(['version_id' => $versionId]);
            $link = $this->pdo->prepare(
                'INSERT INTO organizational_structure_version_documents '
                . '(structure_version_id, organizational_structure_id, document_id, document_role, sort_order, created_by, created_at) '
                . 'VALUES (:version_id, :structure_id, :document_id, :role, :sort_order, :actor, :created_at)'
            );
            $link->execute([
                'version_id' => $versionId,
                'structure_id' => (int) $version['organizational_structure_id'],
                'document_id' => $documentId,
                'role' => $role,
                'sort_order' => (int) $orderStmt->fetchColumn(),
                'actor' => $actorUserId,
                'created_at' => $now,
            ]);
            $this->bumpRevision($versionId, $actorUserId);
            $this->recordEvent((int) $version['organizational_structure_id'], $versionId, null, $actorUserId, 'document.created', null, [
                'document_id' => $documentId,
                'role' => $role,
                'document_type' => $documentType,
                'document_date' => $documentDate,
                'document_number' => $documentNumber,
                'title' => $title,
            ]);
            return $documentId;
        });
    }

    public function updateDocument(
        int $versionId,
        int $documentId,
        string $documentType,
        string $documentDate,
        string $documentNumber,
        string $title,
        ?string $note,
        string $role,
        int $expectedRevision,
        int $actorUserId
    ): int {
        $documentType = $this->requiredText($documentType, 128, 'Укажите вид документа.');
        $documentNumber = $this->requiredText($documentNumber, 128, 'Укажите номер документа или «Без номера».');
        $title = $this->requiredText($title, 255, 'Укажите наименование документа.');
        $note = $this->nullableText($note, 4000);
        if (!$this->validDate($documentDate)) {
            throw new DomainException('Укажите корректную дату документа.');
        }
        if (!in_array($role, ['primary_basis', 'additional_basis', 'amendment'], true)) {
            throw new DomainException('Некорректная роль документа.');
        }

        return $this->transaction(function () use ($versionId, $documentId, $documentType, $documentDate, $documentNumber, $title, $note, $role, $expectedRevision, $actorUserId): int {
            $version = $this->lockDraftVersion($versionId, $expectedRevision);
            $stmt = $this->pdo->prepare(
                'SELECT d.*, vd.document_role, vd.sort_order FROM organizational_structure_version_documents vd '
                . 'JOIN organizational_structure_documents d ON d.id = vd.document_id '
                . 'WHERE vd.structure_version_id = :version_id AND vd.document_id = :document_id FOR UPDATE'
            );
            $stmt->execute(['version_id' => $versionId, 'document_id' => $documentId]);
            $document = $stmt->fetch();
            if (!is_array($document)) {
                throw new DomainException('Документ не связан с редактируемой версией.');
            }
            $this->assertPrimaryDocumentAvailable($versionId, $role, $documentId);
            $before = [
                'document_id' => $documentId,
                'role' => (string) $document['document_role'],
                'document_type' => (string) $document['document_type'],
                'document_date' => (string) $document['document_date'],
                'document_number' => (string) $document['document_number'],
                'title' => (string) $document['title'],
                'note' => $document['note'],
            ];
            $after = [
                'document_id' => $documentId,
                'role' => $role,
                'document_type' => $documentType,
                'document_date' => $documentDate,
                'document_number' => $documentNumber,
                'title' => $title,
                'note' => $note,
            ];
            if ($before === $after) {
                return $documentId;
            }
            $now = $this->now();
            $publishedUse = $this->pdo->prepare(
                "SELECT 1 FROM organizational_structure_version_documents vd "
                . 'JOIN organizational_structure_versions v ON v.id = vd.structure_version_id '
                . "WHERE vd.document_id = :document_id AND v.status <> 'draft' LIMIT 1"
            );
            $publishedUse->execute(['document_id' => $documentId]);
            $targetDocumentId = $documentId;
            if ($publishedUse->fetchColumn() !== false) {
                $insert = $this->pdo->prepare(
                    'INSERT INTO organizational_structure_documents '
                    . '(organizational_structure_id, document_type, document_date, document_number, title, note, created_by, created_at, updated_by, updated_at) '
                    . 'VALUES (:structure_id, :document_type, :document_date, :document_number, :title, :note, :actor, :created_at, :updated_by, :updated_at)'
                );
                $insert->execute([
                    'structure_id' => (int) $version['organizational_structure_id'],
                    'document_type' => $documentType,
                    'document_date' => $documentDate,
                    'document_number' => $documentNumber,
                    'title' => $title,
                    'note' => $note,
                    'actor' => $actorUserId,
                    'created_at' => $now,
                    'updated_by' => $actorUserId,
                    'updated_at' => $now,
                ]);
                $targetDocumentId = (int) $this->pdo->lastInsertId();
                $replace = $this->pdo->prepare(
                    'UPDATE organizational_structure_version_documents SET document_id = :new_document_id, document_role = :role '
                    . 'WHERE structure_version_id = :version_id AND document_id = :old_document_id'
                );
                $replace->execute([
                    'new_document_id' => $targetDocumentId,
                    'role' => $role,
                    'version_id' => $versionId,
                    'old_document_id' => $documentId,
                ]);
            } else {
                $update = $this->pdo->prepare(
                    'UPDATE organizational_structure_documents SET document_type = :document_type, document_date = :document_date, '
                    . 'document_number = :document_number, title = :title, note = :note, updated_by = :actor, updated_at = :updated_at '
                    . 'WHERE id = :document_id'
                );
                $update->execute([
                    'document_type' => $documentType,
                    'document_date' => $documentDate,
                    'document_number' => $documentNumber,
                    'title' => $title,
                    'note' => $note,
                    'actor' => $actorUserId,
                    'updated_at' => $now,
                    'document_id' => $documentId,
                ]);
                if ((string) $document['document_role'] !== $role) {
                    $roleUpdate = $this->pdo->prepare(
                        'UPDATE organizational_structure_version_documents SET document_role = :role '
                        . 'WHERE structure_version_id = :version_id AND document_id = :document_id'
                    );
                    $roleUpdate->execute(['role' => $role, 'version_id' => $versionId, 'document_id' => $documentId]);
                }
            }
            $this->bumpRevision($versionId, $actorUserId);
            $this->recordEvent((int) $version['organizational_structure_id'], $versionId, null, $actorUserId, 'document.updated', $before, [
                'document_id' => $targetDocumentId,
                'replaces_document_id' => $targetDocumentId !== $documentId ? $documentId : null,
                'role' => $role,
                'document_type' => $documentType,
                'document_date' => $documentDate,
                'document_number' => $documentNumber,
                'title' => $title,
                'note' => $note,
            ]);
            return $targetDocumentId;
        });
    }

    public function unlinkDocument(int $versionId, int $documentId, int $expectedRevision, int $actorUserId): void
    {
        $this->transaction(function () use ($versionId, $documentId, $expectedRevision, $actorUserId): void {
            $version = $this->lockDraftVersion($versionId, $expectedRevision);
            $stmt = $this->pdo->prepare(
                'SELECT * FROM organizational_structure_version_documents WHERE structure_version_id = :version_id AND document_id = :document_id FOR UPDATE'
            );
            $stmt->execute(['version_id' => $versionId, 'document_id' => $documentId]);
            $link = $stmt->fetch();
            if (!is_array($link)) {
                throw new DomainException('Связь с документом не найдена.');
            }
            $delete = $this->pdo->prepare(
                'DELETE FROM organizational_structure_version_documents WHERE structure_version_id = :version_id AND document_id = :document_id'
            );
            $delete->execute(['version_id' => $versionId, 'document_id' => $documentId]);
            $remaining = $this->pdo->prepare(
                'SELECT COUNT(*) FROM organizational_structure_version_documents WHERE document_id = :document_id'
            );
            $remaining->execute(['document_id' => $documentId]);
            if ((int) $remaining->fetchColumn() === 0) {
                $deleteDocument = $this->pdo->prepare('DELETE FROM organizational_structure_documents WHERE id = :document_id');
                $deleteDocument->execute(['document_id' => $documentId]);
            }
            $this->bumpRevision($versionId, $actorUserId);
            $this->recordEvent((int) $version['organizational_structure_id'], $versionId, null, $actorUserId, 'document.unlinked', [
                'document_id' => $documentId,
                'role' => (string) $link['document_role'],
            ], null);
        });
    }

}
