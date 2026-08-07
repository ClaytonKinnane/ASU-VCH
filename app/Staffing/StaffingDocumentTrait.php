<?php

declare(strict_types=1);

trait StaffingDocumentTrait
{
    public function createDocument(int $registerId, int $versionId, array $input, int $actorId): int
    {
        $expectedRevision = $this->positiveInt($input['expected_revision'] ?? null, 'Некорректная ревизия версии.');
        $data = $this->normalizeDocumentInput($input);

        return $this->transaction(function () use ($registerId, $versionId, $expectedRevision, $data, $actorId): int {
            $register = $this->lockRegister($registerId);
            $this->assertRegisterActive($register);
            $version = $this->lockVersion($registerId, $versionId);
            $this->assertDraft($version, $expectedRevision);

            $insert = $this->pdo->prepare(
                'INSERT INTO staffing_documents '
                . '(staffing_register_id,document_type,document_date,document_number,title,note,created_by,created_at,updated_by,updated_at) '
                . 'VALUES (:register_id,:document_type,:document_date,:document_number,:title,:note,:created_actor,NOW(),:updated_actor,NOW())'
            );
            $insert->execute([
                'register_id' => $registerId,
                'document_type' => $data['document_type'],
                'document_date' => $data['document_date'],
                'document_number' => $data['document_number'],
                'title' => $data['title'],
                'note' => $data['note'],
                'created_actor' => $actorId,
                'updated_actor' => $actorId,
            ]);
            $documentId = (int) $this->pdo->lastInsertId();
            $link = $this->pdo->prepare(
                'INSERT INTO staffing_version_documents '
                . '(staffing_version_id,staffing_register_id,document_id,document_role,sort_order,created_by,created_at) '
                . 'VALUES (:version_id,:register_id,:document_id,:role,:sort_order,:actor,NOW())'
            );
            $link->execute([
                'version_id' => $versionId,
                'register_id' => $registerId,
                'document_id' => $documentId,
                'role' => $data['document_role'],
                'sort_order' => $data['sort_order'],
                'actor' => $actorId,
            ]);
            $this->incrementVersionRevision($registerId, $versionId, $expectedRevision, $actorId);
            $this->appendEvent(
                $registerId,
                $versionId,
                null,
                $actorId,
                'document.created',
                'document',
                $documentId,
                null,
                $data
            );
            return $documentId;
        });
    }

    public function updateDocument(int $registerId, int $versionId, int $documentId, array $input, int $actorId): int
    {
        $expectedRevision = $this->positiveInt($input['expected_revision'] ?? null, 'Некорректная ревизия версии.');
        $data = $this->normalizeDocumentInput($input);

        return $this->transaction(function () use (
            $registerId,
            $versionId,
            $documentId,
            $expectedRevision,
            $data,
            $actorId
        ): int {
            $register = $this->lockRegister($registerId);
            $this->assertRegisterActive($register);
            $version = $this->lockVersion($registerId, $versionId);
            $this->assertDraft($version, $expectedRevision);

            $documentStmt = $this->pdo->prepare(
                'SELECT d.*,vd.document_role,vd.sort_order FROM staffing_documents d '
                . 'JOIN staffing_version_documents vd ON vd.document_id=d.id '
                . 'WHERE d.id=:document_id AND vd.staffing_version_id=:version_id '
                . 'AND vd.staffing_register_id=:register_id FOR UPDATE'
            );
            $documentStmt->execute([
                'document_id' => $documentId,
                'version_id' => $versionId,
                'register_id' => $registerId,
            ]);
            $document = $documentStmt->fetch();
            if (!is_array($document)) {
                throw new DomainException('Документ-основание не найден.');
            }

            $publishedCheck = $this->pdo->prepare(
                'SELECT 1 FROM staffing_version_documents vd '
                . 'JOIN staffing_versions v ON v.id=vd.staffing_version_id '
                . "WHERE vd.document_id=:document_id AND v.status<>'draft' LIMIT 1"
            );
            $publishedCheck->execute(['document_id' => $documentId]);
            $targetDocumentId = $documentId;

            if ($publishedCheck->fetchColumn() !== false) {
                $clone = $this->pdo->prepare(
                    'INSERT INTO staffing_documents '
                    . '(staffing_register_id,document_type,document_date,document_number,title,note,created_by,created_at,updated_by,updated_at) '
                    . 'VALUES (:register_id,:document_type,:document_date,:document_number,:title,:note,:created_actor,NOW(),:updated_actor,NOW())'
                );
                $clone->execute([
                    'register_id' => $registerId,
                    'document_type' => $data['document_type'],
                    'document_date' => $data['document_date'],
                    'document_number' => $data['document_number'],
                    'title' => $data['title'],
                    'note' => $data['note'],
                    'created_actor' => $actorId,
                    'updated_actor' => $actorId,
                ]);
                $targetDocumentId = (int) $this->pdo->lastInsertId();
                $deleteLink = $this->pdo->prepare(
                    'DELETE FROM staffing_version_documents WHERE staffing_version_id=:version_id AND document_id=:document_id'
                );
                $deleteLink->execute(['version_id' => $versionId, 'document_id' => $documentId]);
                $link = $this->pdo->prepare(
                    'INSERT INTO staffing_version_documents '
                    . '(staffing_version_id,staffing_register_id,document_id,document_role,sort_order,created_by,created_at) '
                    . 'VALUES (:version_id,:register_id,:document_id,:role,:sort_order,:actor,NOW())'
                );
                $link->execute([
                    'version_id' => $versionId,
                    'register_id' => $registerId,
                    'document_id' => $targetDocumentId,
                    'role' => $data['document_role'],
                    'sort_order' => $data['sort_order'],
                    'actor' => $actorId,
                ]);
            } else {
                $update = $this->pdo->prepare(
                    'UPDATE staffing_documents SET document_type=:document_type,document_date=:document_date,'
                    . 'document_number=:document_number,title=:title,note=:note,updated_by=:actor,updated_at=NOW() '
                    . 'WHERE id=:document_id'
                );
                $update->execute([
                    'document_type' => $data['document_type'],
                    'document_date' => $data['document_date'],
                    'document_number' => $data['document_number'],
                    'title' => $data['title'],
                    'note' => $data['note'],
                    'actor' => $actorId,
                    'document_id' => $documentId,
                ]);
                $updateLink = $this->pdo->prepare(
                    'UPDATE staffing_version_documents SET document_role=:role,sort_order=:sort_order '
                    . 'WHERE staffing_version_id=:version_id AND document_id=:document_id'
                );
                $updateLink->execute([
                    'role' => $data['document_role'],
                    'sort_order' => $data['sort_order'],
                    'version_id' => $versionId,
                    'document_id' => $documentId,
                ]);
            }

            $this->incrementVersionRevision($registerId, $versionId, $expectedRevision, $actorId);
            $this->appendEvent(
                $registerId,
                $versionId,
                null,
                $actorId,
                $targetDocumentId === $documentId ? 'document.updated' : 'document.copied_on_write',
                'document',
                $targetDocumentId,
                $document,
                $data
            );
            return $targetDocumentId;
        });
    }

    public function unlinkDocument(
        int $registerId,
        int $versionId,
        int $documentId,
        int $expectedRevision,
        string $reason,
        int $actorId
    ): void {
        $reason = $this->requiredString($reason, 1000, 'Укажите основание исключения документа.');
        $this->transaction(function () use (
            $registerId,
            $versionId,
            $documentId,
            $expectedRevision,
            $reason,
            $actorId
        ): void {
            $register = $this->lockRegister($registerId);
            $this->assertRegisterActive($register);
            $version = $this->lockVersion($registerId, $versionId);
            $this->assertDraft($version, $expectedRevision);
            $stmt = $this->pdo->prepare(
                'DELETE FROM staffing_version_documents WHERE staffing_version_id=:version_id '
                . 'AND staffing_register_id=:register_id AND document_id=:document_id'
            );
            $stmt->execute([
                'version_id' => $versionId,
                'register_id' => $registerId,
                'document_id' => $documentId,
            ]);
            if ($stmt->rowCount() !== 1) {
                throw new DomainException('Связь документа с версией не найдена.');
            }
            $this->incrementVersionRevision($registerId, $versionId, $expectedRevision, $actorId);
            $this->appendEvent(
                $registerId,
                $versionId,
                null,
                $actorId,
                'document.unlinked',
                'document',
                $documentId,
                ['linked' => true],
                ['linked' => false],
                $reason
            );
        });
    }

    /** @return array<string,mixed> */
    private function normalizeDocumentInput(array $input): array
    {
        return [
            'document_type' => $this->enum(
                $input['document_type'] ?? null,
                ['staffing_order', 'amendment_order', 'approval_act', 'other_basis'],
                'Некорректный тип документа.'
            ),
            'document_date' => $this->date($input['document_date'] ?? null, 'Укажите корректную дату документа.'),
            'document_number' => $this->requiredString($input['document_number'] ?? null, 128, 'Укажите номер документа.'),
            'title' => $this->requiredString($input['title'] ?? null, 255, 'Укажите наименование документа.'),
            'note' => $this->nullableString($input['note'] ?? null, 5000, 'Примечание слишком длинное.'),
            'document_role' => $this->enum(
                $input['document_role'] ?? null,
                ['primary_basis', 'additional_basis', 'amendment'],
                'Некорректная роль документа.'
            ),
            'sort_order' => $this->positiveInt($input['sort_order'] ?? null, 'Укажите положительный порядок документа.'),
        ];
    }
}
