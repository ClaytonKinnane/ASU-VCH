CREATE TRIGGER trg_military_rank_version_sources_before_insert
BEFORE INSERT ON military_rank_catalog_version_sources
FOR EACH ROW
BEGIN
    IF COALESCE((SELECT lifecycle_status FROM military_rank_catalog_versions WHERE id = NEW.catalog_version_id), '') <> 'building' THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'MILITARY_RANK_VERSION_SOURCE_INSERT_FORBIDDEN';
    END IF;
END;

CREATE TRIGGER trg_military_rank_version_sources_before_update
BEFORE UPDATE ON military_rank_catalog_version_sources
FOR EACH ROW
BEGIN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'MILITARY_RANK_VERSION_SOURCE_UPDATE_FORBIDDEN';
END;

CREATE TRIGGER trg_military_rank_version_sources_before_delete
BEFORE DELETE ON military_rank_catalog_version_sources
FOR EACH ROW
BEGIN
    IF COALESCE((SELECT lifecycle_status FROM military_rank_catalog_versions WHERE id = OLD.catalog_version_id), '') <> 'building' THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'MILITARY_RANK_VERSION_SOURCE_DELETE_FORBIDDEN';
    END IF;
END;
