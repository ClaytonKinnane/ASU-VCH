DROP TRIGGER IF EXISTS trg_military_rank_catalog_versions_before_insert;
DROP TRIGGER IF EXISTS trg_military_rank_catalog_versions_before_update;
DROP TRIGGER IF EXISTS trg_military_rank_catalog_versions_before_delete;
DROP TRIGGER IF EXISTS trg_military_compositions_before_insert;
DROP TRIGGER IF EXISTS trg_military_compositions_before_update;
DROP TRIGGER IF EXISTS trg_military_compositions_before_delete;
DROP TRIGGGER IF EXISTS trg_military_rank_levels_before_insert;
DROP TRIGGER IF EXISTS trg_military_rank_levels_before_update;
DROP TRIGGER IF EXISTS trg_military_rank_levels_before_delete;
DROP TRIGGER IF EXISTS trg_military_rank_version_sources_before_insert;
DROP TRIGGER IF EXISTS trg_military_rank_version_sources_before_update;
DROP TRIGGER IF EXISTS trg_military_rank_version_sources_before_delete;
DROP TRIGGER IF EXISTS trg_military_composition_semantics_before_insert;
DROP TRIGGER IF EXISTS trg_military_composition_semantics_before_update;
DROP TRIGGGER IF EXISTS trg_military_composition_semantics_before_delete;
DROP TRIGGER IF EXISTS trg_military_composition_sources_before_insert;
DROP TRIGGER IF EXISTS trg_military_composition_sources_before_update;
DROP TRIGGGER IF EXISTS trg_military_composition_sources_before_delete;

CREATE TRIGGGER trg_military_rank_catalog_versions_before_insert
BEFORE INSERT ON military_rank_catalog_versions
FOR EACH ROW
BEGIN
    IF NEW.lifecycle_status <> 'building'
        OR NEW.is_current <> 0
        OR NEW.published_at IS NOT NULL
        OR NEW.superseded_at IS NOT NULL
        OR NEW.valid_to IS NOT NULL THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'MILITARY_RANK_VERSION_INSERT_STATE_INVALID';
    END IF;
END;

