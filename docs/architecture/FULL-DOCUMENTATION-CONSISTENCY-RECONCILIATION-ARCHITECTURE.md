# Full Documentation Consistency Reconciliation — Architecture

## 1. Статус

```text
stage: Architecture
status: APPROVED
classification: documentation-only
repository: ClaytonKinnane/ASU-VCH
branch: docs/full-documentation-consistency-reconciliation
baseline main: 1eef56b50a8d2278a62c5b70a471663b12132354
date: 2026-08-02
```

## 2. Основание

Полный read-only аудит документации выявил, что канонический current-state слой в целом соответствует merged functional baseline, однако отдельные index и target/historical documents содержат устаревшие или неоднозначные утверждения.

Подтверждённые findings:

1. `docs/domains/README.md` завершает functional inventory на PR #15 и не отражает каталоги PR #19/#20.
2. `docs/migrations/README.md` объявляет текущими только migrations 001–009 вместо 001–011.
3. `docs/LOCAL-RUNBOOK.md` запрещает публикацию любых credentials, хотя существующий local-only development fixture `Admin / 12315` публично зафиксирован в specification и runtime seed.
4. `docs/DATABASE.md` содержит несуществующую ссылку `DOMAINS.md`.
5. `docs/STARTER-ADMIN-SPEC.md` не имеет явного historical/implemented framing.
6. `docs/DATABASE.md` представляет первоначальную последовательность проектирования как текущий будущий план.
7. `docs/domains/README.md` содержит сокращённый workflow, не совпадающий с обязательным project workflow.

## 3. Цели

- восстановить согласованность всей документации с текущим merged baseline;
- отделить current state, target architecture и historical evidence;
- устранить устаревшие domain/migration indexes;
- исправить broken textual reference;
- согласовать terminology для публичного local-only fixture;
- предотвратить повторение дефекта через semantic classification rule;
- сохранить runtime, schema, configuration и historical evidence неизменными.

## 4. Архитектурное решение

Применяется documentation-only reconciliation. Runtime password hardening не смешивается с этим инкрементом и переносится в отдельный будущий Security workflow.

```text
DOCUMENTATION_ONLY_RECONCILIATION
+
SEPARATE_FUTURE_SECURITY_HARDENING
```

## 5. Semantic classification

Класс документа определяется его утверждениями, а не только каталогом.

Раздел считается living/current-state, если он сообщает:

- текущий functional baseline;
- текущую нумерацию migrations;
- текущую карту реализованных доменов;
- текущий набор ролей, permissions, themes или routes;
- текущий repository status.

Следствия:

- `docs/domains/README.md` — living domain index;
- `docs/migrations/README.md` — living migration index;
- `docs/DATABASE.md` — target architecture с отдельным current-schema source;
- `docs/STARTER-ADMIN-SPEC.md` — historical implemented specification;
- датированные Architecture, Review, Approval и Test Evidence — historical artifacts.

Mixed documents обязаны явно маркировать current-state и target/historical sections.

## 6. Source-of-truth hierarchy

```text
Live repository state       → GitHub / Git
Current functional state    → docs/PROJECT-STATUS.md
Current physical schema     → docs/DATABASE-CURRENT.md + executable migrations
Theme contract              → config/themes.php
Target architecture         → docs/DATABASE.md, domain/ERD/migration specifications
Historical gate evidence    → dated process and testing records
```

## 7. Local fixture terminology

Текущий `Admin / 12315` классифицируется как публично известный local-only development fixture, а не как secret конкретной установки.

Запрещена публикация:

- production credentials;
- instance-specific credentials;
- реальных временных паролей пользователей;
- session data;
- содержимого `config/local.php`.

Local fixture:

- разрешён только при `environment=local`;
- не должен использоваться повторно;
- требует обязательной смены при первом входе;
- запрещён для production;
- подлежит отдельному будущему hardening increment.

## 8. Scope boundaries

Разрешены только 15 Markdown paths из утверждённой Specification.

Не изменяются:

```text
app/**
config/**
database/**
deploy/**
public/**
themes/**
tools/**
Git refs вне создания утверждённой feature branch
```

## 9. Anti-recursion

Living docs не должны хранить номер, state или branch настоящего documentation PR как постоянно актуальное поле. Current PRs, Issues, branches и exact HEAD определяются динамически.

## 10. Testing classification

```text
PHP lint: NOT REQUIRED
SQL/schema testing: NOT REQUIRED
installer: NOT REQUIRED
deploy: NOT REQUIRED
browser testing: NOT REQUIRED
runtime/database retest: NOT REQUIRED
mobile testing: OUT OF SCOPE / NOT RUN
```

Обязательна Documentation Validation exact changed-path allowlist, semantic consistency, links, stale markers, credential terminology, secrets и main integrity.
