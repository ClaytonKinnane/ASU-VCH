START TRANSACTION;

SET @rank_v2_now = NOW();
SET @rank_v1_id = NULL;
SELECT id INTO @rank_v1_id
FROM military_rank_catalog_versions
WHERE code = 'rf-military-ranks-2026-07-27'
LIMIT 1 FOR UPDATE;

SET @rank_existing_v2_id = NULL;
SELECT id INTO @rank_existing_v2_id
FROM military_rank_catalog_versions
WHERE code = 'rf-military-ranks-staffing-scopes-v2'
LIMIT 1 FOR UPDATE;

DELETE FROM military_personnel_composition_sources
WHERE catalog_version_id = @rank_existing_v2_id;
DELETE FROM military_rank_catalog_version_sources
WHERE catalog_version_id = @rank_existing_v2_id;
DELETE FROM military_rank_levels
WHERE catalog_version_id = @rank_existing_v2_id;
DELETE FROM military_personnel_composition_semantics
WHERE catalog_version_id = @rank_existing_v2_id;
DELETE FROM military_personnel_compositions
WHERE catalog_version_id = @rank_existing_v2_id AND parent_id IS NOT NULL;
DELETE FROM military_personnel_compositions
WHERE catalog_version_id = @rank_existing_v2_id AND parent_id IS NULL;
DELETE FROM military_rank_catalog_versions
WHERE id = @rank_existing_v2_id AND lifecycle_status = 'building';

INSERT INTO military_rank_catalog_versions
    (code, name, is_current, lifecycle_status, valid_from, valid_to, verified_at,
     published_at, superseded_at, created_by, created_at)
VALUES
    ('rf-military-ranks-staffing-scopes-v2',
     'Составы военнослужащих и воинские звания Российской Федерации — версия с категориями для штатных должностей',
     FALSE, 'building', '2026-08-03', NULL, '2026-08-02', NULL, NULL, NULL, @rank_v2_now);

SET @rank_v2_id = LAST_INSERT_ID();
SET @rank_law_source_id = (
    SELECT id FROM legal_sources WHERE code = 'federal-law-53-fz-article-46' LIMIT 1
);
SET @rank_decree_source_id = (
    SELECT id FROM legal_sources WHERE code = 'presidential-decree-1237-article-20' LIMIT 1
);

UPDATE legal_sources
SET verified_at = '2026-08-02'
WHERE id IN (@rank_law_source_id, @rank_decree_source_id);

INSERT INTO military_rank_catalog_version_sources
    (catalog_version_id, legal_source_id, source_role, sort_order)
VALUES
    (@rank_v2_id, @rank_law_source_id, 'primary-list', 1),
    (@rank_v2_id, @rank_decree_source_id, 'equivalence-and-order', 2);

INSERT INTO military_personnel_compositions
    (catalog_version_id, parent_id, code, name, sort_order, created_at)
VALUES
    (@rank_v2_id, NULL, 'enlisted', 'Солдаты, матросы, сержанты и старшины', 10, @rank_v2_now),
    (@rank_v2_id, NULL, 'warrant-officers', 'Прапорщики и мичманы', 20, @rank_v2_now),
    (@rank_v2_id, NULL, 'officers', 'Офицеры', 30, @rank_v2_now);

SET @rank_enlisted_id = (
    SELECT id FROM military_personnel_compositions
    WHERE catalog_version_id = @rank_v2_id AND code = 'enlisted'
);
SET @rank_warrant_id = (
    SELECT id FROM military_personnel_compositions
    WHERE catalog_version_id = @rank_v2_id AND code = 'warrant-officers'
);
SET @rank_officers_id = (
    SELECT id FROM military_personnel_compositions
    WHERE catalog_version_id = @rank_v2_id AND code = 'officers'
);

INSERT INTO military_personnel_compositions
    (catalog_version_id, parent_id, code, name, sort_order, created_at)
VALUES
    (@rank_v2_id, @rank_enlisted_id, 'soldiers-and-sailors', 'Солдаты и матросы', 11, @rank_v2_now),
    (@rank_v2_id, @rank_enlisted_id, 'sergeants-and-starshinas', 'Сержанты и старшины', 12, @rank_v2_now),
    (@rank_v2_id, @rank_officers_id, 'junior-officers', 'Младшие офицеры', 31, @rank_v2_now),
    (@rank_v2_id, @rank_officers_id, 'senior-officers', 'Старшие офицеры', 32, @rank_v2_now),
    (@rank_v2_id, @rank_officers_id, 'higher-officers', 'Высшие офицеры', 33, @rank_v2_now);

SET @rank_soldiers_id = (
    SELECT id FROM military_personnel_compositions
    WHERE catalog_version_id = @rank_v2_id AND code = 'soldiers-and-sailors'
);
SET @rank_sergeants_id = (
    SELECT id FROM military_personnel_compositions
    WHERE catalog_version_id = @rank_v2_id AND code = 'sergeants-and-starshinas'
);
SET @rank_junior_officers_id = (
    SELECT id FROM military_personnel_compositions
    WHERE catalog_version_id = @rank_v2_id AND code = 'junior-officers'
);
SET @rank_senior_officers_id = (
    SELECT id FROM military_personnel_compositions
    WHERE catalog_version_id = @rank_v2_id AND code = 'senior-officers'
);
SET @rank_higher_officers_id = (
    SELECT id FROM military_personnel_compositions
    WHERE catalog_version_id = @rank_v2_id AND code = 'higher-officers'
);

INSERT INTO military_personnel_composition_semantics
    (composition_id, catalog_version_id, classification_kind, is_staffing_selectable, derivation_note, created_at)
VALUES
    (@rank_enlisted_id, @rank_v2_id, 'normative-composition', FALSE, NULL, @rank_v2_now),
    (@rank_soldiers_id, @rank_v2_id, 'derived-staffing-scope', TRUE,
     'Прикладная категория для штатных должностей. Объединяет уровни рядового/матроса и ефрейтора/старшего матроса внутри общего нормативного состава.', @rank_v2_now),
    (@rank_sergeants_id, @rank_v2_id, 'derived-staffing-scope', TRUE,
     'Прикладная категория для штатных должностей. Объединяет сержантские и старшинские уровни внутри общего нормативного состава.', @rank_v2_now),
    (@rank_warrant_id, @rank_v2_id, 'normative-composition', TRUE, NULL, @rank_v2_now),
    (@rank_officers_id, @rank_v2_id, 'normative-composition', TRUE, NULL, @rank_v2_now),
    (@rank_junior_officers_id, @rank_v2_id, 'normative-composition', FALSE, NULL, @rank_v2_now),
    (@rank_senior_officers_id, @rank_v2_id, 'normative-composition', FALSE, NULL, @rank_v2_now),
    (@rank_higher_officers_id, @rank_v2_id, 'normative-composition', FALSE, NULL, @rank_v2_now);

INSERT INTO military_rank_levels
    (catalog_version_id, composition_id, code, troop_name, naval_name, sort_order, created_at)
VALUES
    (@rank_v2_id, @rank_soldiers_id, 'private', 'рядовой', 'матрос', 1, @rank_v2_now),
    (@rank_v2_id, @rank_soldiers_id, 'corporal', 'ефрейтор', 'старший матрос', 2, @rank_v2_now),
    (@rank_v2_id, @rank_sergeants_id, 'junior-sergeant', 'младший сержант', 'старшина 2 статьи', 3, @rank_v2_now),
    (@rank_v2_id, @rank_sergeants_id, 'sergeant', 'сержант', 'старшина 1 статьи', 4, @rank_v2_now),
    (@rank_v2_id, @rank_sergeants_id, 'senior-sergeant', 'старший сержант', 'главный старшина', 5, @rank_v2_now),
    (@rank_v2_id, @rank_sergeants_id, 'starshina', 'старшина', 'главный корабельный старшина', 6, @rank_v2_now),
    (@rank_v2_id, @rank_warrant_id, 'warrant-officer', 'прапорщик', 'мичман', 7, @rank_v2_now),
    (@rank_v2_id, @rank_warrant_id, 'senior-warrant-officer', 'старший прапорщик', 'старший мичман', 8, @rank_v2_now),
    (@rank_v2_id, @rank_junior_officers_id, 'junior-lieutenant', 'младший лейтенант', 'младший лейтенант', 9, @rank_v2_now),
    (@rank_v2_id, @rank_junior_officers_id, 'lieutenant', 'лейтенант', 'лейтенант', 10, @rank_v2_now),
    (@rank_v2_id, @rank_junior_officers_id, 'senior-lieutenant', 'старший лейтенант', 'старший лейтенант', 11, @rank_v2_now),
    (@rank_v2_id, @rank_junior_officers_id, 'captain', 'капитан', 'капитан-лейтенант', 12, @rank_v2_now),
    (@rank_v2_id, @rank_senior_officers_id, 'major', 'майор', 'капитан 3 ранга', 13, @rank_v2_now),
    (@rank_v2_id, @rank_senior_officers_id, 'lieutenant-colonel', 'подполковник', 'капитан 2 ранга', 14, @rank_v2_now),
    (@rank_v2_id, @rank_senior_officers_id, 'colonel', 'полковник', 'капитан 1 ранга', 15, @rank_v2_now),
    (@rank_v2_id, @rank_higher_officers_id, 'major-general', 'генерал-майор', 'контр-адмирал', 16, @rank_v2_now),
    (@rank_v2_id, @rank_higher_officers_id, 'lieutenant-general', 'генерал-лейтенант', 'вице-адмирал', 17, @rank_v2_now),
    (@rank_v2_id, @rank_higher_officers_id, 'colonel-general', 'генерал-полковник', 'адмирал', 18, @rank_v2_now),
    (@rank_v2_id, @rank_higher_officers_id, 'army-general', 'генерал армии', 'адмирал флота', 19, @rank_v2_now),
    (@rank_v2_id, @rank_higher_officers_id, 'marshal-russian-federation', 'Маршал Российской Федерации', NULL, 20, @rank_v2_now);

INSERT INTO military_personnel_composition_sources
    (composition_id, catalog_version_id, legal_source_id, source_role, sort_order, note, created_at)
VALUES
    (@rank_enlisted_id, @rank_v2_id, @rank_law_source_id, 'normative-definition', 1, NULL, @rank_v2_now),
    (@rank_soldiers_id, @rank_v2_id, @rank_law_source_id, 'derived-classification-basis', 1,
     'Источник перечисляет уровни внутри общего нормативного состава; разделение является прикладной классификацией АСУ-ВЧ.', @rank_v2_now),
    (@rank_sergeants_id, @rank_v2_id, @rank_law_source_id, 'derived-classification-basis', 1,
     'Источник перечисляет уровни внутри общего нормативного состава; разделение является прикладной классификацией АСУ-ВЧ.', @rank_v2_now),
    (@rank_warrant_id, @rank_v2_id, @rank_law_source_id, 'normative-definition', 1, NULL, @rank_v2_now),
    (@rank_officers_id, @rank_v2_id, @rank_law_source_id, 'normative-definition', 1, NULL, @rank_v2_now),
    (@rank_junior_officers_id, @rank_v2_id, @rank_decree_source_id, 'rank-list', 1, NULL, @rank_v2_now),
    (@rank_senior_officers_id, @rank_v2_id, @rank_decree_source_id, 'rank-list', 1, NULL, @rank_v2_now),
    (@rank_higher_officers_id, @rank_v2_id, @rank_decree_source_id, 'rank-list', 1, NULL, @rank_v2_now);

UPDATE military_rank_catalog_versions
SET lifecycle_status = 'superseded',
    is_current = FALSE,
    valid_to = '2026-08-02',
    published_at = COALESCE(published_at, created_at),
    superseded_at = @rank_v2_now
WHERE id = @rank_v1_id
  AND lifecycle_status = 'published'
  AND is_current = TRUE;

UPDATE military_rank_catalog_versions
SET lifecycle_status = 'published',
    is_current = TRUE,
    published_at = @rank_v2_now
WHERE id = @rank_v2_id
  AND lifecycle_status = 'building';

COMMIT;
