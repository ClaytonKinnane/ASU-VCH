CREATE TABLE IF NOT EXISTS organizational_structures (
id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
code VARCHAR(64) NOT NULL,
display_name VARCHAR(255) NOT NULL,
short_name VARCHAR(128) NULL,
status VARCHAR(20) NOT NULL DEFAULT 'active',
created_by BIGINT UNSIGNED NULL,
created_at DATETIME NOT NULL,
updated_by BIGINT UNSIGNED NULL,
updated_at DATETIME NOT NULL,
archived_by BIGINT UNSIGNED NULL,
archived_at DATETIME NULL,
archive_reason VARCHAR(1000) NULL,
restored_by BIGINT UNSIGNED NULL,
restored_at DATETIME NULL,
restore_reason VARCHAR(1000) NULL,
UNIQUE KEY uq_organizational_structures_code (code),
KEY idx_organizational_structures_status (status, display_name),
KEY idx_organizational_structures_created_by (created_by),
KEY idx_organizational_structures_updated_by (updated_by),
CONSTRAINT fk_organizational_structures_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON UPDATE RESTRICT ON DELETE SET NULL,
CONSTRAINT fk_organizational_structures_updated_by FOREIGN KEY (updated_by) REFERENCES users(id) ON UPDATE RESTRICT ON DELETE SET NULL,
CONSTRAINT fk_organizational_structures_archived_by FOREIGN KEY (archived_by) REFERENCES users(id) ON UPDATE RESTRICT ON DELETE SET NULL,
CONSTRAINT fk_organizational_structures_restored_by FOREIGN KEY (restored_by) REFERENCES users(id) ON UPDATE RESTRICT ON DELETE SET NULL,
CONSTRAINT chk_organizational_structures_code CHECK (BINARY code = BINARY LOWER(code) AND code REGEXP '^[a-z0-9][a-z0-9-]{1,63}$'),
CONSTRAINT chk_organizational_structures_name CHECK (CHAR_LENGTH(TRIM(display_name)) > 0),
CONSTRAINT chk_organizational_structures_short_name CHECK (short_name IS NULL OR CHAR_LENGTH(TRIM(short_name)) > 0),
CONSTRAINT chk_organizational_structures_status CHECK (status IN ('active', 'archived')),
CONSTRAINT chk_organizational_structures_archive_state CHECK (
(status = 'active') OR (archived_at IS NOT NULL AND archive_reason IS NOT NULL AND CHAR_LENGTH(TRIM(archive_reason)) > 0)
)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS organizational_structure_elements (
id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
organizational_structure_id BIGINT UNSIGNED NOT NULL,
created_by BIGINT UNSIGNED NULL,
created_at DATETIME NOT NULL,
UNIQUE KEY uq_org_structure_elements_id_structure (id, organizational_structure_id),
KEY idx_org_structure_elements_structure (organizational_structure_id, id),
CONSTRAINT fk_org_structure_elements_structure FOREIGN KEY (organizational_structure_id) REFERENCES organizational_structures(id) ON UPDATE RESTRICT ON DELETE RESTRICT,
CONSTRAINT fk_org_structure_elements_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON UPDATE RESTRICT ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS organizational_structure_versions (
id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
organizational_structure_id BIGINT UNSIGNED NOT NULL,
based_on_version_id BIGINT UNSIGNED NULL,
catalog_version_id BIGINT UNSIGNED NOT NULL,
version_number INT UNSIGNED NOT NULL,
status VARCHAR(20) NOT NULL DEFAULT 'draft',
effective_from DATE NULL,
effective_to DATE NULL,
change_reason VARCHAR(1000) NOT NULL,
revision INT UNSIGNED NOT NULL DEFAULT 1,
pending_guard BIGINT UNSIGNED GENERATED ALWAYS AS (
CASE WHEN status IN ('draft', 'approved') THEN organizational_structure_id ELSE NULL END
) STORED,
active_guard BIGINT UNSIGNED GENERATED ALWAYS AS (
CASE WHEN status = 'active' THEN organizational_structure_id ELSE NULL END
) STORED,
created_by BIGINT UNSIGNED NULL,
created_at DATETIME NOT NULL,
updated_by BIGINT UNSIGNED NULL,
updated_at DATETIME NOT NULL,
approved_by BIGINT UNSIGNED NULL,
approved_at DATETIME NULL,
activated_by BIGINT UNSIGNED NULL,
activated_at DATETIME NULL,
cancelled_by BIGINT UNSIGNED NULL,
cancelled_at DATETIME NULL,
cancellation_reason VARCHAR(1000) NULL,
UNIQUE KEY uq_org_structure_versions_number (organizational_structure_id, version_number),
UNIQUE KEY uq_org_structure_versions_id_structure (id, organizational_structure_id),
UNIQUE KEY uq_org_structure_versions_id_structure_catalog (id, organizational_structure_id, catalog_version_id),
UNIQUE KEY uq_org_structure_versions_pending_guard (pending_guard),
UNIQUE KEY uq_org_structure_versions_active_guard (active_guard),
KEY idx_org_structure_versions_structure_status (organizational_structure_id, status, version_number),
KEY idx_org_structure_versions_catalog (catalog_version_id),
CONSTRAINT fk_org_structure_versions_structure FOREIGN KEY (organizational_structure_id) REFERENCES organizational_structures(id) ON UPDATE RESTRICT ON DELETE RESTRICT,
CONSTRAINT fk_org_structure_versions_based_on FOREIGN KEY (based_on_version_id, organizational_structure_id) REFERENCES organizational_structure_versions(id, organizational_structure_id) ON UPDATE RESTRICT ON DELETE RESTRICT,
CONSTRAINT fk_org_structure_versions_catalog FOREIGN KEY (catalog_version_id) REFERENCES organizational_element_catalog_versions(id) ON UPDATE RESTRICT ON DELETE RESTRICT,
CONSTRAINT fk_org_structure_versions_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON UPDATE RESTRICT ON DELETE SET NULL,
CONSTRAINT fk_org_structure_versions_updated_by FOREIGN KEY (updated_by) REFERENCES users(id) ON UPDATE RESTRICT ON DELETE SET NULL,
CONSTRAINT fk_org_structure_versions_approved_by FOREIGN KEY (approved_by) REFERENCES users(id) ON UPDATE RESTRICT ON DELETE SET NULL,
CONSTRAINT fk_org_structure_versions_activated_by FOREIGN KEY (activated_by) REFERENCES users(id) ON UPDATE RESTRICT ON DELETE SET NULL,
CONSTRAINT fk_org_structure_versions_cancelled_by FOREIGN KEY (cancelled_by) REFERENCES users(id) ON UPDATE RESTRICT ON DELETE SET NULL,
CONSTRAINT chk_org_structure_versions_number CHECK (version_number > 0),
CONSTRAINT chk_org_structure_versions_status CHECK (status IN ('draft', 'approved', 'active', 'superseded', 'cancelled')),
CONSTRAINT chk_org_structure_versions_reason CHECK (CHAR_LENGTH(TRIM(change_reason)) > 0),
CONSTRAINT chk_org_structure_versions_revision CHECK (revision > 0),
CONSTRAINT chk_org_structure_versions_dates CHECK (effective_to IS NULL OR (effective_from IS NOT NULL AND effective_to >= effective_from)),
CONSTRAINT chk_org_structure_versions_approved_data CHECK (status NOT IN ('approved', 'active', 'superseded') OR (effective_from IS NOT NULL AND approved_at IS NOT NULL)),
CONSTRAINT chk_org_structure_versions_cancel_data CHECK (status <> 'cancelled' OR (cancelled_at IS NOT NULL AND cancellation_reason IS NOT NULL AND CHAR_LENGTH(TRIM(cancellation_reason)) > 0))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS organizational_structure_documents (
id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
organizational_structure_id BIGINT UNSIGNED NOT NULL,
document_type VARCHAR(128) NOT NULL,
document_date DATE NOT NULL,
document_number VARCHAR(128) NOT NULL,
title VARCHAR(255) NOT NULL,
note TEXT NULL,
created_by BIGINT UNSIGNED NULL,
created_at DATETIME NOT NULL,
updated_by BIGINT UNSIGNED NULL,
updated_at DATETIME NOT NULL,
UNIQUE KEY uq_org_structure_documents_id_structure (id, organizational_structure_id),
KEY idx_org_structure_documents_structure_date (organizational_structure_id, document_date, id),
CONSTRAINT fk_org_structure_documents_structure FOREIGN KEY (organizational_structure_id) REFERENCES organizational_structures(id) ON UPDATE RESTRICT ON DELETE RESTRICT,
CONSTRAINT fk_org_structure_documents_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON UPDATE RESTRICT ON DELETE SET NULL,
CONSTRAINT fk_org_structure_documents_updated_by FOREIGN KEY (updated_by) REFERENCES users(id) ON UPDATE RESTRICT ON DELETE SET NULL,
CONSTRAINT chk_org_structure_documents_type CHECK (CHAR_LENGTH(TRIM(document_type)) > 0),
CONSTRAINT chk_org_structure_documents_number CHECK (CHAR_LENGTH(TRIM(document_number)) > 0),
CONSTRAINT chk_org_structure_documents_title CHECK (CHAR_LENGTH(TRIM(title)) > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS organizational_structure_version_documents (
structure_version_id BIGINT UNSIGNED NOT NULL,
organizational_structure_id BIGINT UNSIGNED NOT NULL,
document_id BIGINT UNSIGNED NOT NULL,
document_role VARCHAR(32) NOT NULL,
sort_order INT UNSIGNED NOT NULL,
primary_guard BIGINT UNSIGNED GENERATED ALWAYS AS (
CASE WHEN document_role = 'primary_basis' THEN structure_version_id ELSE NULL END
) STORED,
created_by BIGINT UNSIGNED NULL,
created_at DATETIME NOT NULL,
PRIMARY KEY (structure_version_id, document_id),
UNIQUE KEY uq_org_structure_version_documents_order (structure_version_id, sort_order),
UNIQUE KEY uq_org_structure_version_documents_primary (primary_guard),
KEY idx_org_structure_version_documents_document (document_id, organizational_structure_id),
CONSTRAINT fk_org_structure_version_documents_version FOREIGN KEY (structure_version_id, organizational_structure_id) REFERENCES organizational_structure_versions(id, organizational_structure_id) ON UPDATE RESTRICT ON DELETE RESTRICT,
CONSTRAINT fk_org_structure_version_documents_document FOREIGN KEY (document_id, organizational_structure_id) REFERENCES organizational_structure_documents(id, organizational_structure_id) ON UPDATE RESTRICT ON DELETE RESTRICT,
CONSTRAINT fk_org_structure_version_documents_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON UPDATE RESTRICT ON DELETE SET NULL,
CONSTRAINT chk_org_structure_version_documents_role CHECK (document_role IN ('primary_basis', 'additional_basis', 'amendment')),
CONSTRAINT chk_org_structure_version_documents_order CHECK (sort_order > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS organizational_structure_nodes (
id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
organizational_structure_id BIGINT UNSIGNED NOT NULL,
structure_version_id BIGINT UNSIGNED NOT NULL,
catalog_version_id BIGINT UNSIGNED NOT NULL,
organizational_structure_element_id BIGINT UNSIGNED NOT NULL,
parent_node_id BIGINT UNSIGNED NULL,
organizational_element_type_id BIGINT UNSIGNED NOT NULL,
internal_code VARCHAR(64) NULL,
name VARCHAR(255) NOT NULL,
short_name VARCHAR(128) NULL,
sort_order INT UNSIGNED NOT NULL,
note TEXT NULL,
root_guard BIGINT UNSIGNED GENERATED ALWAYS AS (
CASE WHEN parent_node_id IS NULL THEN structure_version_id ELSE NULL END
) STORED,
created_by BIGINT UNSIGNED NULL,
created_at DATETIME NOT NULL,
updated_by BIGINT UNSIGNED NULL,
updated_at DATETIME NOT NULL,
UNIQUE KEY uq_org_structure_nodes_version_element (structure_version_id, organizational_structure_element_id),
UNIQUE KEY uq_org_structure_nodes_version_code (structure_version_id, internal_code),
UNIQUE KEY uq_org_structure_nodes_sibling_order (structure_version_id, parent_node_id, sort_order),
UNIQUE KEY uq_org_structure_nodes_root_guard (root_guard),
UNIQUE KEY uq_org_structure_nodes_id_version_structure (id, structure_version_id, organizational_structure_id),
KEY idx_org_structure_nodes_parent (structure_version_id, parent_node_id, sort_order),
KEY idx_org_structure_nodes_type (organizational_element_type_id, catalog_version_id),
KEY idx_org_structure_nodes_name (structure_version_id, name),
CONSTRAINT fk_org_structure_nodes_version FOREIGN KEY (structure_version_id, organizational_structure_id, catalog_version_id) REFERENCES organizational_structure_versions(id, organizational_structure_id, catalog_version_id) ON UPDATE RESTRICT ON DELETE RESTRICT,
CONSTRAINT fk_org_structure_nodes_element FOREIGN KEY (organizational_structure_element_id, organizational_structure_id) REFERENCES organizational_structure_elements(id, organizational_structure_id) ON UPDATE RESTRICT ON DELETE RESTRICT,
CONSTRAINT fk_org_structure_nodes_parent FOREIGN KEY (parent_node_id, structure_version_id, organizational_structure_id) REFERENCES organizational_structure_nodes(id, structure_version_id, organizational_structure_id) ON UPDATE RESTRICT ON DELETE RESTRICT,
CONSTRAINT fk_org_structure_nodes_type FOREIGN KEY (organizational_element_type_id, catalog_version_id) REFERENCES organizational_element_types(id, catalog_version_id) ON UPDATE RESTRICT ON DELETE RESTRICT,
CONSTRAINT fk_org_structure_nodes_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON UPDATE RESTRICT ON DELETE SET NULL,
CONSTRAINT fk_org_structure_nodes_updated_by FOREIGN KEY (updated_by) REFERENCES users(id) ON UPDATE RESTRICT ON DELETE SET NULL,
CONSTRAINT chk_org_structure_nodes_code CHECK (internal_code IS NULL OR internal_code REGEXP '^[A-Za-z0-9][A-Za-z0-9._/-]{0,63}$'),
CONSTRAINT chk_org_structure_nodes_name CHECK (CHAR_LENGTH(TRIM(name)) > 0),
CONSTRAINT chk_org_structure_nodes_short_name CHECK (short_name IS NULL OR CHAR_LENGTH(TRIM(short_name)) > 0),
CONSTRAINT chk_org_structure_nodes_order CHECK (sort_order > 0),
CONSTRAINT chk_org_structure_nodes_self_parent CHECK (parent_node_id IS NULL OR parent_node_id <> id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS organizational_structure_change_events (
id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
organizational_structure_id BIGINT UNSIGNED NOT NULL,
structure_version_id BIGINT UNSIGNED NULL,
organizational_structure_element_id BIGINT UNSIGNED NULL,
actor_user_id BIGINT UNSIGNED NULL,
event_type VARCHAR(80) NOT NULL,
before_state JSON NULL,
after_state JSON NULL,
reason VARCHAR(1000) NULL,
created_at DATETIME NOT NULL,
KEY idx_org_structure_change_events_structure (organizational_structure_id, created_at, id),
KEY idx_org_structure_change_events_version (structure_version_id, organizational_structure_id, created_at, id),
KEY idx_org_structure_change_events_element (organizational_structure_element_id, organizational_structure_id, created_at, id),
KEY idx_org_structure_change_events_actor (actor_user_id, created_at, id),
CONSTRAINT fk_org_structure_change_events_structure FOREIGN KEY (organizational_structure_id) REFERENCES organizational_structures(id) ON UPDATE RESTRICT ON DELETE RESTRICT,
CONSTRAINT fk_org_structure_change_events_version FOREIGN KEY (structure_version_id, organizational_structure_id) REFERENCES organizational_structure_versions(id, organizational_structure_id) ON UPDATE RESTRICT ON DELETE RESTRICT,
CONSTRAINT fk_org_structure_change_events_element FOREIGN KEY (organizational_structure_element_id, organizational_structure_id) REFERENCES organizational_structure_elements(id, organizational_structure_id) ON UPDATE RESTRICT ON DELETE RESTRICT,
CONSTRAINT fk_org_structure_change_events_actor FOREIGN KEY (actor_user_id) REFERENCES users(id) ON UPDATE RESTRICT ON DELETE SET NULL,
CONSTRAINT chk_org_structure_change_events_type CHECK (CHAR_LENGTH(TRIM(event_type)) > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
DROP TRIGGER IF EXISTS trg_org_structures_before_update;
DROP TRIGGER IF EXISTS trg_org_structures_before_delete;
DROP TRIGGER IF EXISTS trg_org_structure_elements_before_update;
DROP TRIGGER IF EXISTS trg_org_structure_elements_before_delete;
DROP TRIGGER IF EXISTS trg_org_structure_versions_before_update;
DROP TRIGGER IF EXISTS trg_org_structure_versions_before_delete;
DROP TRIGGER IF EXISTS trg_org_structure_nodes_before_insert;
DROP TRIGGER IF EXISTS trg_org_structure_nodes_before_update;
DROP TRIGGER IF EXISTS trg_org_structure_nodes_before_delete;
DROP TRIGGER IF EXISTS trg_org_structure_version_documents_before_insert;
DROP TRIGGER IF EXISTS trg_org_structure_version_documents_before_update;
DROP TRIGGER IF EXISTS trg_org_structure_version_documents_before_delete;
DROP TRIGGER IF EXISTS trg_org_structure_documents_before_update;
DROP TRIGGER IF EXISTS trg_org_structure_documents_before_delete;
DROP TRIGGER IF EXISTS trg_org_structure_change_events_before_update;
DROP TRIGGER IF EXISTS trg_org_structure_change_events_before_delete;
CREATE TRIGGER trg_org_structures_before_update
BEFORE UPDATE ON organizational_structures
FOR EACH ROW
BEGIN
IF NOT (NEW.code <=> OLD.code) OR
NOT (NEW.created_by <=> OLD.created_by) OR
NOT (NEW.created_at <=> OLD.created_at) THEN
SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'ORG_STRUCTURE_IDENTITY_IMMUTABLE';
END IF;
IF OLD.status = 'active' THEN
IF NEW.status = 'active' THEN
IF NOT (NEW.archived_by <=> OLD.archived_by) OR
NOT (NEW.archived_at <=> OLD.archived_at) OR
NOT (NEW.archive_reason <=> OLD.archive_reason) OR
NOT (NEW.restored_by <=> OLD.restored_by) OR
NOT (NEW.restored_at <=> OLD.restored_at) OR
NOT (NEW.restore_reason <=> OLD.restore_reason) THEN
SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'ORG_STRUCTURE_LIFECYCLE_INVALID';
END IF;
ELSEIF NEW.status = 'archived' THEN
IF NOT (NEW.display_name <=> OLD.display_name) OR NOT (NEW.short_name <=> OLD.short_name) OR
NEW.archived_by IS NULL OR NEW.archived_at IS NULL OR
NEW.archive_reason IS NULL OR CHAR_LENGTH(TRIM(NEW.archive_reason)) = 0 OR
NOT (NEW.restored_by <=> OLD.restored_by) OR
NOT (NEW.restored_at <=> OLD.restored_at) OR
NOT (NEW.restore_reason <=> OLD.restore_reason) OR
EXISTS (
SELECT 1 FROM organizational_structure_versions v
WHERE v.organizational_structure_id = OLD.id AND v.status IN ('draft', 'approved')
) THEN
SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'ORG_STRUCTURE_ARCHIVE_INVALID';
END IF;
ELSE
SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'ORG_STRUCTURE_TRANSITION_INVALID';
END IF;
ELSEIF OLD.status = 'archived' THEN
IF NEW.status <> 'active' THEN
SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'ORG_STRUCTURE_TRANSITION_INVALID';
END IF;
IF NOT (NEW.display_name <=> OLD.display_name) OR NOT (NEW.short_name <=> OLD.short_name) OR
NOT (NEW.archived_by <=> OLD.archived_by) OR
NOT (NEW.archived_at <=> OLD.archived_at) OR
NOT (NEW.archive_reason <=> OLD.archive_reason) OR
NEW.restored_by IS NULL OR NEW.restored_at IS NULL OR
NEW.restore_reason IS NULL OR CHAR_LENGTH(TRIM(NEW.restore_reason)) = 0 THEN
SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'ORG_STRUCTURE_RESTORE_INVALID';
END IF;
ELSE
SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'ORG_STRUCTURE_STATE_INVALID';
END IF;
END;
CREATE TRIGGER trg_org_structures_before_delete
BEFORE DELETE ON organizational_structures
FOR EACH ROW
BEGIN
SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'ORG_STRUCTURE_DELETE_FORBIDDEN';
END;
CREATE TRIGGER trg_org_structure_elements_before_update
BEFORE UPDATE ON organizational_structure_elements
FOR EACH ROW
BEGIN
SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'ORG_STRUCTURE_ELEMENT_IMMUTABLE';
END;
CREATE TRIGGER trg_org_structure_elements_before_delete
BEFORE DELETE ON organizational_structure_elements
FOR EACH ROW
BEGIN
SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'ORG_STRUCTURE_ELEMENT_IMMUTABLE';
END;
CREATE TRIGGER trg_org_structure_versions_before_update
BEFORE UPDATE ON organizational_structure_versions
FOR EACH ROW
BEGIN
IF NOT (NEW.organizational_structure_id <=> OLD.organizational_structure_id) OR
NOT (NEW.based_on_version_id <=> OLD.based_on_version_id) OR
NOT (NEW.catalog_version_id <=> OLD.catalog_version_id) OR
NOT (NEW.version_number <=> OLD.version_number) OR
NOT (NEW.created_by <=> OLD.created_by) OR
NOT (NEW.created_at <=> OLD.created_at) THEN
SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'ORG_STRUCTURE_VERSION_IDENTITY_IMMUTABLE';
END IF;
IF OLD.status = 'draft' THEN
IF NEW.status NOT IN ('draft', 'approved', 'cancelled') THEN
SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'ORG_STRUCTURE_VERSION_TRANSITION_INVALID';
END IF;
IF NEW.status = 'draft' AND (
NOT (NEW.effective_from <=> OLD.effective_from) OR
NOT (NEW.effective_to <=> OLD.effective_to) OR
NOT (NEW.approved_by <=> OLD.approved_by) OR
NOT (NEW.approved_at <=> OLD.approved_at) OR
NOT (NEW.activated_by <=> OLD.activated_by) OR
NOT (NEW.activated_at <=> OLD.activated_at) OR
NOT (NEW.cancelled_by <=> OLD.cancelled_by) OR
NOT (NEW.cancelled_at <=> OLD.cancelled_at) OR
NOT (NEW.cancellation_reason <=> OLD.cancellation_reason)
) THEN
SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'ORG_STRUCTURE_VERSION_LIFECYCLE_INVALID';
END IF;
IF NEW.status = 'approved' AND (
NEW.effective_from IS NULL OR NEW.effective_to IS NOT NULL OR
NEW.approved_by IS NULL OR NEW.approved_at IS NULL OR
NEW.activated_by IS NOT NULL OR NEW.activated_at IS NOT NULL OR
NEW.cancelled_by IS NOT NULL OR NEW.cancelled_at IS NOT NULL OR NEW.cancellation_reason IS NOT NULL
) THEN
SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'ORG_STRUCTURE_VERSION_APPROVAL_INVALID';
END IF;
IF NEW.status = 'approved' AND (
(SELECT COUNT(*) FROM organizational_structure_nodes n WHERE n.structure_version_id = OLD.id AND n.parent_node_id IS NULL) <> 1 OR
(SELECT COUNT(*) FROM organizational_structure_version_documents vd WHERE vd.structure_version_id = OLD.id AND vd.document_role = 'primary_basis') <> 1 OR
NOT EXISTS (
SELECT 1
FROM organizational_structure_nodes n
JOIN organizational_element_type_classes tc
ON tc.type_id = n.organizational_element_type_id
AND tc.catalog_version_id = n.catalog_version_id
JOIN organizational_element_classes c
ON c.id = tc.class_id
AND c.catalog_version_id = tc.catalog_version_id
WHERE n.structure_version_id = OLD.id
AND n.parent_node_id IS NULL
AND c.code = 'military-unit'
)
) THEN
SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'ORG_STRUCTURE_VERSION_NOT_PUBLISHABLE';
END IF;
IF NEW.status = 'cancelled' AND (
NOT (NEW.effective_from <=> OLD.effective_from) OR
NOT (NEW.effective_to <=> OLD.effective_to) OR
NOT (NEW.approved_by <=> OLD.approved_by) OR
NOT (NEW.approved_at <=> OLD.approved_at) OR
NOT (NEW.activated_by <=> OLD.activated_by) OR
NOT (NEW.activated_at <=> OLD.activated_at) OR
NEW.cancelled_by IS NULL OR NEW.cancelled_at IS NULL OR
NEW.cancellation_reason IS NULL OR CHAR_LENGTH(TRIM(NEW.cancellation_reason)) = 0
) THEN
SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'ORG_STRUCTURE_VERSION_CANCELLATION_INVALID';
END IF;
ELSEIF OLD.status = 'approved' THEN
IF NEW.status NOT IN ('active', 'cancelled') THEN
SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'ORG_STRUCTURE_VERSION_TRANSITION_INVALID';
END IF;
IF NOT (NEW.effective_from <=> OLD.effective_from) OR
NOT (NEW.approved_by <=> OLD.approved_by) OR
NOT (NEW.approved_at <=> OLD.approved_at) THEN
SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'ORG_STRUCTURE_VERSION_APPROVAL_IMMUTABLE';
END IF;
IF NEW.status = 'active' AND (
NEW.effective_to IS NOT NULL OR NEW.activated_by IS NULL OR NEW.activated_at IS NULL OR
NEW.cancelled_by IS NOT NULL OR NEW.cancelled_at IS NOT NULL OR NEW.cancellation_reason IS NOT NULL
) THEN
SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'ORG_STRUCTURE_VERSION_ACTIVATION_INVALID';
END IF;
IF NEW.status = 'cancelled' AND (
NOT (NEW.effective_to <=> OLD.effective_to) OR
NOT (NEW.activated_by <=> OLD.activated_by) OR
NOT (NEW.activated_at <=> OLD.activated_at) OR
NEW.cancelled_by IS NULL OR NEW.cancelled_at IS NULL OR
NEW.cancellation_reason IS NULL OR CHAR_LENGTH(TRIM(NEW.cancellation_reason)) = 0
) THEN
SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'ORG_STRUCTURE_VERSION_CANCELLATION_INVALID';
END IF;
ELSEIF OLD.status = 'active' THEN
IF NEW.status <> 'superseded' THEN
SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'ORG_STRUCTURE_VERSION_TRANSITION_INVALID';
END IF;
IF NOT (NEW.effective_from <=> OLD.effective_from) OR NEW.effective_to IS NULL OR
NOT (NEW.approved_by <=> OLD.approved_by) OR NOT (NEW.approved_at <=> OLD.approved_at) OR
NOT (NEW.activated_by <=> OLD.activated_by) OR NOT (NEW.activated_at <=> OLD.activated_at) OR
NOT (NEW.cancelled_by <=> OLD.cancelled_by) OR NOT (NEW.cancelled_at <=> OLD.cancelled_at) OR
NOT (NEW.cancellation_reason <=> OLD.cancellation_reason) THEN
SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'ORG_STRUCTURE_VERSION_SUPERSEDE_INVALID';
END IF;
ELSE
SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'ORG_STRUCTURE_VERSION_TERMINAL';
END IF;
IF NEW.status <> 'draft' AND (
NOT (NEW.organizational_structure_id <=> OLD.organizational_structure_id) OR
NOT (NEW.based_on_version_id <=> OLD.based_on_version_id) OR
NOT (NEW.catalog_version_id <=> OLD.catalog_version_id) OR
NOT (NEW.version_number <=> OLD.version_number) OR
NOT (NEW.change_reason <=> OLD.change_reason) OR
NOT (NEW.revision <=> OLD.revision) OR
NOT (NEW.created_by <=> OLD.created_by) OR
NOT (NEW.created_at <=> OLD.created_at)
) THEN
SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'ORG_STRUCTURE_VERSION_CONTENT_IMMUTABLE';
END IF;
END;
CREATE TRIGGER trg_org_structure_versions_before_delete
BEFORE DELETE ON organizational_structure_versions
FOR EACH ROW
BEGIN
SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'ORG_STRUCTURE_VERSION_DELETE_FORBIDDEN';
END;
CREATE TRIGGER trg_org_structure_nodes_before_insert
BEFORE INSERT ON organizational_structure_nodes
FOR EACH ROW
BEGIN
IF (SELECT status FROM organizational_structure_versions WHERE id = NEW.structure_version_id) <> 'draft' THEN
SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'ORG_STRUCTURE_NODE_VERSION_IMMUTABLE';
END IF;
END;
CREATE TRIGGER trg_org_structure_nodes_before_update
BEFORE UPDATE ON organizational_structure_nodes
FOR EACH ROW
BEGIN
IF OLD.structure_version_id <> NEW.structure_version_id OR
OLD.organizational_structure_id <> NEW.organizational_structure_id OR
OLD.catalog_version_id <> NEW.catalog_version_id OR
OLD.organizational_structure_element_id <> NEW.organizational_structure_element_id OR
NOT (OLD.created_by <=> NEW.created_by) OR
OLD.created_at <> NEW.created_at THEN
SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'ORG_STRUCTURE_NODE_OWNERSHIP_IMMUTABLE';
END IF;
IF (SELECT status FROM organizational_structure_versions WHERE id = OLD.structure_version_id) <> 'draft' THEN
SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'ORG_STRUCTURE_NODE_VERSION_IMMUTABLE';
END IF;
IF OLD.parent_node_id IS NULL AND NEW.parent_node_id IS NOT NULL THEN
SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'ORG_STRUCTURE_ROOT_MOVE_FORBIDDEN';
END IF;
END;
CREATE TRIGGER trg_org_structure_nodes_before_delete
BEFORE DELETE ON organizational_structure_nodes
FOR EACH ROW
BEGIN
IF (SELECT status FROM organizational_structure_versions WHERE id = OLD.structure_version_id) <> 'draft' THEN
SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'ORG_STRUCTURE_NODE_VERSION_IMMUTABLE';
END IF;
IF OLD.parent_node_id IS NULL THEN
SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'ORG_STRUCTURE_ROOT_DELETE_FORBIDDEN';
END IF;
END;
CREATE TRIGGER trg_org_structure_version_documents_before_insert
BEFORE INSERT ON organizational_structure_version_documents
FOR EACH ROW
BEGIN
IF (SELECT status FROM organizational_structure_versions WHERE id = NEW.structure_version_id) <> 'draft' THEN
SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'ORG_STRUCTURE_DOCUMENT_VERSION_IMMUTABLE';
END IF;
END;
CREATE TRIGGER trg_org_structure_version_documents_before_update
BEFORE UPDATE ON organizational_structure_version_documents
FOR EACH ROW
BEGIN
IF OLD.structure_version_id <> NEW.structure_version_id OR
OLD.organizational_structure_id <> NEW.organizational_structure_id OR
NOT (OLD.created_by <=> NEW.created_by) OR OLD.created_at <> NEW.created_at THEN
SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'ORG_STRUCTURE_DOCUMENT_OWNERSHIP_IMMUTABLE';
END IF;
IF (SELECT status FROM organizational_structure_versions WHERE id = OLD.structure_version_id) <> 'draft' THEN
SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'ORG_STRUCTURE_DOCUMENT_VERSION_IMMUTABLE';
END IF;
END;
CREATE TRIGGER trg_org_structure_version_documents_before_delete
BEFORE DELETE ON organizational_structure_version_documents
FOR EACH ROW
BEGIN
IF (SELECT status FROM organizational_structure_versions WHERE id = OLD.structure_version_id) <> 'draft' THEN
SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'ORG_STRUCTURE_DOCUMENT_VERSION_IMMUTABLE';
END IF;
END;
CREATE TRIGGER trg_org_structure_documents_before_update
BEFORE UPDATE ON organizational_structure_documents
FOR EACH ROW
BEGIN
IF OLD.organizational_structure_id <> NEW.organizational_structure_id OR
NOT (OLD.created_by <=> NEW.created_by) OR OLD.created_at <> NEW.created_at THEN
SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'ORG_STRUCTURE_DOCUMENT_IDENTITY_IMMUTABLE';
END IF;
IF EXISTS (
SELECT 1
FROM organizational_structure_version_documents vd
JOIN organizational_structure_versions v ON v.id = vd.structure_version_id
WHERE vd.document_id = OLD.id AND v.status <> 'draft'
) THEN
SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'ORG_STRUCTURE_DOCUMENT_IMMUTABLE';
END IF;
END;
CREATE TRIGGER trg_org_structure_documents_before_delete
BEFORE DELETE ON organizational_structure_documents
FOR EACH ROW
BEGIN
IF EXISTS (
SELECT 1
FROM organizational_structure_version_documents vd
JOIN organizational_structure_versions v ON v.id = vd.structure_version_id
WHERE vd.document_id = OLD.id AND v.status <> 'draft'
) THEN
SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'ORG_STRUCTURE_DOCUMENT_IMMUTABLE';
END IF;
END;
CREATE TRIGGER trg_org_structure_change_events_before_update
BEFORE UPDATE ON organizational_structure_change_events
FOR EACH ROW
BEGIN
SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'ORG_STRUCTURE_CHANGE_EVENT_IMMUTABLE';
END;
CREATE TRIGGER trg_org_structure_change_events_before_delete
BEFORE DELETE ON organizational_structure_change_events
FOR EACH ROW
BEGIN
SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'ORG_STRUCTURE_CHANGE_EVENT_IMMUTABLE';
END;
INSERT INTO permissions (code, name, description, is_system, created_at, updated_at) VALUES
('organization.structures.view', 'Просмотр организационной структуры', 'Просмотр фактических организационных структур, версий и документов-оснований.', 1, NOW(), NOW()),
('organization.structures.create', 'Создание организационной структуры', 'Создание контейнера фактической организационной структуры и первоначального черновика.', 1, NOW(), NOW()),
('organization.structures.update', 'Изменение организационной структуры', 'Редактирование черновых версий, узлов и документов-оснований.', 1, NOW(), NOW()),
('organization.structures.publish', 'Публикация организационной структуры', 'Утверждение, ввод в действие и отмена версий организационной структуры.', 1, NOW(), NOW()),
('organization.structures.archive', 'Архивирование организационной структуры', 'Архивирование и восстановление контейнеров организационных структур.', 1, NOW(), NOW()),
('organization.structures.history', 'История организационной структуры', 'Просмотр неизменяемой предметной истории изменений организационной структуры.', 1, NOW(), NOW())
ON DUPLICATE KEY UPDATE
name = VALUES(name),
description = VALUES(description),
is_system = 1,
updated_at = VALUES(updated_at);
