CREATE TABLE IF NOT EXISTS legal_sources (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(120) NOT NULL,
    document_type VARCHAR(100) NOT NULL,
    document_date DATE NOT NULL,
    document_number VARCHAR(100) NOT NULL,
    title VARCHAR(500) NOT NULL,
    provision VARCHAR(150) NOT NULL,
    official_url VARCHAR(1000) NOT NULL,
    verified_at DATE NOT NULL,
    created_at DATETIME NOT NULL,
    UNIQUE KEY uq_legal_sources_code (code),
    UNIQUE KEY uq_legal_sources_document (document_type, document_date, document_number, provision)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS military_rank_catalog_versions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(120) NOT NULL,
    name VARCHAR(255) NOT NULL,
    is_current BOOLEAN NOT NULL DEFAULT FALSE,
    valid_from DATE NOT NULL,
    valid_to DATE NULL,
    verified_at DATE NOT NULL,
    created_by BIGINT UNSIGNED NULL,
    created_at DATETIME NOT NULL,
    UNIQUE KEY uq_military_rank_catalog_versions_code (code),
    KEY idx_military_rank_catalog_versions_current (is_current, valid_from),
    KEY idx_military_rank_catalog_versions_created_by (created_by),
    CONSTRAINT fk_military_rank_catalog_versions_created_by
        FOREIGN KEY (created_by) REFERENCES users(id)
        ON UPDATE RESTRICT ON DELETE SET NULL,
    CONSTRAINT chk_military_rank_catalog_versions_dates
        CHECK (valid_to IS NULL OR valid_to >= valid_from)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS military_rank_catalog_version_sources (
    catalog_version_id BIGINT UNSIGNED NOT NULL,
    legal_source_id BIGINT UNSIGNED NOT NULL,
    source_role VARCHAR(80) NOT NULL,
    sort_order SMALLINT UNSIGNED NOT NULL,
    PRIMARY KEY (catalog_version_id, legal_source_id),
    UNIQUE KEY uq_military_rank_catalog_version_source_order (catalog_version_id, sort_order),
    CONSTRAINT fk_military_rank_catalog_source_version
        FOREIGN KEY (catalog_version_id) REFERENCES military_rank_catalog_versions(id)
        ON UPDATE RESTRICT ON DELETE RESTRICT,
    CONSTRAINT fk_military_rank_catalog_source_legal
        FOREIGN KEY (legal_source_id) REFERENCES legal_sources(id)
        ON UPDATE RESTRICT ON DELETE RESTRICT,
    CONSTRAINT chk_military_rank_catalog_source_order CHECK (sort_order > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS military_personnel_compositions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    catalog_version_id BIGINT UNSIGNED NOT NULL,
    parent_id BIGINT UNSIGNED NULL,
    code VARCHAR(100) NOT NULL,
    name VARCHAR(255) NOT NULL,
    sort_order SMALLINT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL,
    UNIQUE KEY uq_military_composition_version_code (catalog_version_id, code),
    UNIQUE KEY uq_military_composition_version_order (catalog_version_id, sort_order),
    UNIQUE KEY uq_military_composition_id_version (id, catalog_version_id),
    KEY idx_military_composition_parent_version (parent_id, catalog_version_id),
    CONSTRAINT fk_military_composition_version
        FOREIGN KEY (catalog_version_id) REFERENCES military_rank_catalog_versions(id)
        ON UPDATE RESTRICT ON DELETE RESTRICT,
    CONSTRAINT fk_military_composition_parent_version
        FOREIGN KEY (parent_id, catalog_version_id) REFERENCES military_personnel_compositions(id, catalog_version_id)
        ON UPDATE RESTRICT ON DELETE RESTRICT,
    CONSTRAINT chk_military_composition_order CHECK (sort_order > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS military_rank_levels (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    catalog_version_id BIGINT UNSIGNED NOT NULL,
    composition_id BIGINT UNSIGNED NOT NULL,
    code VARCHAR(100) NOT NULL,
    troop_name VARCHAR(255) NOT NULL,
    naval_name VARCHAR(255) NULL,
    sort_order SMALLINT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL,
    UNIQUE KEY uq_military_rank_level_version_code (catalog_version_id, code),
    UNIQUE KEY uq_military_rank_level_version_order (catalog_version_id, sort_order),
    KEY idx_military_rank_level_composition_version (composition_id, catalog_version_id),
    CONSTRAINT fk_military_rank_level_version
        FOREIGN KEY (catalog_version_id) REFERENCES military_rank_catalog_versions(id)
        ON UPDATE RESTRICT ON DELETE RESTRICT,
    CONSTRAINT fk_military_rank_level_composition_version
        FOREIGN KEY (composition_id, catalog_version_id) REFERENCES military_personnel_compositions(id, catalog_version_id)
        ON UPDATE RESTRICT ON DELETE RESTRICT,
    CONSTRAINT chk_military_rank_level_order CHECK (sort_order > 0),
    CONSTRAINT chk_military_rank_level_troop_name CHECK (CHAR_LENGTH(TRIM(troop_name)) > 0),
    CONSTRAINT chk_military_rank_level_naval_name CHECK (naval_name IS NULL OR CHAR_LENGTH(TRIM(naval_name)) > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET @military_rank_now = NOW();

INSERT INTO legal_sources
    (code, document_type, document_date, document_number, title, provision, official_url, verified_at, created_at)
VALUES
    (
        'federal-law-53-fz-article-46',
        'Федеральный закон',
        '1998-03-28',
        '53-ФЗ',
        'О воинской обязанности и военной службе',
        'статья 46',
        'https://www.kremlin.ru/acts/bank/12128/print',
        '2026-07-27',
        @military_rank_now
    ),
    (
        'presidential-decree-1237-article-20',
        'Указ Президента Российской Федерации',
        '1999-09-16',
        '1237',
        'Вопросы прохождения военной службы',
        'статья 20 Положения о порядке прохождения военной службы',
        'https://www.kremlin.ru/acts/bank/14416/print',
        '2026-07-27',
        @military_rank_now
    )
ON DUPLICATE KEY UPDATE
    document_type = VALUES(document_type),
    document_date = VALUES(document_date),
    document_number = VALUES(document_number),
    title = VALUES(title),
    provision = VALUES(provision),
    official_url = VALUES(official_url),
    verified_at = VALUES(verified_at);

UPDATE military_rank_catalog_versions
SET is_current = FALSE,
    valid_to = COALESCE(valid_to, '2026-07-26')
WHERE is_current = TRUE
  AND code <> 'rf-military-ranks-2026-07-27';

INSERT INTO military_rank_catalog_versions
    (code, name, is_current, valid_from, valid_to, verified_at, created_by, created_at)
VALUES
    (
        'rf-military-ranks-2026-07-27',
        'Составы военнослужащих и воинские звания Российской Федерации',
        TRUE,
        '2026-07-27',
        NULL,
        '2026-07-27',
        NULL,
        @military_rank_now
    )
ON DUPLICATE KEY UPDATE
    name = VALUES(name),
    is_current = TRUE,
    valid_from = VALUES(valid_from),
    valid_to = NULL,
    verified_at = VALUES(verified_at);

SET @military_rank_version_id = (
    SELECT id FROM military_rank_catalog_versions
    WHERE code = 'rf-military-ranks-2026-07-27'
    LIMIT 1
);
SET @military_rank_law_source_id = (
    SELECT id FROM legal_sources
    WHERE code = 'federal-law-53-fz-article-46'
    LIMIT 1
);
SET @military_rank_decree_source_id = (
    SELECT id FROM legal_sources
    WHERE code = 'presidential-decree-1237-article-20'
    LIMIT 1
);

INSERT INTO military_rank_catalog_version_sources
    (catalog_version_id, legal_source_id, source_role, sort_order)
VALUES
    (@military_rank_version_id, @military_rank_law_source_id, 'primary-list', 1),
    (@military_rank_version_id, @military_rank_decree_source_id, 'equivalence-and-order', 2)
ON DUPLICATE KEY UPDATE
    source_role = VALUES(source_role),
    sort_order = VALUES(sort_order);

INSERT INTO military_personnel_compositions
    (catalog_version_id, parent_id, code, name, sort_order, created_at)
VALUES
    (@military_rank_version_id, NULL, 'enlisted', 'Солдаты, матросы, сержанты, старшины', 10, @military_rank_now),
    (@military_rank_version_id, NULL, 'warrant-officers', 'Прапорщики и мичманы', 20, @military_rank_now),
    (@military_rank_version_id, NULL, 'officers', 'Офицеры', 30, @military_rank_now)
ON DUPLICATE KEY UPDATE
    parent_id = VALUES(parent_id),
    name = VALUES(name),
    sort_order = VALUES(sort_order);

SET @military_rank_officers_id = (
    SELECT id FROM military_personnel_compositions
    WHERE catalog_version_id = @military_rank_version_id AND code = 'officers'
    LIMIT 1
);

INSERT INTO military_personnel_compositions
    (catalog_version_id, parent_id, code, name, sort_order, created_at)
VALUES
    (@military_rank_version_id, @military_rank_officers_id, 'junior-officers', 'Младшие офицеры', 31, @military_rank_now),
    (@military_rank_version_id, @military_rank_officers_id, 'senior-officers', 'Старшие офицеры', 32, @military_rank_now),
    (@military_rank_version_id, @military_rank_officers_id, 'higher-officers', 'Высшие офицеры', 33, @military_rank_now)
ON DUPLICATE KEY UPDATE
    parent_id = VALUES(parent_id),
    name = VALUES(name),
    sort_order = VALUES(sort_order);

SET @military_rank_enlisted_id = (
    SELECT id FROM military_personnel_compositions
    WHERE catalog_version_id = @military_rank_version_id AND code = 'enlisted'
    LIMIT 1
);
SET @military_rank_warrant_id = (
    SELECT id FROM military_personnel_compositions
    WHERE catalog_version_id = @military_rank_version_id AND code = 'warrant-officers'
    LIMIT 1
);
SET @military_rank_junior_officers_id = (
    SELECT id FROM military_personnel_compositions
    WHERE catalog_version_id = @military_rank_version_id AND code = 'junior-officers'
    LIMIT 1
);
SET @military_rank_senior_officers_id = (
    SELECT id FROM military_personnel_compositions
    WHERE catalog_version_id = @military_rank_version_id AND code = 'senior-officers'
    LIMIT 1
);
SET @military_rank_higher_officers_id = (
    SELECT id FROM military_personnel_compositions
    WHERE catalog_version_id = @military_rank_version_id AND code = 'higher-officers'
    LIMIT 1
);

INSERT INTO military_rank_levels
    (catalog_version_id, composition_id, code, troop_name, naval_name, sort_order, created_at)
VALUES
    (@military_rank_version_id, @military_rank_enlisted_id, 'private', 'рядовой', 'матрос', 1, @military_rank_now),
    (@military_rank_version_id, @military_rank_enlisted_id, 'corporal', 'ефрейтор', 'старший матрос', 2, @military_rank_now),
    (@military_rank_version_id, @military_rank_enlisted_id, 'junior-sergeant', 'младший сержант', 'старшина 2 статьи', 3, @military_rank_now),
    (@military_rank_version_id, @military_rank_enlisted_id, 'sergeant', 'сержант', 'старшина 1 статьи', 4, @military_rank_now),
    (@military_rank_version_id, @military_rank_enlisted_id, 'senior-sergeant', 'старший сержант', 'главный старшина', 5, @military_rank_now),
    (@military_rank_version_id, @military_rank_enlisted_id, 'starshina', 'старшина', 'главный корабельный старшина', 6, @military_rank_now),
    (@military_rank_version_id, @military_rank_warrant_id, 'warrant-officer', 'прапорщик', 'мичман', 7, @military_rank_now),
    (@military_rank_version_id, @military_rank_warrant_id, 'senior-warrant-officer', 'старший прапорщик', 'старший мичман', 8, @military_rank_now),
    (@military_rank_version_id, @military_rank_junior_officers_id, 'junior-lieutenant', 'младший лейтенант', 'младший лейтенант', 9, @military_rank_now),
    (@military_rank_version_id, @military_rank_junior_officers_id, 'lieutenant', 'лейтенант', 'лейтенант', 10, @military_rank_now),
    (@military_rank_version_id, @military_rank_junior_officers_id, 'senior-lieutenant', 'старший лейтенант', 'старший лейтенант', 11, @military_rank_now),
    (@military_rank_version_id, @military_rank_junior_officers_id, 'captain', 'капитан', 'капитан-лейтенант', 12, @military_rank_now),
    (@military_rank_version_id, @military_rank_senior_officers_id, 'major', 'майор', 'капитан 3 ранга', 13, @military_rank_now),
    (@military_rank_version_id, @military_rank_senior_officers_id, 'lieutenant-colonel', 'подполковник', 'капитан 2 ранга', 14, @military_rank_now),
    (@military_rank_version_id, @military_rank_senior_officers_id, 'colonel', 'полковник', 'капитан 1 ранга', 15, @military_rank_now),
    (@military_rank_version_id, @military_rank_higher_officers_id, 'major-general', 'генерал-майор', 'контр-адмирал', 16, @military_rank_now),
    (@military_rank_version_id, @military_rank_higher_officers_id, 'lieutenant-general', 'генерал-лейтенант', 'вице-адмирал', 17, @military_rank_now),
    (@military_rank_version_id, @military_rank_higher_officers_id, 'colonel-general', 'генерал-полковник', 'адмирал', 18, @military_rank_now),
    (@military_rank_version_id, @military_rank_higher_officers_id, 'army-general', 'генерал армии', 'адмирал флота', 19, @military_rank_now),
    (@military_rank_version_id, @military_rank_higher_officers_id, 'marshal-russian-federation', 'Маршал Российской Федерации', NULL, 20, @military_rank_now)
ON DUPLICATE KEY UPDATE
    composition_id = VALUES(composition_id),
    troop_name = VALUES(troop_name),
    naval_name = VALUES(naval_name),
    sort_order = VALUES(sort_order);
