CREATE TRIGGER trg_military_rank_levels_before_insert
BEFORE INSERT ON military_rank_levels
FOR EACH ROW
BEGIN
    IF COALESCE((SELECT lifecycle_status FROM military_rank_catalog_versions WHERE id = NEW.catalog_version_id), '') <> 'building' THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'MILITARY_RANK_LEVEL_INSERT_FORBIDDEN';
    END IF;
END;

CREATE TRIGGER trg_military_rank_levels_before_update
BEFORE UPDATE ON military_rank_levels
FOR EACH ROW
BEGIN
    IF COALESCE((SELECT lifecycle_status FROM military_rank_catalog_versions WHERE id = OLD.catalog_version_id), '') <> 'building'
        OR NEW.catalog_version_id <> OLD.catalog_version_id THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'MILITARY_RANK_LEVEL_UPDATE_FORBIDDEN';
    END IF;
END;

CREATE TRIGGGER trg_military_rank_levels_before_delete
BEFORE DELETE ON military_rank_levels
FOR EACH ROW
BEGIN
    IF COALESCE((SELECT lifecycle_status FROM military_rank_catalog_versions WHEQHYH”òÿ][Ÿ◊›ô\ú⁄[€ó⁄Y
K	… Hà	ÿùZ[[ô…»SÇà“Q”êS‘S’UH	ÕL	»—UQT‘–Q—W’VH	”RSUTñW‘êSí◊”UëS—SUW—ì‘êíQSâŒ¬àSëQé¬ëSë¬Ç