CREATE TRIGGER trg_military_rank_catalog_versions_before_update
BEFORE UPDATE ON military_rank_catalog_versions
FOR EACH ROW
BEGIN
    DECLARE composition_count INT DEFAULT 0;
    DECLARE semantics_count INT DEFAULT 0;
    DECLARE rank_count INT DEFAULT 0;
    DECLARE version_source_count INT DEFAULT 0;
    DECLARE composition_source_count INT DEFAULT 0;
    DECLARE selectable_count INT DEFAULT 0;
    DECLARE derived_count INT DEFAULT 0;
    DECLARE composition_anchor_count INT DEFAULT 0;
    DECLARE semantics_anchor_count INT DEFAULT 0;
    DECLARE rank_anchor_count INT DEFAULT 0;
    DECLARE version_source_anchor_count INT DEFAULT 0;
    DECLARE composition_source_anchor_count INT DEFAULT 0;

    IF NOT (NEW.code <=> OLD.code)
        OR NOT (NEW.name <=> OLD.name)
        OR NOT (NEW.valid_from <=> OLD.valid_from)
        OR NOT (NEW.verified_at <=> OLD.verified_at)
        OR NOT (NEW.created_by <=> OLD.created_by)
        OR NOT (NEW.created_at <=> OLD.created_at) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'MILITARY_RANK_VERSION_IDENTITY_IMMUTABLE';
    END IF;

    IF OLD.lifecycle_status = 'building' AND NEW.lifecycle_status = 'published' THEN
        IF NEW.is_current <> 1 OR NEW.published_at IS NULL
            OR NEW.valid_to IS NOT NULL OR NEW.superseded_at IS NOT NULL THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'MILITARY_RANK_VERSION_PUBLISH_STATE_INVALID';
        END IF;

        IF NEW.code = 'rf-military-ranks-staffing-scopes-v2' THEN
            SELECT COUNT(*) INTO composition_count
            FROM military_personnel_compositions WHERE catalog_version_id = OLD.id;
            SELECT COUNT(*) INTO semantics_count
            FROM military_personnel_composition_semantics WHERE catalog_version_id = OLD.id;
            SELECT COUNT(*) INTO rank_count
            FROM military_rank_levels WHERE catalog_version_id = OLD.id;
            SELECT COUNT(*) INTO version_source_count
            FROM military_rank_catalog_version_sources WHERE catalog_version_id = OLD.id;
            SELECT COUNT(*) INTO composition_source_count
            FROM military_personnel_composition_sources WHERE catalog_version_id = OLD.id;
            SELECT COUNT(*) INTO selectable_count
            FROM military_personnel_composition_semantics
            WHERE catalog_version_id = OLD.id AND is_staffing_selectable = 1;
            SELECT COUNT(*) INTO derived_count
            FROM military_personnel_composition_semantics
            WHERE catalog_version_id = OLD.id AND classification_kind = 'derived-staffing-scope';

            SELECT COUNT(*) INTO composition_anchor_count
            FROM military_personnel_compositions c
            LEFT JOIN military_personnel_compositions p
                ON p.id = c.parent_id AND p.catalog_version_id = c.catalog_version_id
            WHERE c.catalog_version_id = OLD.id AND (
                (c.code = 'enlisted' AND c.name = 'Солдаты, матросы, сержанты и старшины' AND c.parent_id IS NULL AND c.sort_order = 10) OR
                (c.code = 'soldiers-and-sailors' AND c.name = 'Солдаты и матросы' AND p.code = 'enlisted' AND c.sort_order = 11) OR
                (c.code = 'sergeants-and-starshinas' AND c.name = 'Сержанты и старшины' AND p.code = 'enlisted' AND c.sort_order = 12) OR
                (c.code = 'warrant-officers' AND c.name = 'Прапорщики и мичманы' AND c.parent_id IS NULL AND c.sort_order = 20) OR
                (c.code = 'officers' AND c.name = 'Офицеры' AND c.parent_id IS NULL AND c.sort_order = 30) OR
                (c.code = 'junior-officers' AND c.name = 'Младшие офицеры' AND p.code = 'officers' AND c.sort_order = 31) OR
                (c.code = 'senior-officers' AND c.name = 'Старшие офицеры' AND p.code = 'officers' AND c.sort_order = 32) OR
                (c.code = 'higher-officers' AND c.name = 'Высшие офицеры' AND p.code = 'officers' AND c.sort_order = 33)
            );

            SELECT COUNT(*) INTO semantics_anchor_count
            FROM military_personnel_composition_semantics s
            JOIN military_personnel_compositions c
                ON c.id = s.composition_id AND c.catalog_version_id = s.catalog_version_id
            WHERE s.catalog_version_id = OLD.id AND (
                (c.code IN ('soldiers-and-sailors', 'sergeants-and-starshinas')
                    AND s.classification_kind = 'derived-staffing-scope'
                    AND s.is_staffing_selectable = 1
                    AND s.derivation_note IS NOT NULL AND CHAR_LENGTH(TRIM(s.derivation_note)) > 0) OR
                (c.code IN ('warrant-officers', 'officers')
                    AND s.classification_kind = 'normative-composition'
                    AND s.is_staffing_selectable = 1) OR
                (c.code IN ('enlisted', 'junior-officers', 'senior-officers', 'higher-officers')
                    AND s.classification_kind = 'normative-composition'
                    AND s.is_staffing_selectable = 0)
            );

            SELECT COUNT(*) INTO rank_anchor_count
            FROM military_rank_levels r
            JOIN military_personnel_compositions c
                ON c.id = r.composition_id AND c.catalog_version_id = r.catalog_version_id
            WHERE r.catalog_version_id = OLD.id AND (
                (r.code = 'private' AND r.troop_name = 'рядовой' AND r.naval_name = 'матрос' AND r.sort_order = 1 AND c.code = 'soldiers-and-sailors') OR
                (r.code = 'corporal' AND r.troop_name = 'ефрейтор' AND r.naval_name = 'старший матрос' AND r.sort_order = 2 AND c.code = 'soldiers-and-sailors') OR
                (r.code = 'junior-sergeant' AND r.troop_name = 'младший сержант' AND r.naval_name = 'старшина 2 статьи' AND r.sort_order = 3 AND c.code = 'sergeants-and-starshinas') OR
                (r.code = 'sergeant' AND r.troop_name = 'сержант' AND r.naval_name = 'старшина 1 статьи' AND r.sort_order = 4 AND c.code = 'sergeants-and-starshinas') OR
                (r.code = 'senior-sergeant' AND r.troop_name = 'старший сержант' AND r.naval_name = 'главный старшина' AND r.sort_order = 5 AND c.code = 'sergeants-and-starshinas') OR
                (r.code = 'starshina' AND r.troop_name = 'старшина' AND r.naval_name = 'главный корабельный старшина' AND r.sort_order = 6 AND c.code = 'sergeants-and-starshinas') OR
                (r.code = 'warrant-officer' AND r.troop_name = 'прапорщик' AND r.naval_name = 'мичман' AND r.sort_order = 7 AND c.code = 'warrant-officers') OR
                (r.code = 'senior-warrant-officer' AND r.troop_name = 'старший прапорщик' AND r.naval_name = 'старший мичман' AND r.sort_order = 8 AND c.code = 'warrant-officers') OR
                (r.code = 'junior-lieutenant' AND r.troop_name = 'младший лейтенант' AND r.naval_name = 'младший лейтенант' AND r.sort_order = 9 AND c.code = 'junior-officers') OR
                (r.code = 'lieutenant' AND r.troop_name = 'лейтенант' AND r.naval_name = 'лейтенант' AND r.sort_order = 10 AND c.code = 'junior-officers') OR
                (r.code = 'senior-lieutenant' AND r.troop_name = 'старший лейтенант' AND r.naval_name = 'старший лейтенант' AND r.sort_order = 11 AND c.code = 'junior-officers') OR
                (r.code = 'captain' AND r.troop_name = 'капитан' AND r.naval_name = 'капитан-лейтенант' AND r.sort_order = 12 AND c.code = 'junior-officers') OR
                (r.code = 'major' AND r.troop_name = 'майор' AND r.naval_name = 'капитан 3 ранга' AND r.sort_order = 13 AND c.code = 'senior-officers') OR
                (r.code = 'lieutenant-colonel' AND r.troop_name = 'подполковник' AND r.naval_name = 'капитан 2 ранга' AND r.sort_order = 14 AND c.code = 'senior-officers') OR
                (r.code = 'colonel' AND r.troop_name = 'полковник' AND r.naval_name = 'капитан 1 ранга' AND r.sort_order = 15 AND c.code = 'senior-officers') OR
                (r.code = 'major-general' AND r.troop_name = 'генерал-майор' AND r.naval_name = 'контр-адмирал' AND r.sort_order = 16 AND c.code = 'higher-officers') OR
                (r.code = 'lieutenant-general' AND r.troop_name = 'генерал-лейтенант' AND r.naval_name = 'вице-адмирал' AND r.sort_order = 17 AND c.code = 'higher-officers') OR
                (r.code = 'colonel-general' AND r.troop_name = 'генерал-полковник' AND r.naval_name = 'адмирал' AND r.sort_order = 18 AND c.code = 'higher-officers') OR
                (r.code = 'army-general' AND r.troop_name = 'генерал армии' AND r.naval_name = 'адмирал флота' AND r.sort_order = 19 AND c.code = 'higher-officers') OR
                (r.code = 'marshal-russian-federation' AND r.troop_name = 'Маршал Российской Федерации' AND r.naval_name IS NULL AND r.sort_order = 20 AND c.code = 'higher-officers')
            );

            SELECT COUNT(*) INTO version_source_anchor_count
            FROM military_rank_catalog_version_sources vs
            JOIN legal_sources ls ON ls.id = vs.legal_source_id
            WHERE vs.catalog_version_id = OLD.id AND (
                (ls.code = 'federal-law-53-fz-article-46' AND vs.source_role = 'primary-list' AND vs.sort_order = 1) OR
                (ls.code = 'presidential-decree-1237-article-20' AND vs.source_role = 'equivalence-and-order' AND vs.sort_order = 2)
            );

            SELECT COUNT(*) INTO composition_source_anchor_count
            FROM military_personnel_composition_sources cs
            JOIN military_personnel_compositions c
                ON c.id = cs.composition_id AND c.catalog_version_id = cs.catalog_version_id
            JOIN legal_sources ls ON ls.id = cs.legal_source_id
            WHERE cs.catalog_version_id = OLD.id AND cs.sort_order = 1 AND (
                (c.code IN ('enlisted', 'warrant-officers', 'officers')
                    AND ls.code = 'federal-law-53-fz-article-46'
                    AND cs.source_role = 'normative-definition') OR
                (c.code IN ('soldiers-and-sailors', 'sergeants-and-starshinas')
                    AND ls.code = 'federal-law-53-fz-article-46'
                    AND cs.source_role = 'derived-classification-basis'
                    AND cs.note IS NOT NULL AND CHAR_LENGTH(TRIM(cs.note)) > 0) OR
                (c.code IN ('junior-officers', 'senior-officers', 'higher-officers')
                    AND ls.code = 'presidential-decree-1237-article-20'
                    AND cs.source_role = 'rank-list')
            );

            IF composition_count <> 8 OR semantics_count <> 8 OR rank_count <> 20
                OR version_source_count <> 2 OR composition_source_count <> 8
                OR selectable_count <> 4 OR derived_count <> 2
                OR composition_anchor_count <> 8 OR semantics_anchor_count <> 8
                OR rank_anchor_count <> 20 OR version_source_anchor_count <> 2
                OR composition_source_anchor_count <> 8 THEN
                SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'MILITARY_RANK_V2_PUBLICATION_INCOMPLETE';
            END IF;
        END IF;
    ELSEIF OLD.lifecycle_status = 'published' AND NEW.lifecycle_status = 'superseded' THEN
        IF NEW.is_current <> 0 OR NEW.published_at IS NULL
            OR (OLD.published_at IS NOT NULL AND NOT (NEW.published_at <=> OLD.published_at))
            OR NEW.valid_to IS NULL OR NEW.superseded_at IS NULL THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'MILITARY_RANK_VERSION_SUPERSEDE_STATE_INVALID';
        END IF;
    ELSEIF OLD.lifecycle_status = NEW.lifecycle_status THEN
        IF NOT (NEW.is_current <=> OLD.is_current)
            OR NOT (NEW.valid_to <=> OLD.valid_to)
            OR NOT (NEW.published_at <=> OLD.published_at)
            OR NOT (NEW.superseded_at <=> OLD.superseded_at) THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'MILITARY_RANK_VERSION_LIFECYCLE_UPDATE_INVALID';
        END IF;
    ELSE
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'MILITARY_RANK_VERSION_TRANSITION_INVALID';
    END IF;
END;
