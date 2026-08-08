CREATE TABLE IF NOT EXISTS personnel_records (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    last_name VARCHAR(100) NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    middle_name VARCHAR(100) NULL,
    birth_date DATE NOT NULL,
    birth_place VARCHAR(255) NULL,
    citizenship VARCHAR(100) NULL,
    nationality VARCHAR(100) NULL,
    religion VARCHAR(150) NULL,
    record_status VARCHAR(16) NOT NULL DEFAULT 'active',
    revision INT UNSIGNED NOT NULL DEFAULT 1,
    created_by BIGINT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL,
    updated_by BIGINT UNSIGNED NOT NULL,
    updated_at DATETIME NOT NULL,
    archived_by BIGINT UNSIGNED NULL,
    archived_at DATETIME NULL,
    archive_reason VARCHAR(500) NULL,
    KEY idx_personnel_records_status_name (record_status, last_name, first_name, middle_name, id),
    KEY idx_personnel_records_birth_date (birth_date),
    CONSTRAINT fk_personnel_records_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON UPDATE RESTRICT ON DELETE RESTRICT,
    CONSTRAINT fk_personnel_records_updated_by FOREIGN KEY (updated_by) REFERENCES users(id) ON UPDATE RESTRICT ON DELETE RESTRICT,
    CONSTRAINT fk_personnel_records_archived_by FOREIGN KEY (archived_by) REFERENCES users(id) ON UPDATE RESTRICT ON DELETE RESTRICT,
    CONSTRAINT chk_personnel_records_status CHECK (record_status IN ('active','archived')),
    CONSTRAINT chk_personnel_records_revision CHECK (revision > 0),
    CONSTRAINT chk_personnel_records_archive_metadata CHECK (
        (record_status = 'active' AND archived_by IS NULL AND archived_at IS NULL AND archive_reason IS NULL)
        OR
        (record_status = 'archived' AND archived_by IS NOT NULL AND archived_at IS NOT NULL AND archive_reason IS NOT NULL)
    )
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS personnel_identifier_types (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(64) NOT NULL,
    name VARCHAR(120) NOT NULL,
    description VARCHAR(500) NULL,
    enforce_global_unique TINYINT(1) NOT NULL DEFAULT 0,
    is_system TINYINT(1) NOT NULL DEFAULT 1,
    sort_order INT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL,
    UNIQUE KEY uq_personnel_identifier_types_code (code),
    UNIQUE KEY uq_personnel_identifier_types_binding (id, enforce_global_unique),
    UNIQUE KEY uq_personnel_identifier_types_sort (sort_order),
    CONSTRAINT chk_personnel_identifier_types_unique CHECK (enforce_global_unique IN (0,1)),
    CONSTRAINT chk_personnel_identifier_types_system CHECK (is_system IN (0,1)),
    CONSTRAINT chk_personnel_identifier_types_sort CHECK (sort_order > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO personnel_identifier_types
    (code, name, description, enforce_global_unique, is_system, sort_order, created_at)
VALUES
    ('personal_number', 'Личный номер', 'Основной служебный личный номер. Историческое значение не переиспользуется.', 1, 1, 10, NOW()),
    ('service_dog_tag', 'Жетон', 'Номер жетона военнослужащего. Историческое значение не переиспользуется.', 1, 1, 20, NOW()),
    ('table_number', 'Табельный номер', 'Табельный номер. Повторное использование допускается политикой типа после завершения предыдущего интервала.', 0, 1, 30, NOW()),
    ('call_sign', 'Позывной', 'Позывной. Повторное использование допускается политикой типа после завершения предыдущего интервала.', 0, 1, 40, NOW());

CREATE TABLE IF NOT EXISTS personnel_identifiers (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    personnel_id BIGINT UNSIGNED NOT NULL,
    identifier_type_id BIGINT UNSIGNED NOT NULL,
    enforce_global_unique TINYINT(1) NOT NULL,
    value VARCHAR(255) NOT NULL,
    valid_from DATE NULL,
    valid_to DATE NULL,
    note VARCHAR(500) NULL,
    created_by BIGINT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL,
    ended_by BIGINT UNSIGNED NULL,
    ended_at DATETIME NULL,
    active_guard TINYINT GENERATED ALWAYS AS (CASE WHEN valid_to IS NULL THEN 1 ELSE NULL END) STORED,
    global_unique_key VARCHAR(300) GENERATED ALWAYS AS (
        CASE WHEN enforce_global_unique = 1 THEN CONCAT(CAST(identifier_type_id AS CHAR), ':', value) ELSE NULL END
    ) STORED,
    UNIQUE KEY uq_personnel_identifiers_active (personnel_id, identifier_type_id, active_guard),
    UNIQUE KEY uq_personnel_identifiers_global_history (global_unique_key),
    KEY idx_personnel_identifiers_person_type (personnel_id, identifier_type_id),
    KEY idx_personnel_identifiers_type_value (identifier_type_id, value),
    CONSTRAINT fk_personnel_identifiers_person FOREIGN KEY (personnel_id) REFERENCES personnel_records(id) ON UPDATE RESTRICT ON DELETE RESTRICT,
    CONSTRAINT fk_personnel_identifiers_type_policy FOREIGN KEY (identifier_type_id, enforce_global_unique)
        REFERENCES personnel_identifier_types(id, enforce_global_unique) ON UPDATE RESTRICT ON DELETE RESTRICT,
    CONSTRAINT fk_personnel_identifiers_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON UPDATE RESTRICT ON DELETE RESTRICT,
    CONSTRAINT fk_personnel_identifiers_ended_by FOREIGN KEY (ended_by) REFERENCES users(id) ON UPDATE RESTRICT ON DELETE RESTRICT,
    CONSTRAINT chk_personnel_identifiers_policy CHECK (enforce_global_unique IN (0,1)),
    CONSTRAINT chk_personnel_identifiers_value CHECK (CHAR_LENGTH(TRIM(value)) > 0),
    CONSTRAINT chk_personnel_identifiers_interval CHECK (valid_to IS NULL OR valid_from IS NULL OR valid_to >= valid_from),
    CONSTRAINT chk_personnel_identifiers_end_metadata CHECK (
        (valid_to IS NULL AND ended_by IS NULL AND ended_at IS NULL)
        OR
        (valid_to IS NOT NULL AND ended_by IS NOT NULL AND ended_at IS NOT NULL)
    )
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS personnel_change_events (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    personnel_id BIGINT UNSIGNED NOT NULL,
    actor_user_id BIGINT UNSIGNED NOT NULL,
    event_type VARCHAR(80) NOT NULL,
    target_type VARCHAR(40) NOT NULL,
    target_id BIGINT UNSIGNED NULL,
    revision_from INT UNSIGNED NULL,
    revision_to INT UNSIGNED NULL,
    before_state JSON NULL,
    after_state JSON NULL,
    reason VARCHAR(500) NULL,
    occurred_at DATETIME NOT NULL,
    KEY idx_personnel_change_events_person_time (personnel_id, occurred_at, id),
    KEY idx_personnel_change_events_actor_time (actor_user_id, occurred_at, id),
    CONSTRAINT fk_personnel_change_events_person FOREIGN KEY (personnel_id) REFERENCES personnel_records(id) ON UPDATE RESTRICT ON DELETE RESTRICT,
    CONSTRAINT fk_personnel_change_events_actor FOREIGN KEY (actor_user_id) REFERENCES users(id) ON UPDATE RESTRICT ON DELETE RESTRICT,
    CONSTRAINT chk_personnel_change_events_revision CHECK (
        (revision_from IS NULL AND revision_to IS NULL)
        OR
        (revision_from IS NOT NULL AND revision_to = revision_from + 1)
    )
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TRIGGER IF EXISTS trg_personnel_records_update;
CREATE TRIGGER trg_personnel_records_update
BEFORE UPDATE ON personnel_records
FOR EACH ROW
BEGIN
    IF NEW.id <> OLD.id OR NEW.created_by <> OLD.created_by OR NEW.created_at <> OLD.created_at THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'PERSONNEL_RECORD_IDENTITY_IMMUTABLE';
    END IF;
    IF NEW.revision <> OLD.revision + 1 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'PERSONNEL_RECORD_REVISION_INVALID';
    END IF;
    IF OLD.record_status = 'active' AND NEW.record_status NOT IN ('active','archived') THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'PERSONNEL_RECORD_STATUS_TRANSITION_INVALID';
    END IF;
    IF OLD.record_status = 'archived' AND NEW.record_status NOT IN ('archived','active') THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'PERSONNEL_RECORD_STATUS_TRANSITION_INVALID';
    END IF;
END;

DROP TRIGGER IF EXISTS trg_personnel_records_delete;
CREATE TRIGGER trg_personnel_records_delete
BEFORE DELETE ON personnel_records
FOR EACH ROW
BEGIN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'PERSONNEL_RECORD_DELETE_FORBIDDEN';
END;

DROP TRIGGER IF EXISTS trg_personnel_identifier_types_update;
CREATE TRIGGER trg_personnel_identifier_types_update
BEFORE UPDATE ON personnel_identifier_types
FOR EACH ROW
BEGIN
    IF OLD.is_system = 1 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'PERSONNEL_IDENTIFIER_TYPE_SYSTEM_IMMUTABLE';
    END IF;
END;

DROP TRIGGER IF EXISTS trg_personnel_identifier_types_delete;
CREATE TRIGGER trg_personnel_identifier_types_delete
BEFORE DELETE ON personnel_identifier_types
FOR EACH ROW
BEGIN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'PERSONNEL_IDENTIFIER_TYPE_DELETE_FORBIDDEN';
END;

DROP TRIGGER IF EXISTS trg_personnel_identifiers_insert;
CREATE TRIGGER trg_personnel_identifiers_insert
BEFORE INSERT ON personnel_identifiers
FOR EACH ROW
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM personnel_records p
        WHERE p.id = NEW.personnel_id AND p.record_status = 'active'
    ) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'PERSONNEL_IDENTIFIER_ACTIVE_RECORD_REQUIRED';
    END IF;
    IF NEW.valid_to IS NOT NULL OR NEW.ended_by IS NOT NULL OR NEW.ended_at IS NOT NULL THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'PERSONNEL_IDENTIFIER_NEW_MUST_BE_ACTIVE';
    END IF;
END;

DROP TRIGGER IF EXISTS trg_personnel_identifiers_update;
CREATE TRIGGER trg_personnel_identifiers_update
BEFORE UPDATE ON personnel_identifiers
FOR EACH ROW
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM personnel_records p
        WHERE p.id = OLD.personnel_id AND p.record_status = 'active'
    ) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'PERSONNEL_IDENTIFIER_ACTIVE_RECORD_REQUIRED';
    END IF;
    IF NEW.id <> OLD.id
        OR NEW.personnel_id <> OLD.personnel_id
        OR NEW.identifier_type_id <> OLD.identifier_type_id
        OR NEW.enforce_global_unique <> OLD.enforce_global_unique
        OR NEW.value <> OLD.value
        OR NOT (NEW.valid_from <=> OLD.valid_from)
        OR NOT (NEW.note <=> OLD.note)
        OR NEW.created_by <> OLD.created_by
        OR NEW.created_at <> OLD.created_at THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'PERSONNEL_IDENTIFIER_IDENTITY_IMMUTABLE';
    END IF;
    IF OLD.valid_to IS NOT NULL THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'PERSONNEL_IDENTIFIER_ALREADY_ENDED';
    END IF;
    IF NEW.valid_to IS NULL OR NEW.ended_by IS NULL OR NEW.ended_at IS NULL THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'PERSONNEL_IDENTIFIER_END_METADATA_REQUIRED';
    END IF;
END;

DROP TRIGGER IF EXISTS trg_personnel_identifiers_delete;
CREATE TRIGGER trg_personnel_identifiers_delete
BEFORE DELETE ON personnel_identifiers
FOR EACH ROW
BEGIN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'PERSONNEL_IDENTIFIER_DELETE_FORBIDDEN';
END;

DROP TRIGGER IF EXISTS trg_personnel_change_events_update;
CREATE TRIGGER trg_personnel_change_events_update
BEFORE UPDATE ON personnel_change_events
FOR EACH ROW
BEGIN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'PERSONNEL_EVENT_APPEND_ONLY';
END;

DROP TRIGGER IF EXISTS trg_personnel_change_events_delete;
CREATE TRIGGER trg_personnel_change_events_delete
BEFORE DELETE ON personnel_change_events
FOR EACH ROW
BEGIN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'PERSONNEL_EVENT_APPEND_ONLY';
END;
