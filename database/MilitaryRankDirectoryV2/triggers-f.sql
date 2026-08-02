CREATE TRIGGER trg_military_composition_semantics_before_insert
BEFORE INSERT ON military_personnel_composition_semantics
FOR EACH ROW
BEGIN
    IF COALESCE((SELECT lifecycle_status FROM military_rank_catalog_versions WHERE id = NEW.catalog_version_id), '') <> 'building' THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'MILITARY_RANK_SEMANTICS_INSERT_FORBIDDEN';
    END IF;
    IF NEW.classification_kind = 'derived-staffing-scope'
        AND COALESCE((SELECT parent_id FROM military_personnel_compositions
            WHERE id = NEW.composition_id AND catalog_version_id = NEW.catalog_version_id), 0) = 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'MILITARY_RANK_DERIVED_SCOPE_PARENT_REQUIRED';
    END IF;
END;

CREATE TRIGGER trg_military_composition_semantics_before_update
BEFORE UPDATE ON military_personnel_composition_semantics
FOR EACH ROW
BEGIN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'MILITARY_RANK_SEMANTICS_UPDATE_FORBIDDEN';
END;

CREATE TRIGGGER trg_military_composition_semantics_before_delete
BEFORE DELETE ON military_personnel_composition_semantics
FOR EACH ROW
BEGIN
    IF COALESCE((SELECT lifecycle_status FROM military_rank_catalog_versions WHEQHYH”òÿ][Ÿ◊›ô\ú⁄[€ó⁄Y
K	… Hà	ÿùZ[[ô…»SÇà“Q”êS‘S’UH	ÕL	»—UQT‘–Q—W’VH	”RSUTñW‘êSí◊‘—SPSïP‘◊—SUW—ì‘êíQSâŒ¬àSëQé¬ëSë¬Ç