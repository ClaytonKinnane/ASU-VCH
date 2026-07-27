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
SELECT @org_element_version_id, s.id, seed.source_role, seed.sort_order
FROM JSON_TABLE(
    '[
        {"code":"federal-law-61-fz-article-11","source_role":"general-composition","sort_order":1},
        {"code":"presidential-decree-1237-article-11","source_role":"classification","sort_order":2},
        {"code":"presidential-decree-1495-internal-service-charter","source_role":"internal-service","sort_order":3},
        {"code":"presidential-decree-511-ship-charter","source_role":"naval-organization","sort_order":4}
    ]',
    '$[*]' COLUMNS (
        code VARCHAR(120) PATH '$.code',
        source_role VARCHAR(80) PATH '$.source_role',
        sort_order SMALLINT PATH '$.sort_order'
    )
) AS seed
JOIN legal_sources s ON s.code = seed.code
ON DUPLICATE KEY UPDATE
    source_role = VALUES(source_role),
    sort_order = VALUES(sort_order);

INSERT INTO organizational_element_classes
    (catalog_version_id, code, name, description, sort_order, created_at)
SELECT @org_element_version_id, seed.code, seed.name, seed.description, seed.sort_order, @org_element_now
FROM JSON_TABLE(
    '[
        {"code":"military-command-body","name":"Орган военного управления","description":"Организационный элемент, выполняющий функции военного управления в установленном нормативном контексте.","sort_order":1},
        {"code":"association","name":"Объединение","description":"Организационный класс оперативного или стратегического масштаба, прямо используемый нормативными актами.","sort_order":2},
        {"code":"formation","name":"Соединение","description":"Организационный класс, объединяющий несколько воинских частей или меньших соединений.","sort_order":3},
        {"code":"military-unit","name":"Воинская часть","description":"Самостоятельный организационный класс, статус которого определяется утверждённым штатом и правовым основанием.","sort_order":4},
        {"code":"organization","name":"Организация","description":"Организация Вооружённых Сил, не классифицируемая в данном контексте как воинская часть.","sort_order":5},
        {"code":"subdivision","name":"Подразделение","description":"Организационный элемент, входящий в состав другого элемента согласно утверждённой структуре.","sort_order":6}
    ]',
    '$[*]' COLUMNS (
        code VARCHAR(100) PATH '$.code',
        name VARCHAR(255) PATH '$.name',
        description VARCHAR(1000) PATH '$.description',
        sort_order SMALLINT PATH '$.sort_order'
    )
) AS seed
ON DUPLICATE KEY UPDATE
    name = VALUES(name),
    description = VALUES(description),
    sort_order = VALUES(sort_order);

INSERT INTO organizational_element_types
    (catalog_version_id, code, name, short_name, description, applicability_note, sort_order, created_at)
SELECT @org_element_version_id, seed.code, seed.name, seed.short_name, seed.description,
    seed.applicability_note, seed.sort_order, @org_element_now
FROM JSON_TABLE(
    '[
        {"code":"administration","name":"управление","short_name":null,"description":"Организационный элемент управления, официально используемый для органов и внутренних элементов структуры.","applicability_note":"Класс определяется конкретным нормативным положением и утверждённой структурой.","sort_order":1},
        {"code":"headquarters","name":"штаб","short_name":null,"description":"Орган управления, обеспечивающий планирование, координацию и управление в установленном контексте.","applicability_note":"Может выступать органом военного управления либо подразделением другого элемента.","sort_order":2},
        {"code":"service","name":"служба","short_name":null,"description":"Функциональное подразделение по установленному направлению деятельности.","applicability_note":"Состав и место службы определяются утверждённым штатом.","sort_order":3},
        {"code":"direction","name":"направление","short_name":null,"description":"Функциональный организационный элемент, официально употребляемый в структурах органов управления.","applicability_note":"Не задаёт универсального уровня и применяется только в подтверждённом контексте.","sort_order":4},
        {"code":"department","name":"отдел","short_name":null,"description":"Функциональное подразделение органа управления или организации.","applicability_note":"Место отдела в структуре определяется конкретным положением или штатом.","sort_order":5},
        {"code":"army","name":"армия","short_name":null,"description":"Организационный тип, нормативно классифицируемый как объединение.","applicability_note":"Не определяет состав конкретной армии.","sort_order":6},
        {"code":"corps","name":"корпус","short_name":null,"description":"Организационный тип, нормативно классифицируемый как соединение.","applicability_note":"Не определяет состав конкретного корпуса.","sort_order":7},
        {"code":"division","name":"дивизия","short_name":null,"description":"Организационный тип, нормативно классифицируемый как соединение.","applicability_note":"Не определяет состав конкретной дивизии.","sort_order":8},
        {"code":"brigade","name":"бригада","short_name":null,"description":"Организационный тип, нормативно классифицируемый как соединение.","applicability_note":"Не определяет состав конкретной бригады.","sort_order":9},
        {"code":"regiment","name":"полк","short_name":null,"description":"Основная тактическая и административно-хозяйственная единица, содержащаяся по установленному штату.","applicability_note":"В справочнике фиксируется общий тип без состава конкретного полка.","sort_order":10},
        {"code":"arsenal","name":"арсенал","short_name":null,"description":"Организационный тип, официально относимый к воинским частям в нормативном контексте.","applicability_note":"Фактическое назначение и состав конкретного арсенала не хранятся.","sort_order":11},
        {"code":"test-center","name":"испытательный центр","short_name":null,"description":"Организационный тип, официально относимый к воинским частям в нормативном контексте.","applicability_note":"Не описывает специализацию и состав конкретного центра.","sort_order":12},
        {"code":"storage-supply-base","name":"база хранения и снабжения","short_name":null,"description":"Организационный тип, официально относимый к воинским частям в нормативном контексте.","applicability_note":"Не содержит сведений о запасах, дислокации или фактической структуре.","sort_order":13},
        {"code":"enterprise","name":"предприятие","short_name":null,"description":"Организация, официально упоминаемая в составе системы Вооружённых Сил.","applicability_note":"Организационно-правовая форма конкретного предприятия определяется отдельно.","sort_order":14},
        {"code":"institution","name":"учреждение","short_name":null,"description":"Организация, официально упоминаемая в составе системы Вооружённых Сил.","applicability_note":"Не определяет ведомственный статус конкретного учреждения.","sort_order":15},
        {"code":"military-educational-organization","name":"военная образовательная организация","short_name":null,"description":"Организация, осуществляющая образовательную деятельность в установленной сфере.","applicability_note":"Конкретный вид и статус определяются учредительными и нормативными документами.","sort_order":16},
        {"code":"battalion","name":"батальон","short_name":null,"description":"Тактический организационный элемент, используемый как подразделение и в отдельных случаях как воинская часть.","applicability_note":"Самостоятельный статус возможен только при наличии соответствующего утверждённого основания.","sort_order":17},
        {"code":"divizion","name":"дивизион","short_name":null,"description":"Организационный элемент, используемый в сухопутной и корабельной организации.","applicability_note":"Самостоятельный статус и область применения определяются конкретным нормативным контекстом.","sort_order":18},
        {"code":"company","name":"рота","short_name":null,"description":"Тактическое подразделение, официально используемое в общевоинской организации.","applicability_note":"Состав и место в структуре определяются утверждённым штатом.","sort_order":19},
        {"code":"battery","name":"батарея","short_name":null,"description":"Тактическое подразделение, официально используемое в общевоинской и корабельной организации.","applicability_note":"Состав и принадлежность определяются утверждённой структурой.","sort_order":20},
        {"code":"platoon","name":"взвод","short_name":null,"description":"Тактическое подразделение, официально используемое в общевоинской организации.","applicability_note":"Состав и место в структуре определяются утверждённым штатом.","sort_order":21},
        {"code":"group","name":"группа","short_name":null,"description":"Организационное подразделение, официально употребляемое в уставах.","applicability_note":"Название само по себе не определяет уровень, численность или назначение.","sort_order":22},
        {"code":"section","name":"отделение","short_name":null,"description":"Низовое подразделение, официально используемое в общевоинской и корабельной организации.","applicability_note":"Состав определяется конкретным штатом или корабельной организацией.","sort_order":23},
        {"code":"team","name":"команда","short_name":null,"description":"Организационное подразделение, официально используемое в уставах.","applicability_note":"Не следует смешивать с временной командой вне подтверждённого организационного контекста.","sort_order":24},
        {"code":"raschet","name":"расчёт","short_name":null,"description":"Организационное подразделение личного состава для совместного выполнения установленных обязанностей.","applicability_note":"Конкретное назначение расчёта в справочнике не фиксируется.","sort_order":25},
        {"code":"crew","name":"экипаж","short_name":null,"description":"Организационное подразделение личного состава, обслуживающее соответствующий объект или средство.","applicability_note":"Тип не содержит сведений о технике и фактическом составе экипажа.","sort_order":26},
        {"code":"ship","name":"корабль","short_name":null,"description":"Боевая тактическая и административно-хозяйственная единица, содержащаяся по установленному штату.","applicability_note":"Классификация как воинской части применяется только в соответствующем правовом контексте.","sort_order":27},
        {"code":"combat-unit","name":"боевая часть","short_name":"БЧ","description":"Основной организационный элемент корабельной организации по установленному назначению.","applicability_note":"Применяется к корабельной организации и не является универсальным общевойсковым подразделением.","sort_order":28}
    ]',
    '$[*]' COLUMNS (
        code VARCHAR(100) PATH '$.code',
        name VARCHAR(255) PATH '$.name',
        short_name VARCHAR(100) PATH '$.short_name' NULL ON EMPTY,
        description VARCHAR(1000) PATH '$.description',
        applicability_note VARCHAR(1000) PATH '$.applicability_note',
        sort_order SMALLINT PATH '$.sort_order'
    )
) AS seed
ON DUPLICATE KEY UPDATE
    name = VALUES(name),
    short_name = VALUES(short_name),
    description = VALUES(description),
    applicability_note = VALUES(applicability_note),
    sort_order = VALUES(sort_order);

INSERT INTO organizational_element_type_classes
    (catalog_version_id, type_id, class_id, is_primary, context_note, sort_order)
SELECT @org_element_version_id, t.id, c.id, seed.is_primary, seed.context_note, seed.sort_order
FROM JSON_TABLE(
    '[
        {"type_code":"administration","class_code":"military-command-body","is_primary":1,"context_note":null,"sort_order":1},
        {"type_code":"administration","class_code":"subdivision","is_primary":0,"context_note":"Управление может быть внутренним подразделением другого организационного элемента.","sort_order":2},
        {"type_code":"headquarters","class_code":"military-command-body","is_primary":1,"context_note":null,"sort_order":1},
        {"type_code":"headquarters","class_code":"subdivision","is_primary":0,"context_note":"Штаб может входить в состав другого организационного элемента как подразделение.","sort_order":2},
        {"type_code":"service","class_code":"subdivision","is_primary":1,"context_note":null,"sort_order":1},
        {"type_code":"direction","class_code":"subdivision","is_primary":1,"context_note":null,"sort_order":1},
        {"type_code":"department","class_code":"subdivision","is_primary":1,"context_note":null,"sort_order":1},
        {"type_code":"army","class_code":"association","is_primary":1,"context_note":null,"sort_order":1},
        {"type_code":"corps","class_code":"formation","is_primary":1,"context_note":null,"sort_order":1},
        {"type_code":"division","class_code":"formation","is_primary":1,"context_note":null,"sort_order":1},
        {"type_code":"brigade","class_code":"formation","is_primary":1,"context_note":null,"sort_order":1},
        {"type_code":"regiment","class_code":"military-unit","is_primary":1,"context_note":null,"sort_order":1},
        {"type_code":"arsenal","class_code":"military-unit","is_primary":1,"context_note":null,"sort_order":1},
        {"type_code":"test-center","class_code":"military-unit","is_primary":1,"context_note":null,"sort_order":1},
        {"type_code":"storage-supply-base","class_code":"military-unit","is_primary":1,"context_note":null,"sort_order":1},
        {"type_code":"enterprise","class_code":"organization","is_primary":1,"context_note":null,"sort_order":1},
        {"type_code":"institution","class_code":"organization","is_primary":1,"context_note":null,"sort_order":1},
        {"type_code":"military-educational-organization","class_code":"organization","is_primary":1,"context_note":null,"sort_order":1},
        {"type_code":"battalion","class_code":"subdivision","is_primary":1,"context_note":null,"sort_order":1},
        {"type_code":"battalion","class_code":"military-unit","is_primary":0,"context_note":"Отдельный батальон может иметь статус воинской части при наличии утверждённого основания.","sort_order":2},
        {"type_code":"divizion","class_code":"subdivision","is_primary":1,"context_note":null,"sort_order":1},
        {"type_code":"divizion","class_code":"military-unit","is_primary":0,"context_note":"Отдельный дивизион может иметь статус воинской части при наличии утверждённого основания.","sort_order":2},
        {"type_code":"company","class_code":"subdivision","is_primary":1,"context_note":null,"sort_order":1},
        {"type_code":"battery","class_code":"subdivision","is_primary":1,"context_note":null,"sort_order":1},
        {"type_code":"platoon","class_code":"subdivision","is_primary":1,"context_note":null,"sort_order":1},
        {"type_code":"group","class_code":"subdivision","is_primary":1,"context_note":null,"sort_order":1},
        {"type_code":"section","class_code":"subdivision","is_primary":1,"context_note":null,"sort_order":1},
        {"type_code":"team","class_code":"subdivision","is_primary":1,"context_note":null,"sort_order":1},
        {"type_code":"raschet","class_code":"subdivision","is_primary":1,"context_note":null,"sort_order":1},
        {"type_code":"crew","class_code":"subdivision","is_primary":1,"context_note":null,"sort_order":1},
        {"type_code":"ship","class_code":"military-unit","is_primary":1,"context_note":null,"sort_order":1},
        {"type_code":"combat-unit","class_code":"subdivision","is_primary":1,"context_note":null,"sort_order":1}
    ]',
    '$[*]' COLUMNS (
        type_code VARCHAR(100) PATH '$.type_code',
        class_code VARCHAR(100) PATH '$.class_code',
        is_primary TINYINT PATH '$.is_primary',
        context_note VARCHAR(1000) PATH '$.context_note' NULL ON EMPTY,
        sort_order SMALLINT PATH '$.sort_order'
    )
) AS seed
JOIN organizational_element_types t
    ON t.catalog_version_id = @org_element_version_id AND t.code = seed.type_code
JOIN organizational_element_classes c
    ON c.catalog_version_id = @org_element_version_id AND c.code = seed.class_code
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
