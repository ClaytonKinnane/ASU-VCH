SET @mp14_now = NOW(6);

DROP PROCEDURE IF EXISTS validate_military_positions_directory_v1_preflight;
CREATE PROCEDURE validate_military_positions_directory_v1_preflight()
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.tables
        WHERE table_schema = DATABASE() AND table_name = 'military_position_catalog_versions'
    ) OR NOT EXISTS (
        SELECT 1 FROM information_schema.tables
        WHERE table_schema = DATABASE() AND table_name = 'military_position_types'
    ) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'MP14_LEGACY_SCHEMA_MISSING';
    END IF;
    IF EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = DATABASE() AND table_name = 'military_position_types' AND column_name = 'stable_key'
    ) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'MP14_PARTIAL_SCHEMA_DETECTED';
    END IF;
    IF (SELECT COUNT(*) FROM military_position_catalog_versions WHERE status = 'published') <> 1 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'MP14_CURRENT_LEGACY_VERSION_INVALID';
    END IF;
    IF EXISTS (
        SELECT 1 FROM military_position_catalog_versions
        WHERE status NOT IN ('published', 'superseded')
    ) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'MP14_UNSUPPORTED_LEGACY_LIFECYCLE';
    END IF;
END;
CALL validate_military_positions_directory_v1_preflight();
DROP PROCEDURE validate_military_positions_directory_v1_preflight;

DROP TRIGGER IF EXISTS trg_mp_cv_bi;
DROP TRIGGER IF EXISTS trg_mp_cv_bu;
DROP TRIGGER IF EXISTS trg_mp_cv_bd;
DROP TRIGGER IF EXISTS trg_mp_t_bi;
DROP TRIGGER IF EXISTS trg_mp_t_bu;
DROP TRIGGER IF EXISTS trg_mp_t_bd;

ALTER TABLE military_position_catalog_versions
    DROP CHECK chk_mp_catalog_versions_status,
    DROP CHECK chk_mp_catalog_versions_dates;

ALTER TABLE military_position_catalog_versions
    ADD COLUMN version_number INT UNSIGNED NULL AFTER id,
    ADD COLUMN version_label VARCHAR(255) NULL AFTER name,
    ADD COLUMN catalog_kind VARCHAR(20) NOT NULL DEFAULT 'legacy' AFTER coverage_note,
    ADD COLUMN revision INT UNSIGNED NOT NULL DEFAULT 1 AFTER valid_to,
    ADD COLUMN change_reason VARCHAR(1000) NULL AFTER revision,
    ADD COLUMN updated_by BIGINT UNSIGNED NULL AFTER created_at,
    ADD COLUMN updated_at DATETIME(6) NULL AFTER updated_by,
    ADD COLUMN published_by BIGINT UNSIGNED NULL AFTER updated_at,
    ADD COLUMN published_at DATETIME(6) NULL AFTER published_by,
    ADD COLUMN superseded_by BIGINT UNSIGNED NULL AFTER published_at,
    ADD COLUMN superseded_at DATETIME(6) NULL AFTER superseded_by,
    ADD COLUMN cancelled_by BIGINT UNSIGNED NULL AFTER superseded_at,
    ADD COLUMN cancelled_at DATETIME(6) NULL AFTER cancelled_by,
    ADD COLUMN cancellation_reason VARCHAR(1000) NULL AFTER cancelled_at;

SET @mp14_version_number = 0;
UPDATE military_position_catalog_versions
SET version_number = (@mp14_version_number := @mp14_version_number + 1),
    version_label = name,
    catalog_kind = 'legacy',
    revision = 1,
    change_reason = 'Историческая версия публичного классификатора migration 010.',
    updated_at = created_at,
    published_at = CASE WHEN status IN ('published', 'superseded') THEN created_at ELSE NULL END,
    superseded_at = CASE WHEN status = 'superseded' THEN created_at ELSE NULL END
ORDER BY valid_from, id;

ALTER TABLE military_position_catalog_versions
    MODIFY COLUMN version_number INT UNSIGNED NOT NULL,
    MODIFY COLUMN version_label VARCHAR(255) NOT NULL,
    MODIFY COLUMN status VARCHAR(20) NOT NULL DEFAULT 'draft',
    MODIFY COLUMN change_reason VARCHAR(1000) NOT NULL,
    MODIFY COLUMN updated_at DATETIME(6) NOT NULL,
    ADD COLUMN draft_guard TINYINT GENERATED ALWAYS AS (CASE WHEN status = 'draft' THEN 1 ELSE NULL END) STORED AFTER current_guard,
    ADD UNIQUE KEY uq_mp_catalog_versions_number (version_number),
    ADD UNIQUE KEY uq_mp_catalog_versions_draft_guard (draft_guard),
    ADD CONSTRAINT chk_mp_catalog_versions_status_v1 CHECK (status IN ('draft','published','superseded','cancelled')),
    ADD CONSTRAINT chk_mp_catalog_versions_dates_v1 CHECK (
        (status IN ('draft','published','cancelled') AND valid_to IS NULL)
        OR (status = 'superseded' AND valid_to IS NOT NULL AND valid_to >= valid_from)
    ),
    ADD CONSTRAINT chk_mp_catalog_versions_kind_v1 CHECK (catalog_kind IN ('legacy','canonical')),
    ADD CONSTRAINT chk_mp_catalog_versions_revision_v1 CHECK (revision > 0 AND version_number > 0),
    ADD CONSTRAINT chk_mp_catalog_versions_cancellation_v1 CHECK (
        (status = 'cancelled' AND cancelled_at IS NOT NULL AND cancellation_reason IS NOT NULL AND CHAR_LENGTH(TRIM(cancellation_reason)) > 0)
        OR (status <> 'cancelled' AND cancelled_at IS NULL AND cancellation_reason IS NULL)
    );

ALTER TABLE military_position_types
    ADD COLUMN stable_key VARCHAR(160) NULL AFTER catalog_version_id,
    ADD COLUMN normalized_name VARCHAR(500) NULL AFTER name,
    ADD COLUMN full_name VARCHAR(255) NULL AFTER normalized_name,
    ADD COLUMN short_name VARCHAR(128) NULL AFTER full_name,
    ADD COLUMN is_combined BOOLEAN NOT NULL DEFAULT 0 AFTER short_name,
    ADD COLUMN source_type VARCHAR(20) NOT NULL DEFAULT 'official' AFTER is_combined,
    ADD COLUMN source_reference VARCHAR(1000) NULL AFTER source_type,
    ADD COLUMN note TEXT NULL AFTER source_reference,
    ADD COLUMN status VARCHAR(20) NOT NULL DEFAULT 'active' AFTER applicability_note,
    ADD COLUMN revision INT UNSIGNED NOT NULL DEFAULT 1 AFTER sort_order,
    ADD COLUMN created_by BIGINT UNSIGNED NULL AFTER created_at,
    ADD COLUMN updated_by BIGINT UNSIGNED NULL AFTER created_by,
    ADD COLUMN updated_at DATETIME(6) NULL AFTER updated_by;

UPDATE military_position_types
SET stable_key = CONCAT('legacy:', code),
    normalized_name = LOWER(TRIM(REGEXP_REPLACE(name, '[[:space:]]+', ' '))),
    full_name = NULL,
    short_name = NULL,
    is_combined = 0,
    source_type = 'official',
    source_reference = NULL,
    note = NULL,
    status = 'active',
    revision = 1,
    updated_at = created_at;

ALTER TABLE military_position_types
    MODIFY COLUMN stable_key VARCHAR(160) NOT NULL,
    MODIFY COLUMN normalized_name VARCHAR(500) NOT NULL,
    MODIFY COLUMN updated_at DATETIME(6) NOT NULL,
    ADD UNIQUE KEY uq_mp_types_stable_key_v1 (catalog_version_id, stable_key),
    ADD UNIQUE KEY uq_mp_types_normalized_name_v1 (catalog_version_id, normalized_name),
    ADD CONSTRAINT chk_mp_types_source_v1 CHECK (source_type IN ('official','local','imported')),
    ADD CONSTRAINT chk_mp_types_status_v1 CHECK (status IN ('active','archived')),
    ADD CONSTRAINT chk_mp_types_combined_v1 CHECK (is_combined IN (0,1)),
    ADD CONSTRAINT chk_mp_types_revision_v1 CHECK (revision > 0),
    ADD CONSTRAINT chk_mp_types_canonical_text_v1 CHECK (
        CHAR_LENGTH(TRIM(stable_key)) > 0
        AND CHAR_LENGTH(TRIM(normalized_name)) > 0
        AND (full_name IS NULL OR CHAR_LENGTH(TRIM(full_name)) > 0)
        AND (short_name IS NULL OR CHAR_LENGTH(TRIM(short_name)) > 0)
    );

CREATE TABLE IF NOT EXISTS military_position_change_events (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    catalog_version_id BIGINT UNSIGNED NOT NULL,
    actor_user_id BIGINT UNSIGNED NULL,
    event_type VARCHAR(80) NOT NULL,
    target_type VARCHAR(32) NOT NULL,
    target_id BIGINT UNSIGNED NOT NULL,
    before_state JSON NULL,
    after_state JSON NULL,
    reason VARCHAR(1000) NULL,
    created_at DATETIME(6) NOT NULL,
    KEY idx_mp_change_events_version (catalog_version_id, created_at, id),
    KEY idx_mp_change_events_target (target_type, target_id, created_at, id),
    CONSTRAINT fk_mp_change_events_version FOREIGN KEY (catalog_version_id) REFERENCES military_position_catalog_versions(id) ON UPDATE RESTRICT ON DELETE RESTRICT,
    CONSTRAINT fk_mp_change_events_actor FOREIGN KEY (actor_user_id) REFERENCES users(id) ON UPDATE RESTRICT ON DELETE SET NULL,
    CONSTRAINT chk_mp_change_events_type CHECK (event_type IN (
        'catalog.version.created','catalog.version.published','catalog.version.cancelled',
        'position.created','position.updated','position.archived','position.restored'
    )),
    CONSTRAINT chk_mp_change_events_target CHECK (target_type IN ('catalog_version','position')),
    CONSTRAINT chk_mp_change_events_reason CHECK (reason IS NULL OR CHAR_LENGTH(TRIM(reason)) > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TRIGGER trg_mp_cv_bi BEFORE INSERT ON military_position_catalog_versions FOR EACH ROW
BEGIN
    IF NEW.status <> 'draft' OR NEW.catalog_kind <> 'canonical' OR NEW.valid_to IS NOT NULL
       OR NEW.version_number < 1 OR NEW.revision <> 1
       OR CHAR_LENGTH(TRIM(NEW.version_label)) = 0 OR CHAR_LENGTH(TRIM(NEW.change_reason)) = 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'MP_CATALOG_INSERT_INVALID';
    END IF;
END;

CREATE TRIGGER trg_mp_cv_bu BEFORE UPDATE ON military_position_catalog_versions FOR EACH ROW
BEGIN
    IF NOT (NEW.code <=> OLD.code)
       OR NOT (NEW.version_number <=> OLD.version_number)
       OR NOT (NEW.catalog_kind <=> OLD.catalog_kind)
       OR NOT (NEW.rank_catalog_version_id <=> OLD.rank_catalog_version_id)
       OR NOT (NEW.organizational_element_catalog_version_id <=> OLD.organizational_element_catalog_version_id)
       OR NOT (NEW.created_by <=> OLD.created_by)
       OR NOT (NEW.created_at <=> OLD.created_at) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'MP_CATALOG_IDENTITY_IMMUTABLE';
    END IF;
    IF OLD.status <> 'draft' AND (
        NOT (NEW.name <=> OLD.name)
        OR NOT (NEW.version_label <=> OLD.version_label)
        OR NOT (NEW.coverage_note <=> OLD.coverage_note)
        OR NOT (NEW.valid_from <=> OLD.valid_from)
        OR NOT (NEW.verified_at <=> OLD.verified_at)
        OR NOT (NEW.change_reason <=> OLD.change_reason)
        OR NOT (NEW.published_by <=> OLD.published_by)
        OR NOT (NEW.published_at <=> OLD.published_at)
        OR NOT (NEW.cancelled_by <=> OLD.cancelled_by)
        OR NOT (NEW.cancelled_at <=> OLD.cancelled_at)
        OR NOT (NEW.cancellation_reason <=> OLD.cancellation_reason)
    ) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'MP_CATALOG_TERMINAL_CONTENT_IMMUTABLE';
    END IF;
    IF OLD.status = 'draft' AND NEW.status = 'draft' THEN
        IF NEW.revision <> OLD.revision + 1 OR NEW.valid_to IS NOT NULL
           OR NEW.published_at IS NOT NULL OR NEW.superseded_at IS NOT NULL OR NEW.cancelled_at IS NOT NULL THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'MP_CATALOG_REVISION_INVALID';
        END IF;
    ELSEIF OLD.status = 'draft' AND NEW.status = 'published' THEN
        IF NEW.revision <> OLD.revision + 1 OR NEW.valid_to IS NOT NULL
           OR NEW.published_at IS NULL OR NEW.published_by IS NULL THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'MP_CATALOG_PUBLISH_INVALID';
        END IF;
    ELSEIF OLD.status = 'draft' AND NEW.status = 'cancelled' THEN
        IF NEW.revision <> OLD.revision + 1 OR NEW.cancelled_at IS NULL OR NEW.cancelled_by IS NULL
           OR NEW.cancellation_reason IS NULL OR CHAR_LENGTH(TRIM(NEW.cancellation_reason)) = 0 THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'MP_CATALOG_CANCEL_INVALID';
        END IF;
    ELSEIF OLD.status = 'published' AND NEW.status = 'superseded' THEN
        IF NEW.revision <> OLD.revision + 1 OR NEW.valid_to IS NULL OR NEW.valid_to < NEW.valid_from
           OR NEW.superseded_at IS NULL OR NEW.superseded_by IS NULL THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'MP_CATALOG_SUPERSEDE_INVALID';
        END IF;
    ELSE
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'MP_CATALOG_TRANSITION_INVALID';
    END IF;
END;

CREATE TRIGGER trg_mp_cv_bd BEFORE DELETE ON military_position_catalog_versions FOR EACH ROW
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'MP_CATALOG_DELETE_FORBIDDEN';

CREATE TRIGGER trg_mp_t_bi BEFORE INSERT ON military_position_types FOR EACH ROW
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM military_position_catalog_versions v
        WHERE v.id = NEW.catalog_version_id AND v.status = 'draft' AND v.catalog_kind = 'canonical'
    ) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'MP_ENTRY_INSERT_DRAFT_ONLY';
    END IF;
    IF NEW.revision <> 1 OR NEW.sort_order < 1 OR CHAR_LENGTH(TRIM(NEW.stable_key)) = 0
       OR CHAR_LENGTH(TRIM(NEW.name)) = 0 OR CHAR_LENGTH(TRIM(NEW.normalized_name)) = 0
       OR NEW.source_type NOT IN ('official','local','imported') OR NEW.status NOT IN ('active','archived') THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'MP_ENTRY_INSERT_INVALID';
    END IF;
END;

CREATE TRIGGER trg_mp_t_bu BEFORE UPDATE ON military_position_types FOR EACH ROW
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM military_position_catalog_versions v
        WHERE v.id = OLD.catalog_version_id AND v.status = 'draft' AND v.catalog_kind = 'canonical'
    ) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'MP_ENTRY_UPDATE_DRAFT_ONLY';
    END IF;
    IF NOT (NEW.catalog_version_id <=> OLD.catalog_version_id)
       OR NOT (NEW.stable_key <=> OLD.stable_key)
       OR NOT (NEW.code <=> OLD.code)
       OR NOT (NEW.created_by <=> OLD.created_by)
       OR NOT (NEW.created_at <=> OLD.created_at) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'MP_ENTRY_IDENTITY_IMMUTABLE';
    END IF;
    IF NEW.revision <> OLD.revision + 1 OR NEW.sort_order < 1
       OR CHAR_LENGTH(TRIM(NEW.name)) = 0 OR CHAR_LENGTH(TRIM(NEW.normalized_name)) = 0
       OR NEW.source_type NOT IN ('official','local','imported') OR NEW.status NOT IN ('active','archived') THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'MP_ENTRY_REVISION_INVALID';
    END IF;
END;

CREATE TRIGGER trg_mp_t_bd BEFORE DELETE ON military_position_types FOR EACH ROW
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'MP_ENTRY_DELETE_FORBIDDEN';

DROP TRIGGER IF EXISTS trg_staffing_slots_insert;
CREATE TRIGGER trg_staffing_slots_insert BEFORE INSERT ON staffing_slots FOR EACH ROW
BEGIN
    IF (SELECT status FROM staffing_versions WHERE id=NEW.staffing_version_id) <> 'draft' THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='STAFFING_SLOT_INSERT_DRAFT_ONLY';
    END IF;
    IF NOT EXISTS (
        SELECT 1 FROM military_position_types t
        JOIN military_position_catalog_versions v ON v.id=t.catalog_version_id
        WHERE t.id=NEW.position_type_id AND t.catalog_version_id=NEW.position_catalog_version_id
          AND (v.catalog_kind='legacy' OR t.status='active')
    ) OR (
        NEW.position_variant_id IS NOT NULL AND NOT EXISTS (
            SELECT 1 FROM military_position_variants p
            WHERE p.id=NEW.position_variant_id AND p.catalog_version_id=NEW.position_catalog_version_id
              AND p.position_type_id=NEW.position_type_id
        )
    ) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='STAFFING_SLOT_POSITION_INVALID';
    END IF;
    IF (NEW.minimum_rank_id IS NOT NULL AND NOT EXISTS(SELECT 1 FROM military_rank_levels r WHERE r.id=NEW.minimum_rank_id AND r.catalog_version_id=NEW.rank_catalog_version_id))
       OR (NEW.maximum_rank_id IS NOT NULL AND NOT EXISTS(SELECT 1 FROM military_rank_levels r WHERE r.id=NEW.maximum_rank_id AND r.catalog_version_id=NEW.rank_catalog_version_id))
       OR (NEW.preferred_rank_id IS NOT NULL AND NOT EXISTS(SELECT 1 FROM military_rank_levels r WHERE r.id=NEW.preferred_rank_id AND r.catalog_version_id=NEW.rank_catalog_version_id)) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='STAFFING_SLOT_RANK_INVALID';
    END IF;
END;

DROP TRIGGER IF EXISTS trg_staffing_slots_update;
CREATE TRIGGER trg_staffing_slots_update BEFORE UPDATE ON staffing_slots FOR EACH ROW
BEGIN
    IF (SELECT status FROM staffing_versions WHERE id=OLD.staffing_version_id) <> 'draft' THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='STAFFING_SLOT_UPDATE_DRAFT_ONLY';
    END IF;
    IF NOT(NEW.staffing_register_id<=>OLD.staffing_register_id)
       OR NOT(NEW.staffing_version_id<=>OLD.staffing_version_id)
       OR NOT(NEW.staffing_slot_identity_id<=>OLD.staffing_slot_identity_id)
       OR NOT(NEW.organizational_structure_id<=>OLD.organizational_structure_id)
       OR NOT(NEW.organizational_structure_version_id<=>OLD.organizational_structure_version_id)
       OR NOT(NEW.position_catalog_version_id<=>OLD.position_catalog_version_id)
       OR NOT(NEW.rank_catalog_version_id<=>OLD.rank_catalog_version_id)
       OR NOT(NEW.vus_catalog_version_id<=>OLD.vus_catalog_version_id) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='STAFFING_SLOT_IDENTITY_IMMUTABLE';
    END IF;
    IF NOT EXISTS (
        SELECT 1 FROM military_position_types t
        JOIN military_position_catalog_versions v ON v.id=t.catalog_version_id
        WHERE t.id=NEW.position_type_id AND t.catalog_version_id=NEW.position_catalog_version_id
          AND (((NEW.position_type_id <=> OLD.position_type_id) AND (NEW.position_variant_id <=> OLD.position_variant_id))
               OR v.catalog_kind='legacy' OR t.status='active')
    ) OR (
        NEW.position_variant_id IS NOT NULL AND NOT EXISTS (
            SELECT 1 FROM military_position_variants p
            WHERE p.id=NEW.position_variant_id AND p.catalog_version_id=NEW.position_catalog_version_id
              AND p.position_type_id=NEW.position_type_id
        )
    ) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='STAFFING_SLOT_POSITION_INVALID';
    END IF;
    IF (NEW.minimum_rank_id IS NOT NULL AND NOT EXISTS(SELECT 1 FROM military_rank_levels r WHERE r.id=NEW.minimum_rank_id AND r.catalog_version_id=NEW.rank_catalog_version_id))
       OR (NEW.maximum_rank_id IS NOT NULL AND NOT EXISTS(SELECT 1 FROM military_rank_levels r WHERE r.id=NEW.maximum_rank_id AND r.catalog_version_id=NEW.rank_catalog_version_id))
       OR (NEW.preferred_rank_id IS NOT NULL AND NOT EXISTS(SELECT 1 FROM military_rank_levels r WHERE r.id=NEW.preferred_rank_id AND r.catalog_version_id=NEW.rank_catalog_version_id)) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='STAFFING_SLOT_RANK_INVALID';
    END IF;
END;

CREATE TRIGGER trg_mp_event_bu BEFORE UPDATE ON military_position_change_events FOR EACH ROW
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'MP_EVENT_APPEND_ONLY';

CREATE TRIGGER trg_mp_event_bd BEFORE DELETE ON military_position_change_events FOR EACH ROW
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'MP_EVENT_APPEND_ONLY';

INSERT INTO permissions (code, name, description, is_system, created_at, updated_at)
SELECT 'directories.military_positions.view', 'Просмотр справочника воинских должностей', 'Просмотр версий и канонических наименований воинских должностей.', TRUE, @mp14_now, @mp14_now
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE code = 'directories.military_positions.view');
INSERT INTO permissions (code, name, description, is_system, created_at, updated_at)
SELECT 'directories.military_positions.manage', 'Изменение справочника воинских должностей', 'Создание и изменение записей в черновой версии справочника.', TRUE, @mp14_now, @mp14_now
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE code = 'directories.military_positions.manage');
INSERT INTO permissions (code, name, description, is_system, created_at, updated_at)
SELECT 'directories.military_positions.publish', 'Публикация справочника воинских должностей', 'Публикация и отмена черновых версий справочника.', TRUE, @mp14_now, @mp14_now
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE code = 'directories.military_positions.publish');
INSERT INTO permissions (code, name, description, is_system, created_at, updated_at)
SELECT 'directories.military_positions.history', 'История справочника воинских должностей', 'Просмотр предметной истории версий и должностей.', TRUE, @mp14_now, @mp14_now
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE code = 'directories.military_positions.history');

SELECT id, rank_catalog_version_id, organizational_element_catalog_version_id
INTO @mp14_legacy_version_id, @mp14_rank_version_id, @mp14_org_version_id
FROM military_position_catalog_versions
WHERE status = 'published'
LIMIT 1;
SELECT COALESCE(MAX(version_number), 0) + 1
INTO @mp14_next_version_number
FROM military_position_catalog_versions;

INSERT INTO military_position_catalog_versions (
    version_number, code, name, version_label, coverage_note, catalog_kind, status,
    valid_from, valid_to, revision, change_reason, verified_at,
    rank_catalog_version_id, organizational_element_catalog_version_id,
    created_by, created_at, updated_by, updated_at
) VALUES (
    @mp14_next_version_number,
    'asu-canonical-military-positions-v1',
    'Канонические наименования воинских должностей',
    'Первичная каноническая редакция',
    'Утверждённый синтетический initial set из 24 канонических наименований.',
    'canonical', 'draft', CURRENT_DATE(), NULL, 1,
    'Создание первичной управляемой канонической версии справочника.', CURRENT_DATE(),
    @mp14_rank_version_id, @mp14_org_version_id,
    NULL, @mp14_now, NULL, @mp14_now
);
SET @mp14_canonical_version_id = LAST_INSERT_ID();

INSERT INTO military_position_types (
    catalog_version_id, stable_key, code, name, normalized_name, full_name, short_name,
    is_combined, source_type, source_reference, note, description, applicability_note,
    status, sort_order, revision, created_at, created_by, updated_by, updated_at
) VALUES
(@mp14_canonical_version_id,'canonical-position-001','canonical-001','Командир роты','командир роты',NULL,NULL,0,'local',NULL,NULL,'Каноническое наименование воинской должности.','Утверждённая синтетическая запись initial canonical draft.','active',1,1,@mp14_now,NULL,NULL,@mp14_now),
(@mp14_canonical_version_id,'canonical-position-002','canonical-002','Заместитель командира роты по военно-политической работе','заместитель командира роты по военно-политической работе',NULL,NULL,0,'local',NULL,NULL,'Каноническое наименование воинской должности.','Утверждённая синтетическая запись initial canonical draft.','active',2,1,@mp14_now,NULL,NULL,@mp14_now),
(@mp14_canonical_version_id,'canonical-position-003','canonical-003','Старшина','старшина',NULL,NULL,0,'local',NULL,NULL,'Каноническое наименование воинской должности.','Утверждённая синтетическая запись initial canonical draft.','active',3,1,@mp14_now,NULL,NULL,@mp14_now),
(@mp14_canonical_version_id,'canonical-position-004','canonical-004','Санитарный инструктор','санитарный инструктор',NULL,NULL,0,'local',NULL,NULL,'Каноническое наименование воинской должности.','Утверждённая синтетическая запись initial canonical draft.','active',4,1,@mp14_now,NULL,NULL,@mp14_now),
(@mp14_canonical_version_id,'canonical-position-005','canonical-005','Командир взвода','командир взвода',NULL,NULL,0,'local',NULL,NULL,'Каноническое наименование воинской должности.','Утверждённая синтетическая запись initial canonical draft.','active',5,1,@mp14_now,NULL,NULL,@mp14_now),
(@mp14_canonical_version_id,'canonical-position-006','canonical-006','Начальник аппаратной-техник','начальник аппаратной-техник',NULL,NULL,1,'local',NULL,NULL,'Каноническое наименование воинской должности.','Утверждённая синтетическая запись initial canonical draft.','active',6,1,@mp14_now,NULL,NULL,@mp14_now),
(@mp14_canonical_version_id,'canonical-position-007','canonical-007','Техник','техник',NULL,NULL,0,'local',NULL,NULL,'Каноническое наименование воинской должности.','Утверждённая синтетическая запись initial canonical draft.','active',7,1,@mp14_now,NULL,NULL,@mp14_now),
(@mp14_canonical_version_id,'canonical-position-008','canonical-008','Оператор','оператор',NULL,NULL,0,'local',NULL,NULL,'Каноническое наименование воинской должности.','Утверждённая синтетическая запись initial canonical draft.','active',8,1,@mp14_now,NULL,NULL,@mp14_now),
(@mp14_canonical_version_id,'canonical-position-009','canonical-009','Командир отделения','командир отделения',NULL,NULL,0,'local',NULL,NULL,'Каноническое наименование воинской должности.','Утверждённая синтетическая запись initial canonical draft.','active',9,1,@mp14_now,NULL,NULL,@mp14_now),
(@mp14_canonical_version_id,'canonical-position-010','canonical-010','Старший механик','старший механик',NULL,NULL,0,'local',NULL,NULL,'Каноническое наименование воинской должности.','Утверждённая синтетическая запись initial canonical draft.','active',10,1,@mp14_now,NULL,NULL,@mp14_now),
(@mp14_canonical_version_id,'canonical-position-011','canonical-011','Механик','механик',NULL,NULL,0,'local',NULL,NULL,'Каноническое наименование воинской должности.','Утверждённая синтетическая запись initial canonical draft.','active',11,1,@mp14_now,NULL,NULL,@mp14_now),
(@mp14_canonical_version_id,'canonical-position-012','canonical-012','Начальник радиостанции','начальник радиостанции',NULL,NULL,0,'local',NULL,NULL,'Каноническое наименование воинской должности.','Утверждённая синтетическая запись initial canonical draft.','active',12,1,@mp14_now,NULL,NULL,@mp14_now),
(@mp14_canonical_version_id,'canonical-position-013','canonical-013','Механик-радиотелефонист','механик-радиотелефонист',NULL,NULL,1,'local',NULL,NULL,'Каноническое наименование воинской должности.','Утверждённая синтетическая запись initial canonical draft.','active',13,1,@mp14_now,NULL,NULL,@mp14_now),
(@mp14_canonical_version_id,'canonical-position-014','canonical-014','Радиотелеграфист','радиотелеграфист',NULL,NULL,0,'local',NULL,NULL,'Каноническое наименование воинской должности.','Утверждённая синтетическая запись initial canonical draft.','active',14,1,@mp14_now,NULL,NULL,@mp14_now),
(@mp14_canonical_version_id,'canonical-position-015','canonical-015','Водитель-электрик','водитель-электрик',NULL,NULL,1,'local',NULL,NULL,'Каноническое наименование воинской должности.','Утверждённая синтетическая запись initial canonical draft.','active',15,1,@mp14_now,NULL,NULL,@mp14_now),
(@mp14_canonical_version_id,'canonical-position-016','canonical-016','Радиотелефонист','радиотелефонист',NULL,NULL,0,'local',NULL,NULL,'Каноническое наименование воинской должности.','Утверждённая синтетическая запись initial canonical draft.','active',16,1,@mp14_now,NULL,NULL,@mp14_now),
(@mp14_canonical_version_id,'canonical-position-017','canonical-017','Водитель-радиотелефонист','водитель-радиотелефонист',NULL,NULL,1,'local',NULL,NULL,'Каноническое наименование воинской должности.','Утверждённая синтетическая запись initial canonical draft.','active',17,1,@mp14_now,NULL,NULL,@mp14_now),
(@mp14_canonical_version_id,'canonical-position-018','canonical-018','Заместитель командира взвода-командир отделения','заместитель командира взвода-командир отделения',NULL,NULL,1,'local',NULL,NULL,'Каноническое наименование воинской должности.','Утверждённая синтетическая запись initial canonical draft.','active',18,1,@mp14_now,NULL,NULL,@mp14_now),
(@mp14_canonical_version_id,'canonical-position-019','canonical-019','Регулировщик','регулировщик',NULL,NULL,0,'local',NULL,NULL,'Каноническое наименование воинской должности.','Утверждённая синтетическая запись initial canonical draft.','active',19,1,@mp14_now,NULL,NULL,@mp14_now),
(@mp14_canonical_version_id,'canonical-position-020','canonical-020','Регулировщик-наводчик','регулировщик-наводчик',NULL,NULL,1,'local',NULL,NULL,'Каноническое наименование воинской должности.','Утверждённая синтетическая запись initial canonical draft.','active',20,1,@mp14_now,NULL,NULL,@mp14_now),
(@mp14_canonical_version_id,'canonical-position-021','canonical-021','Регулировщик-радиотелефонист','регулировщик-радиотелефонист',NULL,NULL,1,'local',NULL,NULL,'Каноническое наименование воинской должности.','Утверждённая синтетическая запись initial canonical draft.','active',21,1,@mp14_now,NULL,NULL,@mp14_now),
(@mp14_canonical_version_id,'canonical-position-022','canonical-022','Водитель-регулировщик','водитель-регулировщик',NULL,NULL,1,'local',NULL,NULL,'Каноническое наименование воинской должности.','Утверждённая синтетическая запись initial canonical draft.','active',22,1,@mp14_now,NULL,NULL,@mp14_now),
(@mp14_canonical_version_id,'canonical-position-023','canonical-023','Водитель','водитель',NULL,NULL,0,'local',NULL,NULL,'Каноническое наименование воинской должности.','Утверждённая синтетическая запись initial canonical draft.','active',23,1,@mp14_now,NULL,NULL,@mp14_now),
(@mp14_canonical_version_id,'canonical-position-024','canonical-024','Водитель-гранатометчик','водитель-гранатометчик',NULL,NULL,1,'local',NULL,NULL,'Каноническое наименование воинской должности.','Утверждённая синтетическая запись initial canonical draft.','active',24,1,@mp14_now,NULL,NULL,@mp14_now);

INSERT INTO military_position_change_events (
    catalog_version_id, actor_user_id, event_type, target_type, target_id,
    before_state, after_state, reason, created_at
) VALUES (
    @mp14_canonical_version_id, NULL, 'catalog.version.created', 'catalog_version', @mp14_canonical_version_id,
    NULL,
    JSON_OBJECT('status','draft','entry_count',24,'catalog_kind','canonical'),
    'Создание первичной канонической версии migration 014.', @mp14_now
);
