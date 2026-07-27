CREATE TABLE IF NOT EXISTS organizational_element_catalog_versions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(120) NOT NULL,
    name VARCHAR(255) NOT NULL,
    is_current BOOLEAN NOT NULL DEFAULT FALSE,
    current_guard TINYINT GENERATED ALWAYS AS (
        CASE WHEN is_current = 1 THEN 1 ELSE NULL END
    ) STORED,
    valid_from DATE NOT NULL,
    valid_to DATE NULL,
    verified_at DATE NOT NULL,
    created_by BIGINT UNSIGNED NULL,
    created_at DATETIME NOT NULL,
    UNIQUE KEY uq_org_element_catalog_versions_code (code),
    UNIQUE KEY uq_org_element_catalog_current_guard (current_guard),
    KEY idx_org_element_catalog_versions_current (is_current, valid_from),
    KEY idx_org_element_catalog_versions_created_by (created_by),
    CONSTRAINT fk_org_element_catalog_versions_created_by
        FOREIGN KEY (created_by) REFERENCES users(id)
        ON UPDATE RESTRICT ON DELETE SET NULL,
    CONSTRAINT chk_org_element_catalog_versions_current CHECK (is_current IN (0, 1)),
    CONSTRAINT chk_org_element_catalog_versions_dates
        CHECK (valid_to IS NULL OR valid_to >= valid_from),
    CONSTRAINT chk_org_element_catalog_versions_name
        CHECK (CHAR_LENGTH(TRIM(name)) > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS organizational_element_catalog_version_sources (
    catalog_version_id BIGINT UNSIGNED NOT NULL,
    legal_source_id BIGINT UNSIGNED NOT NULL,
    source_role VARCHAR(80) NOT NULL,
    sort_order SMALLINT UNSIGNED NOT NULL,
    PRIMARY KEY (catalog_version_id, legal_source_id),
    UNIQUE KEY uq_org_element_catalog_source_order (catalog_version_id, sort_order),
    CONSTRAINT fk_org_element_catalog_source_version
        FOREIGN KEY (catalog_version_id) REFERENCES organizational_element_catalog_versions(id)
        ON UPDATE RESTRICT ON DELETE RESTRICT,
    CONSTRAINT fk_org_element_catalog_source_legal
        FOREIGN KEY (legal_source_id) REFERENCES legal_sources(id)
        ON UPDATE RESTRICT ON DELETE RESTRICT,
    CONSTRAINT chk_org_element_catalog_source_role CHECK (
        source_role IN ('general-composition', 'classification', 'internal-service', 'naval-organization')
    ),
    CONSTRAINT chk_org_element_catalog_source_order CHECK (sort_order > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS organizational_element_classes (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    catalog_version_id BIGINT UNSIGNED NOT NULL,
    code VARCHAR(100) NOT NULL,
    name VARCHAR(255) NOT NULL,
    description VARCHAR(1000) NOT NULL,
    sort_order SMALLINT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL,
    UNIQUE KEY uq_org_element_class_version_code (catalog_version_id, code),
    UNIQUE KEY uq_org_element_class_version_name (catalog_version_id, name),
    UNIQUE KEY uq_org_element_class_version_order (catalog_version_id, sort_order),
    UNIQUE KEY uq_org_element_class_id_version (id, catalog_version_id),
    CONSTRAINT fk_org_element_class_version
        FOREIGN KEY (catalog_version_id) REFERENCES organizational_element_catalog_versions(id)
        ON UPDATE RESTRICT ON DELETE RESTRICT,
    CONSTRAINT chk_org_element_class_order CHECK (sort_order > 0),
    CONSTRAINT chk_org_element_class_name CHECK (CHAR_LENGTH(TRIM(name)) > 0),
    CONSTRAINT chk_org_element_class_description CHECK (CHAR_LENGTH(TRIM(description)) > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS organizational_element_types (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    catalog_version_id BIGINT UNSIGNED NOT NULL,
    code VARCHAR(100) NOT NULL,
    name VARCHAR(255) NOT NULL,
    short_name VARCHAR(100) NULL,
    description VARCHAR(1000) NOT NULL,
    applicability_note VARCHAR(1000) NOT NULL,
    sort_order SMALLINT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL,
    UNIQUE KEY uq_org_element_type_version_code (catalog_version_id, code),
    UNIQUE KEY uq_org_element_type_version_name (catalog_version_id, name),
    UNIQUE KEY uq_org_element_type_version_order (catalog_version_id, sort_order),
    UNIQUE KEY uq_org_element_type_id_version (id, catalog_version_id),
    CONSTRAINT fk_org_element_type_version
        FOREIGN KEY (catalog_version_id) REFERENCES organizational_element_catalog_versions(id)
        ON UPDATE RESTRICT ON DELETE RESTRICT,
    CONSTRAINT chk_org_element_type_order CHECK (sort_order > 0),
    CONSTRAINT chk_org_element_type_name CHECK (CHAR_LENGTH(TRIM(name)) > 0),
    CONSTRAINT chk_org_element_type_short_name
        CHECK (short_name IS NULL OR CHAR_LENGTH(TRIM(short_name)) > 0),
    CONSTRAINT chk_org_element_type_description CHECK (CHAR_LENGTH(TRIM(description)) > 0),
    CONSTRAINT chk_org_element_type_applicability CHECK (CHAR_LENGTH(TRIM(applicability_note)) > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS organizational_element_type_classes (
    catalog_version_id BIGINT UNSIGNED NOT NULL,
    type_id BIGINT UNSIGNED NOT NULL,
    class_id BIGINT UNSIGNED NOT NULL,
    is_primary BOOLEAN NOT NULL DEFAULT FALSE,
    primary_guard BIGINT UNSIGNED GENERATED ALWAYS AS (
        CASE WHEN is_primary = 1 THEN type_id ELSE NULL END
    ) STORED,
    context_note VARCHAR(1000) NULL,
    sort_order SMALLINT UNSIGNED NOT NULL,
    PRIMARY KEY (type_id, class_id),
    UNIQUE KEY uq_org_element_type_class_order (type_id, sort_order),
    UNIQUE KEY uq_org_element_type_primary_guard (primary_guard),
    KEY idx_org_element_type_class_version (catalog_version_id, type_id, class_id),
    KEY idx_org_element_class_type_version (class_id, catalog_version_id),
    CONSTRAINT fk_org_element_type_class_type_version
        FOREIGN KEY (type_id, catalog_version_id)
        REFERENCES organizational_element_types(id, catalog_version_id)
        ON UPDATE RESTRICT ON DELETE RESTRICT,
    CONSTRAINT fk_org_element_type_class_class_version
        FOREIGN KEY (class_id, catalog_version_id)
        REFERENCES organizational_element_classes(id, catalog_version_id)
        ON UPDATE RESTRICT ON DELETE RESTRICT,
    CONSTRAINT chk_org_element_type_class_primary CHECK (is_primary IN (0, 1)),
    CONSTRAINT chk_org_element_type_class_order CHECK (sort_order > 0),
    CONSTRAINT chk_org_element_type_class_context CHECK (
        is_primary = 1 OR (context_note IS NOT NULL AND CHAR_LENGTH(TRIM(context_note)) > 0)
    )
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS organizational_element_type_aliases (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    catalog_version_id BIGINT UNSIGNED NOT NULL,
    type_id BIGINT UNSIGNED NOT NULL,
    alias_type VARCHAR(40) NOT NULL,
    alias VARCHAR(255) NOT NULL,
    legal_source_id BIGINT UNSIGNED NOT NULL,
    sort_order SMALLINT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL,
    UNIQUE KEY uq_org_element_alias_version_type_kind_value
        (catalog_version_id, type_id, alias_type, alias),
    KEY idx_org_element_alias_type_version (type_id, catalog_version_id),
    KEY idx_org_element_alias_source_version (catalog_version_id, legal_source_id),
    CONSTRAINT fk_org_element_alias_type_version
        FOREIGN KEY (type_id, catalog_version_id)
        REFERENCES organizational_element_types(id, catalog_version_id)
        ON UPDATE RESTRICT ON DELETE RESTRICT,
    CONSTRAINT fk_org_element_alias_source_version
        FOREIGN KEY (catalog_version_id, legal_source_id)
        REFERENCES organizational_element_catalog_version_sources(catalog_version_id, legal_source_id)
        ON UPDATE RESTRICT ON DELETE RESTRICT,
    CONSTRAINT chk_org_element_alias_type CHECK (
        alias_type IN ('official-short-name', 'official-variant', 'historical-name', 'search-synonym')
    ),
    CONSTRAINT chk_org_element_alias_value CHECK (CHAR_LENGTH(TRIM(alias)) > 0),
    CONSTRAINT chk_org_element_alias_order CHECK (sort_order > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS organizational_element_type_sources (
    catalog_version_id BIGINT UNSIGNED NOT NULL,
    type_id BIGINT UNSIGNED NOT NULL,
    legal_source_id BIGINT UNSIGNED NOT NULL,
    source_role VARCHAR(40) NOT NULL,
    provision_detail VARCHAR(500) NOT NULL,
    sort_order SMALLINT UNSIGNED NOT NULL,
    PRIMARY KEY (type_id, legal_source_id, source_role),
    UNIQUE KEY uq_org_element_type_source_order (type_id, sort_order),
    KEY idx_org_element_type_source_version (type_id, catalog_version_id),
    KEY idx_org_element_source_version (catalog_version_id, legal_source_id),
    CONSTRAINT fk_org_element_type_source_type_version
        FOREIGN KEY (type_id, catalog_version_id)
        REFERENCES organizational_element_types(id, catalog_version_id)
        ON UPDATE RESTRICT ON DELETE RESTRICT,
    CONSTRAINT fk_org_element_type_source_legal_version
        FOREIGN KEY (catalog_version_id, legal_source_id)
        REFERENCES organizational_element_catalog_version_sources(catalog_version_id, legal_source_id)
        ON UPDATE RESTRICT ON DELETE RESTRICT,
    CONSTRAINT chk_org_element_type_source_role CHECK (
        source_role IN ('definition', 'classification', 'official-usage', 'authority-rule', 'historical-context')
    ),
    CONSTRAINT chk_org_element_type_source_detail CHECK (CHAR_LENGTH(TRIM(provision_detail)) > 0),
    CONSTRAINT chk_org_element_type_source_order CHECK (sort_order > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET @org_element_now = NOW();

INSERT INTO legal_sources
    (code, document_type, document_date, document_number, title, provision, official_url, verified_at, created_at)
VALUES
    ('federal-law-61-fz-article-11', 'Федеральный закон', '1996-05-31', '61-ФЗ', 'Об обороне', 'статья 11', 'https://www.kremlin.ru/acts/bank/9446/print', '2026-07-27', @org_element_now),
    ('presidential-decree-1237-article-11', 'Указ Президента Российской Федерации', '1999-09-16', '1237', 'Вопросы прохождения военной службы', 'статья 11 Положения о порядке прохождения военной службы', 'https://www.kremlin.ru/acts/bank/14416/print', '2026-07-27', @org_element_now),
    ('presidential-decree-1495-internal-service-charter', 'Указ Президента Российской Федерации', '2007-11-10', '1495', 'Об утверждении общевоинских уставов Вооружённых Сил Российской Федерации', 'Устав внутренней службы, преамбула и статьи 93–159, 259, 272, 283, 296, 298', 'https://www.kremlin.ru/acts/bank/26528/print', '2026-07-27', @org_element_now),
    ('presidential-decree-511-ship-charter', 'Указ Президента Российской Федерации', '2022-07-31', '511', 'Об утверждении Корабельного устава Военно-Морского Флота', 'Корабельный устав, статьи 12–18, 27–35', 'https://www.kremlin.ru/acts/bank/48223/print', '2026-07-27', @org_element_now)
ON DUPLICATE KEY UPDATE
    document_type = VALUES(document_type),
    document_date = VALUES(document_date),
    document_number = VALUES(document_number),
    title = VALUES(title),
    provision = VALUES(provision),
    official_url = VALUES(official_url),
    verified_at = VALUES(verified_at);

INSERT INTO organizational_element_catalog_versions
    (code, name, is_current, valid_from, valid_to, verified_at, created_by, created_at)
VALUES
    ('rf-organizational-elements-2026-07-27', 'Типы организационных элементов Вооружённых Сил Российской Федерации', 1, '2026-07-27', NULL, '2026-07-27', NULL, @org_element_now)
ON DUPLICATE KEY UPDATE
    name = VALUES(name),
    is_current = VALUES(is_current),
    valid_from = VALUES(valid_from),
    valid_to = VALUES(valid_to),
    verified_at = VALUES(verified_at);

SET @org_element_version_id = (
    SELECT id FROM organizational_element_catalog_versions
    WHERE code = 'rf-organizational-elements-2026-07-27' LIMIT 1
);

INSERT INTO organizational_element_catalog_version_sources
    (catalog_version_id, legal_source_id, source_role, sort_order)
SELECT @org_element_version_id, id, 'general-composition', 1
FROM legal_sources WHERE code = 'federal-law-61-fz-article-11'
ON DUPLICATE KEY UPDATE source_role = VALUES(source_role), sort_order = VALUES(sort_order);

INSERT INTO organizational_element_catalog_version_sources
    (catalog_version_id, legal_source_id, source_role, sort_order)
SELECT @org_element_version_id, id, 'classification', 2
FROM legal_sources WHERE code = 'presidential-decree-1237-article-11'
ON DUPLICATE KEY UPDATE source_role = VALUES(source_role), sort_order = VALUES(sort_order);

INSERT INTO organizational_element_catalog_version_sources
    (catalog_version_id, legal_source_id, source_role, sort_order)
SELECT @org_element_version_id, id, 'internal-service', 3
FROM legal_sources WHERE code = 'presidential-decree-1495-internal-service-charter'
ON DUPLICATE KEY UPDATE source_role = VALUES(source_role), sort_order = VALUES(sort_order);

INSERT INTO organizational_element_catalog_version_sources
    (catalog_version_id, legal_source_id, source_role, sort_order)
SELECT @org_element_version_id, id, 'naval-organization', 4
FROM legal_sources WHERE code = 'presidential-decree-511-ship-charter'
ON DUPLICATE KEY UPDATE source_role = VALUES(source_role), sort_order = VALUES(sort_order);

INSERT INTO organizational_element_classes
    (catalog_version_id, code, name, description, sort_order, created_at)
VALUES
    (@org_element_version_id, 'military-command-body', 'Орган военного управления', 'Организационный элемент, выполняющий функции военного управления в установленном нормативном контексте.', 1, @org_element_now),
    (@org_element_version_id, 'association', 'Объединение', 'Организационный класс оперативного или стратегического масштаба, прямо используемый нормативными актами.', 2, @org_element_now),
    (@org_element_version_id, 'formation', 'Соединение', 'Организационный класс, объединяющий несколько воинских частей или меньших соединений.', 3, @org_element_now),
    (@org_element_version_id, 'military-unit', 'Воинская часть', 'Самостоятельный организационный класс, статус которого определяется утверждённым штатом и правовым основанием.', 4, @org_element_now),
    (@org_element_version_id, 'organization', 'Организация', 'Организация Вооружённых Сил, не классифицируемая в данном контексте как воинская часть.', 5, @org_element_now),
    (@org_element_version_id, 'subdivision', 'Подразделение', 'Организационный элемент, входящий в состав другого элемента согласно утверждённой структуре.', 6, @org_element_now)
ON DUPLICATE KEY UPDATE
    name = VALUES(name),
    description = VALUES(description),
    sort_order = VALUES(sort_order);

INSERT INTO organizational_element_types
    (catalog_version_id, code, name, short_name, description, applicability_note, sort_order, created_at)
VALUES
    (@org_element_version_id, 'administration', 'управление', NULL, 'Организационный элемент управления, официально используемый для органов и внутренних элементов структуры.', 'Класс определяется конкретным нормативным положением и утверждённой структурой.', 1, @org_element_now),
    (@org_element_version_id, 'headquarters', 'штаб', NULL, 'Орган управления, обеспечивающий планирование, координацию и управление в установленном контексте.', 'Может выступать органом военного управления либо подразделением другого элемента.', 2, @org_element_now),
    (@org_element_version_id, 'service', 'служба', NULL, 'Функциональное подразделение по установленному направлению деятельности.', 'Состав и место службы определяются утверждённым штатом.', 3, @org_element_now),
    (@org_element_version_id, 'direction', 'направление', NULL, 'Функциональный организационный элемент, официально употребляемый в структурах органов управления.', 'Не задаёт универсального уровня и применяется только в подтверждённом контексте.', 4, @org_element_now),
    (@org_element_version_id, 'department', 'отдел', NULL, 'Функциональное подразделение органа управления или организации.', 'Место отдела в структуре определяется конкретным положением или штатом.', 5, @org_element_now),
    (@org_element_version_id, 'army', 'армия', NULL, 'Организационный тип, нормативно классифицируемый как объединение.', 'Не определяет состав конкретной армии.', 6, @org_element_now),
    (@org_element_version_id, 'corps', 'корпус', NULL, 'Организационный тип, нормативно классифицируемый как соединение.', 'Не определяет состав конкретного корпуса.', 7, @org_element_now),
    (@org_element_version_id, 'division', 'дивизия', NULL, 'Организационный тип, нормативно классифицируемый как соединение.', 'Не определяет состав конкретной дивизии.', 8, @org_element_now),
    (@org_element_version_id, 'brigade', 'бригада', NULL, 'Организационный тип, нормативно классифицируемый как соединение.', 'Не определяет состав конкретной бригады.', 9, @org_element_now),
    (@org_element_version_id, 'regiment', 'полк', NULL, 'Основная тактическая и административно-хозяйственная единица, содержащаяся по установленному штату.', 'В справочнике фиксируется общий тип без состава конкретного полка.', 10, @org_element_now),
    (@org_element_version_id, 'arsenal', 'арсенал', NULL, 'Организационный тип, официально относимый к воинским частям в нормативном контексте.', 'Фактическое назначение и состав конкретного арсенала не хранятся.', 11, @org_element_now),
    (@org_element_version_id, 'test-center', 'испытательный центр', NULL, 'Организационный тип, официально относимый к воинским частям в нормативном контексте.', 'Не описывает специализацию и состав конкретного центра.', 12, @org_element_now),
    (@org_element_version_id, 'storage-supply-base', 'база хранения и снабжения', NULL, 'Организационный тип, официально относимый к воинским частям в нормативном контексте.', 'Не содержит сведений о запасах, дислокации или фактической структуре.', 13, @org_element_now),
    (@org_element_version_id, 'enterprise', 'предприятие', NULL, 'Организация, официально упоминаемая в составе системы Вооружённых Сил.', 'Организационно-правовая форма конкретного предприятия определяется отдельно.', 14, @org_element_now),
    (@org_element_version_id, 'institution', 'учреждение', NULL, 'Организация, официально упоминаемая в составе системы Вооружённых Сил.', 'Не определяет ведомственный статус конкретного учреждения.', 15, @org_element_now),
    (@org_element_version_id, 'military-educational-organization', 'военная образовательная организация', NULL, 'Организация, осуществляющая образовательную деятельность в установленной сфере.', 'Конкретный вид и статус определяются учредительными и нормативными документами.', 16, @org_element_now),
    (@org_element_version_id, 'battalion', 'батальон', NULL, 'Тактический организационный элемент, используемый как подразделение и в отдельных случаях как воинская часть.', 'Самостоятельный статус возможен только при наличии соответствующего утверждённого основания.', 17, @org_element_now),
    (@org_element_version_id, 'divizion', 'дивизион', NULL, 'Организационный элемент, используемый в сухопутной и корабельной организации.', 'Самостоятельный статус и область применения определяются конкретным нормативным контекстом.', 18, @org_element_now),
    (@org_element_version_id, 'company', 'рота', NULL, 'Тактическое подразделение, официально используемое в общевоинской организации.', 'Состав и место в структуре определяются утверждённым штатом.', 19, @org_element_now),
    (@org_element_version_id, 'battery', 'батарея', NULL, 'Тактическое подразделение, официально используемое в общевоинской и корабельной организации.', 'Состав и принадлежность определяются утверждённой структурой.', 20, @org_element_now),
    (@org_element_version_id, 'platoon', 'взвод', NULL, 'Тактическое подразделение, официально используемое в общевоинской организации.', 'Состав и место в структуре определяются утверждённым штатом.', 21, @org_element_now),
    (@org_element_version_id, 'group', 'группа', NULL, 'Организационное подразделение, официально употребляемое в уставах.', 'Название само по себе не определяет уровень, численность или назначение.', 22, @org_element_now),
    (@org_element_version_id, 'section', 'отделение', NULL, 'Низовое подразделение, официально используемое в общевоинской и корабельной организации.', 'Состав определяется конкретным штатом или корабельной организацией.', 23, @org_element_now),
    (@org_element_version_id, 'team', 'команда', NULL, 'Организационное подразделение, официально используемое в уставах.', 'Не следует смешивать с временной командой вне подтверждённого организационного контекста.', 24, @org_element_now),
    (@org_element_version_id, 'raschet', 'расчёт', NULL, 'Организационное подразделение личного состава для совместного выполнения установленных обязанностей.', 'Конкретное назначение расчёта в справочнике не фиксируется.', 25, @org_element_now),
    (@org_element_version_id, 'crew', 'экипаж', NULL, 'Организационное подразделение личного состава, обслуживающее соответствующий объект или средство.', 'Тип не содержит сведений о технике и фактическом составе экипажа.', 26, @org_element_now),
    (@org_element_version_id, 'ship', 'корабль', NULL, 'Боевая тактическая и административно-хозяйственная единица, содержащаяся по установленному штату.', 'Классификация как воинской части применяется только в соответствующем правовом контексте.', 27, @org_element_now),
    (@org_element_version_id, 'combat-unit', 'боевая часть', 'БЧ', 'Основной организационный элемент корабельной организации по установленному назначению.', 'Применяется к корабельной организации и не является универсальным общевойсковым подразделением.', 28, @org_element_now)
ON DUPLICATE KEY UPDATE
    name = VALUES(name),
    short_name = VALUES(short_name),
    description = VALUES(description),
    applicability_note = VALUES(applicability_note),
    sort_order = VALUES(sort_order);

INSERT INTO organizational_element_type_classes
    (catalog_version_id, type_id, class_id, is_primary, context_note, sort_order)
VALUES
    (@org_element_version_id, (SELECT id FROM organizational_element_types WHERE catalog_version_id = @org_element_version_id AND code = 'administration'), (SELECT id FROM organizational_element_classes WHERE catalog_version_id = @org_element_version_id AND code = 'military-command-body'), 1, NULL, 1),
    (@org_element_version_id, (SELECT id FROM organizational_element_types WHERE catalog_version_id = @org_element_version_id AND code = 'administration'), (SELECT id FROM organizational_element_classes WHERE catalog_version_id = @org_element_version_id AND code = 'subdivision'), 0, 'Управление может быть внутренним подразделением другого организационного элемента.', 2),
    (@org_element_version_id, (SELECT id FROM organizational_element_types WHERE catalog_version_id = @org_element_version_id AND code = 'headquarters'), (SELECT id FROM organizational_element_classes WHERE catalog_version_id = @org_element_version_id AND code = 'military-command-body'), 1, NULL, 1),
    (@org_element_version_id, (SELECT id FROM organizational_element_types WHERE catalog_version_id = @org_element_version_id AND code = 'headquarters'), (SELECT id FROM organizational_element_classes WHERE catalog_version_id = @org_element_version_id AND code = 'subdivision'), 0, 'Штаб может входить в состав другого организационного элемента как подразделение.', 2),
    (@org_element_version_id, (SELECT id FROM organizational_element_types WHERE catalog_version_id = @org_element_version_id AND code = 'service'), (SELECT id FROM organizational_element_classes WHERE catalog_version_id = @org_element_version_id AND code = 'subdivision'), 1, NULL, 1),
    (@org_element_version_id, (SELECT id FROM organizational_element_types WHERE catalog_version_id = @org_element_version_id AND code = 'direction'), (SELECT id FROM organizational_element_classes WHERE catalog_version_id = @org_element_version_id AND code = 'subdivision'), 1, NULL, 1),
    (@org_element_version_id, (SELECT id FROM organizational_element_types WHERE catalog_version_id = @org_element_version_id AND code = 'department'), (SELECT id FROM organizational_element_classes WHERE catalog_version_id = @org_element_version_id AND code = 'subdivision'), 1, NULL, 1),
    (@org_element_version_id, (SELECT id FROM organizational_element_types WHERE catalog_version_id = @org_element_version_id AND code = 'army'), (SELECT id FROM organizational_element_classes WHERE catalog_version_id = @org_element_version_id AND code = 'association'), 1, NULL, 1),
    (@org_element_version_id, (SELECT id FROM organizational_element_types WHERE catalog_version_id = @org_element_version_id AND code = 'corps'), (SELECT id FROM organizational_element_classes WHERE catalog_version_id = @org_element_version_id AND code = 'formation'), 1, NULL, 1),
    (@org_element_version_id, (SELECT id FROM organizational_element_types WHERE catalog_version_id = @org_element_version_id AND code = 'division'), (SELECT id FROM organizational_element_classes WHERE catalog_version_id = @org_element_version_id AND code = 'formation'), 1, NULL, 1),
    (@org_element_version_id, (SELECT id FROM organizational_element_types WHERE catalog_version_id = @org_element_version_id AND code = 'brigade'), (SELECT id FROM organizational_element_classes WHERE catalog_version_id = @org_element_version_id AND code = 'formation'), 1, NULL, 1),
    (@org_element_version_id, (SELECT id FROM organizational_element_types WHERE catalog_version_id = @org_element_version_id AND code = 'regiment'), (SELECT id FROM organizational_element_classes WHERE catalog_version_id = @org_element_version_id AND code = 'military-unit'), 1, NULL, 1),
    (@org_element_version_id, (SELECT id FROM organizational_element_types WHERE catalog_version_id = @org_element_version_id AND code = 'arsenal'), (SELECT id FROM organizational_element_classes WHERE catalog_version_id = @org_element_version_id AND code = 'military-unit'), 1, NULL, 1),
    (@org_element_version_id, (SELECT id FROM organizational_element_types WHERE catalog_version_id = @org_element_version_id AND code = 'test-center'), (SELECT id FROM organizational_element_classes WHERE catalog_version_id = @org_element_version_id AND code = 'military-unit'), 1, NULL, 1),
    (@org_element_version_id, (SELECT id FROM organizational_element_types WHERE catalog_version_id = @org_element_version_id AND code = 'storage-supply-base'), (SELECT id FROM organizational_element_classes WHERE catalog_version_id = @org_element_version_id AND code = 'military-unit'), 1, NULL, 1),
    (@org_element_version_id, (SELECT id FROM organizational_element_types WHERE catalog_version_id = @org_element_version_id AND code = 'enterprise'), (SELECT id FROM organizational_element_classes WHERE catalog_version_id = @org_element_version_id AND code = 'organization'), 1, NULL, 1),
    (@org_element_version_id, (SELECT id FROM organizational_element_types WHERE catalog_version_id = @org_element_version_id AND code = 'institution'), (SELECT id FROM organizational_element_classes WHERE catalog_version_id = @org_element_version_id AND code = 'organization'), 1, NULL, 1),
    (@org_element_version_id, (SELECT id FROM organizational_element_types WHERE catalog_version_id = @org_element_version_id AND code = 'military-educational-organization'), (SELECT id FROM organizational_element_classes WHERE catalog_version_id = @org_element_version_id AND code = 'organization'), 1, NULL, 1),
    (@org_element_version_id, (SELECT id FROM organizational_element_types WHERE catalog_version_id = @org_element_version_id AND code = 'battalion'), (SELECT id FROM organizational_element_classes WHERE catalog_version_id = @org_element_version_id AND code = 'subdivision'), 1, NULL, 1),
    (@org_element_version_id, (SELECT id FROM organizational_element_types WHERE catalog_version_id = @org_element_version_id AND code = 'battalion'), (SELECT id FROM organizational_element_classes WHERE catalog_version_id = @org_element_version_id AND code = 'military-unit'), 0, 'Отдельный батальон может иметь статус воинской части при наличии утверждённого основания.', 2),
    (@org_element_version_id, (SELECT id FROM organizational_element_types WHERE catalog_version_id = @org_element_version_id AND code = 'divizion'), (SELECT id FROM organizational_element_classes WHERE catalog_version_id = @org_element_version_id AND code = 'subdivision'), 1, NULL, 1),
    (@org_element_version_id, (SELECT id FROM organizational_element_types WHERE catalog_version_id = @org_element_version_id AND code = 'divizion'), (SELECT id FROM organizational_element_classes WHERE catalog_version_id = @org_element_version_id AND code = 'military-unit'), 0, 'Отдельный дивизион может иметь статус воинской части при наличии утверждённого основания.', 2),
    (@org_element_version_id, (SELECT id FROM organizational_element_types WHERE catalog_version_id = @org_element_version_id AND code = 'company'), (SELECT id FROM organizational_element_classes WHERE catalog_version_id = @org_element_version_id AND code = 'subdivision'), 1, NULL, 1),
    (@org_element_version_id, (SELECT id FROM organizational_element_types WHERE catalog_version_id = @org_element_version_id AND code = 'battery'), (SELECT id FROM organizational_element_classes WHERE catalog_version_id = @org_element_version_id AND code = 'subdivision'), 1, NULL, 1),
    (@org_element_version_id, (SELECT id FROM organizational_element_types WHERE catalog_version_id = @org_element_version_id AND code = 'platoon'), (SELECT id FROM organizational_element_classes WHERE catalog_version_id = @org_element_version_version_id AND code = 'subdivision'), 1, NULL, 1),
    (@org_element_version_id, (SELECT id FROM organizational_element_types WHERE catalog_version_id = @org_element_version_id AND code = 'group'), (SELECT id FROM organizational_element_classes WHERE catalog_version_id = @org_element_version_id AND code = 'subdivision'), 1, NULL, 1),
    (@org_element_version_id, (SELECT id FROM organizational_element_types WHERE catalog_version_id = @org_element_version_id AND code = 'section'), (SELECT id FROM organizational_element_classes WHERE catalog_version_id = @org_element_version_id AND code = 'subdivision'), 1, NULL, 1),
    (@org_element_version_id, (SELECT id FROM organizational_element_types WHERE catalog_version_id = @org_element_version_id AND code = 'team'), (SELECT id FROM organizational_element_classes WHERE catalog_version_id = @org_element_version_id AND code = 'subdivision'), 1, NULL, 1),
    (@org_element_version_id, (SELECT id FROM organizational_element_types WHERE catalog_version_id = @org_element_version_id AND code = 'raschet'), (SELECT id FROM organizational_element_classes WHERE catalog_version_id = @org_element_version_id AND code = 'subdivision'), 1, NULL, 1),
    (@org_element_version_id, (SELECT id FROM organizational_element_types WHERE catalog_version_id = @org_element_version_id AND code = 'crew'), (SELECT id FROM organizational_element_classes WHERE catalog_version_id = @org_element_version_id AND code = 'subdivision'), 1, NULL, 1),
    (@org_element_version_id, (SELECT id FROM organizational_element_types WHERE catalog_version_id = @org_element_version_id AND code = 'ship'), (SELECT id FROM organizational_element_classes WHERE catalog_version_id = @org_element_version_id AND code = 'military-unit'), 1, NULL, 1),
    (@org_element_version_id, (SELECT id FROM organizational_element_types WHERE catalog_version_id = @org_element_version_id AND code = 'combat-unit'), (SELECT id FROM organizational_element_classes WHERE catalog_version_id = @org_element_version_id AND code = 'subdivision'), 1, NULL, 1)
ON DUPLICATE KEY UPDATE
    is_primary = VALUES(is_primary),
    context_note = VALUES(context_note),
    sort_order = VALUES(sort_order);

INSERT INTO organizational_element_type_sources
    (catalog_version_id, type_id, legal_source_id, source_role, provision_detail, sort_order)
SELECT @org_element_version_id, t.id, s.id,
    CASE
        WHEN t.code IN ('army', 'corps', 'division', 'brigade', 'regiment', 'arsenal', 'test-center', 'storage-supply-base') THEN 'classification'
        ELSE 'official-usage'
    END,
    CASE
        WHEN t.code IN ('army', 'corps', 'division', 'brigade', 'regiment', 'arsenal', 'test-center', 'storage-supply-base') THEN 'Статья 11 Положения о порядке прохождения военной службы.'
        ELSE 'Официальное употребление термина в Положении о порядке прохождения военной службы.'
    END,
    1
FROM organizational_element_types t
JOIN legal_sources s ON s.code = 'presidential-decree-1237-article-11'
WHERE t.catalog_version_id = @org_element_version_id
  AND t.code IN ('administration', 'headquarters', 'service', 'direction', 'department', 'army', 'corps', 'division', 'brigade', 'regiment', 'arsenal', 'test-center', 'storage-supply-base', 'enterprise', 'institution', 'military-educational-organization')
ON DUPLICATE KEY UPDATE
    provision_detail = VALUES(provision_detail),
    sort_order = VALUES(sort_order);

INSERT INTO organizational_element_type_sources
    (catalog_version_id, type_id, legal_source_id, source_role, provision_detail, sort_order)
SELECT @org_element_version_id, t.id, s.id, 'official-usage',
    'Официальное употребление типа в Уставе внутренней службы Вооружённых Сил Российской Федерации.',
    1
FROM organizational_element_types t
JOIN legal_sources s ON s.code = 'presidential-decree-1495-internal-service-charter'
WHERE t.catalog_version_id = @org_element_version_id
  AND t.code IN ('battalion', 'company', 'platoon', 'group', 'section', 'team', 'raschet', 'crew')
ON DUPLICATE KEY UPDATE
    provision_detail = VALUES(provision_detail),
    sort_order = VALUES(sort_order);

INSERT INTO organizational_element_type_sources
    (catalog_version_id, type_id, legal_source_id, source_role, provision_detail, sort_order)
SELECT @org_element_version_id, t.id, s.id, 'official-usage',
    CASE
        WHEN t.code = 'ship' THEN 'Статьи 12–18 Корабельного устава: корабельная организация и статус корабля.'
        WHEN t.code = 'combat-unit' THEN 'Статьи 27–35 Корабельного устава: боевая часть и официальное сокращение БЧ.'
        ELSE 'Статьи 27–35 Корабельного устава: официальное употребление типа.'
    END,
    1
FROM organizational_element_types t
JOIN legal_sources s ON s.code = 'presidential-decree-511-ship-charter'
WHERE t.catalog_version_id = @org_element_version_id
  AND t.code IN ('divizion', 'battery', 'ship', 'combat-unit')
ON DUPLICATE KEY UPDATE
    provision_detail = VALUES(provision_detail),
    sort_order = VALUES(sort_order);
