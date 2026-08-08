<?php

declare(strict_types=1);

ob_start();

$root = dirname(__DIR__);
$failures = [];
$passes = 0;

function personnel_check(bool $ok, string $label): void
{
    global $failures, $passes;
    if ($ok) {
        $passes++;
        echo "PASS: {$label}\n";
        return;
    }
    $failures[] = $label;
    echo "FAIL: {$label}\n";
}

function personnel_contents(string $root, string $path): string
{
    $file = $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $path);
    if (!is_file($file)) {
        return '';
    }
    $value = file_get_contents($file);
    return is_string($value) ? $value : '';
}

$allowlist = [
    'public/admin/content.php','docs/domains/README.md','docs/DATABASE.md','docs/DATABASE-CURRENT.md','docs/ACCESS.md',
    'docs/PROJECT-STATUS.md','docs/ROADMAP.md','docs/TRACEABILITY.md','docs/CHAT-HANDOFF.md',
    'database/migrations/015_personnel_core_card_v1.sql','app/Personnel/PersonnelRepository.php','app/Personnel/PersonnelService.php',
    'app/Personnel/PersonnelSupportTrait.php','app/Personnel/PersonnelCreateUpdateTrait.php','app/Personnel/PersonnelIdentifierTrait.php',
    'app/Personnel/PersonnelLifecycleTrait.php','app/Personnel/functions.php','public/admin/personnel/persons.php','public/admin/personnel/person.php',
    'public/admin/personnel/persons/create.php','public/admin/personnel/persons/update.php','public/admin/personnel/persons/archive.php',
    'public/admin/personnel/persons/restore.php','public/admin/personnel/identifiers/create.php','public/admin/personnel/identifiers/replace.php',
    'public/admin/personnel/identifiers/end.php','public/admin/personnel/history.php','public/admin/personnel/views/person-list.php',
    'public/admin/personnel/views/person-card.php','public/admin/personnel/views/person-form.php','public/admin/personnel/views/identifier-form.php',
    'public/admin/personnel/views/history-list.php','tools/check-personnel-core-card-v1.php','tools/Test-PersonnelCoreCardV1.ps1',
    'docs/domains/PERSONNEL.md','docs/design/PERSONNEL-ACCESS-FUTURE-DESIGN-NOTES.md','docs/design/PERSONNEL-CORE-CARD-V1-ARCHITECTURE.md',
    'docs/design/PERSONNEL-CORE-CARD-V1-SPECIFICATION.md','docs/design/PERSONNEL-CORE-CARD-V1-REVIEW.md','docs/design/PERSONNEL-CORE-CARD-V1-APPROVAL.md',
];
personnel_check(count($allowlist) === 40 && count(array_unique($allowlist)) === 40, 'exact 40-path approved allowlist');

$required = [
    'database/migrations/015_personnel_core_card_v1.sql','app/Personnel/PersonnelRepository.php','app/Personnel/PersonnelService.php',
    'app/Personnel/PersonnelSupportTrait.php','app/Personnel/PersonnelCreateUpdateTrait.php','app/Personnel/PersonnelIdentifierTrait.php',
    'app/Personnel/PersonnelLifecycleTrait.php','app/Personnel/functions.php','public/admin/personnel/persons.php','public/admin/personnel/person.php',
    'public/admin/personnel/persons/create.php','public/admin/personnel/persons/update.php','public/admin/personnel/persons/archive.php',
    'public/admin/personnel/persons/restore.php','public/admin/personnel/identifiers/create.php','public/admin/personnel/identifiers/replace.php',
    'public/admin/personnel/identifiers/end.php','public/admin/personnel/history.php','public/admin/personnel/views/person-list.php',
    'public/admin/personnel/views/person-card.php','public/admin/personnel/views/person-form.php','public/admin/personnel/views/identifier-form.php',
    'public/admin/personnel/views/history-list.php','tools/Test-PersonnelCoreCardV1.ps1','docs/domains/PERSONNEL.md',
    'docs/design/PERSONNEL-ACCESS-FUTURE-DESIGN-NOTES.md','docs/design/PERSONNEL-CORE-CARD-V1-ARCHITECTURE.md',
    'docs/design/PERSONNEL-CORE-CARD-V1-SPECIFICATION.md','docs/design/PERSONNEL-CORE-CARD-V1-REVIEW.md','docs/design/PERSONNEL-CORE-CARD-V1-APPROVAL.md',
];
foreach ($required as $path) {
    personnel_check(is_file($root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $path)), "required path exists: {$path}");
}

$migration = personnel_contents($root, 'database/migrations/015_personnel_core_card_v1.sql');
foreach (['personnel_records','personnel_identifier_types','personnel_identifiers','personnel_change_events'] as $table) {
    personnel_check(str_contains($migration, "CREATE TABLE IF NOT EXISTS {$table}"), "migration creates {$table}");
}
foreach (['personal_number','service_dog_tag','table_number','call_sign'] as $code) {
    personnel_check(substr_count($migration, "('{$code}'") === 1, "identifier type seeded exactly once: {$code}");
}
personnel_check(!str_contains($migration, 'INSERT INTO permissions'), 'migration adds no permissions');
personnel_check(!str_contains($migration, 'INSERT INTO role_permissions'), 'migration adds no role grants');
personnel_check(str_contains($migration, 'uq_personnel_identifiers_active'), 'one active identifier per person/type DB guard');
personnel_check(str_contains($migration, 'uq_personnel_identifiers_global_history'), 'historical never-reuse DB guard');
personnel_check(str_contains($migration, 'PERSONNEL_EVENT_APPEND_ONLY'), 'append-only event DB guard');
personnel_check(str_contains($migration, 'PERSONNEL_RECORD_DELETE_FORBIDDEN'), 'record physical delete forbidden');
personnel_check(str_contains($migration, 'PERSONNEL_IDENTIFIER_DELETE_FORBIDDEN'), 'identifier physical delete forbidden');
personnel_check(!preg_match('/\b(position_id|department_id|rank_id|vus_id)\b/i', $migration), 'no assignment/rank/VUS truth in Personnel Core');
personnel_check(!preg_match('/\b(BLOB|LONGBLOB|MEDIUMBLOB|TINYBLOB)\b/i', $migration), 'no binary storage');

$service = personnel_contents($root, 'app/Personnel/PersonnelService.php');
foreach (['PersonnelSupportTrait','PersonnelCreateUpdateTrait','PersonnelIdentifierTrait','PersonnelLifecycleTrait'] as $trait) {
    personnel_check(str_contains($service, "use {$trait};"), "service composes {$trait}");
}
$functions = personnel_contents($root, 'app/Personnel/functions.php');
personnel_check(str_contains($functions, 'require_system_owner();'), 'prototype uses system_owner gate');
personnel_check(str_contains($functions, 'require_csrf();'), 'mutations require CSRF');
personnel_check(str_contains($functions, "str_starts_with(\$path, '/admin/personnel/')"), 'PRG return path is Personnel-scoped');

$routes = [
    'public/admin/personnel/persons.php','public/admin/personnel/person.php','public/admin/personnel/persons/create.php',
    'public/admin/personnel/persons/update.php','public/admin/personnel/persons/archive.php','public/admin/personnel/persons/restore.php',
    'public/admin/personnel/identifiers/create.php','public/admin/personnel/identifiers/replace.php','public/admin/personnel/identifiers/end.php',
    'public/admin/personnel/history.php',
];
foreach ($routes as $path) {
    personnel_check(str_contains(personnel_contents($root, $path), 'personnel_require_owner();'), "owner gate: {$path}");
}
$content = personnel_contents($root, 'public/admin/content.php');
personnel_check(str_contains($content, '/admin/personnel/persons.php'), 'content page links active Personnel tile');
personnel_check(str_contains($content, 'if ($isOwner)'), 'Personnel tile remains owner-gated');
$views = personnel_contents($root, 'public/admin/personnel/views/person-card.php') . personnel_contents($root, 'public/admin/personnel/views/person-list.php');
personnel_check(str_contains($views, 'Не реализовано в v1'), 'future dossier sections are explicitly marked');
personnel_check(!str_contains($views, '<input type="file"'), 'no file upload UI');

$architecture = personnel_contents($root, 'docs/design/PERSONNEL-CORE-CARD-V1-ARCHITECTURE.md');
$specification = personnel_contents($root, 'docs/design/PERSONNEL-CORE-CARD-V1-SPECIFICATION.md');
$review = personnel_contents($root, 'docs/design/PERSONNEL-CORE-CARD-V1-REVIEW.md');
$approval = personnel_contents($root, 'docs/design/PERSONNEL-CORE-CARD-V1-APPROVAL.md');
personnel_check(str_contains($architecture, 'VERSION=0.2'), 'Architecture v0.2 present');
personnel_check(str_contains($specification, 'VERSION=0.2'), 'Specification v0.2 present');
personnel_check(str_contains($review, 'REVIEW_STATUS=PASS'), 'Formal Review PASS present');
personnel_check(str_contains($approval, 'APPROVAL_STATUS=APPROVED'), 'Owner Approval present');

$harness = personnel_contents($root, 'tools/Test-PersonnelCoreCardV1.ps1');
personnel_check(str_contains($harness, '--runtime-bootstrap='), 'runtime checker is wired to deployed bootstrap');

if (is_dir($root . DIRECTORY_SEPARATOR . '.git') && function_exists('exec')) {
    $output = [];
    $code = 0;
    exec('git -C ' . escapeshellarg($root) . ' diff --name-only dadc2dd2c1151a797cfc2f6690bcf19b1f73e4b8...HEAD 2>&1', $output, $code);
    personnel_check($code === 0, 'git diff inventory succeeds');
    if ($code === 0) {
        $unexpected = array_values(array_diff($output, $allowlist));
        personnel_check(count($output) <= 40, 'changed path count <= 40');
        personnel_check($unexpected === [], 'changed paths stay inside allowlist');
        if ($unexpected !== []) {
            echo 'UNEXPECTED_PATHS=' . implode(',', $unexpected) . "\n";
        }
    }
}

$runRuntime = in_array('--runtime', $argv, true);
$runtimeBootstrap = null;
foreach ($argv as $argument) {
    if (is_string($argument) && str_starts_with($argument, '--runtime-bootstrap=')) {
        $runtimeBootstrap = substr($argument, strlen('--runtime-bootstrap='));
        break;
    }
}

if ($runRuntime) {
    $bootstrapPath = is_string($runtimeBootstrap) && $runtimeBootstrap !== ''
        ? $runtimeBootstrap
        : $root . '/app/bootstrap.php';
    personnel_check(is_file($bootstrapPath), 'runtime bootstrap exists');
    $runtimeRoot = dirname(dirname($bootstrapPath));
    personnel_check(is_file($runtimeRoot . '/config/local.php'), 'runtime local config exists beside deployed bootstrap');
    if (!is_file($bootstrapPath) || !is_file($runtimeRoot . '/config/local.php')) {
        fwrite(STDERR, "PERSONNEL_CORE_CARD_V1_RUNTIME_BOOTSTRAP=FAIL\n");
        exit(1);
    }

    require_once $bootstrapPath;
    $pdo = db();
    $tables = $pdo->query("SELECT table_name FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name LIKE 'personnel_%' ORDER BY table_name")->fetchAll(PDO::FETCH_COLUMN);
    $expectedTables = ['personnel_change_events','personnel_identifier_types','personnel_identifiers','personnel_records'];
    personnel_check($tables === $expectedTables, 'runtime has exactly four Personnel tables');
    personnel_check((int) $pdo->query('SELECT COUNT(*) FROM personnel_identifier_types')->fetchColumn() === 4, 'runtime has four identifier types');
    personnel_check((int) $pdo->query('SELECT COUNT(*) FROM permissions')->fetchColumn() === 35, 'permission total remains 35');
    personnel_check((int) $pdo->query("SELECT COUNT(*) FROM migrations WHERE migration='015_personnel_core_card_v1.sql'")->fetchColumn() === 1, 'migration 015 recorded exactly once');
    $actorId = (int) $pdo->query("SELECT u.id FROM users u JOIN user_roles ur ON ur.user_id=u.id JOIN roles r ON r.id=ur.role_id WHERE r.code='system_owner' AND u.deleted_at IS NULL ORDER BY u.id LIMIT 1")->fetchColumn();
    personnel_check($actorId > 0, 'system_owner actor available for runtime invariant tests');
    if ($actorId > 0) {
        $pdo->beginTransaction();
        try {
            $now = (new DateTimeImmutable())->format('Y-m-d H:i:s');
            $insertPerson = $pdo->prepare("INSERT INTO personnel_records(last_name,first_name,birth_date,record_status,revision,created_by,created_at,updated_by,updated_at) VALUES('Тестов','Прототип','1990-01-01','active',1,:created_by,:created_at,:updated_by,:updated_at)");
            $insertPerson->execute([
                'created_by' => $actorId,
                'created_at' => $now,
                'updated_by' => $actorId,
                'updated_at' => $now,
            ]);
            $personId = (int) $pdo->lastInsertId();
            $types = [];
            foreach ($pdo->query('SELECT id,code,enforce_global_unique FROM personnel_identifier_types')->fetchAll() as $type) {
                $types[(string) $type['code']] = $type;
            }
            $insertIdentifier = $pdo->prepare('INSERT INTO personnel_identifiers(personnel_id,identifier_type_id,enforce_global_unique,value,valid_from,created_by,created_at) VALUES(:personnel_id,:type_id,:policy,:value,:valid_from,:actor,:now)');
            $personal = $types['personal_number'];
            $insertIdentifier->execute(['personnel_id'=>$personId,'type_id'=>$personal['id'],'policy'=>$personal['enforce_global_unique'],'value'=>'TEST-PERSONAL-0001','valid_from'=>'2026-01-01','actor'=>$actorId,'now'=>$now]);
            $identifierId = (int) $pdo->lastInsertId();
            $pdo->prepare('UPDATE personnel_identifiers SET valid_to=:date,ended_by=:actor,ended_at=:now WHERE id=:id')->execute(['date'=>'2026-02-01','actor'=>$actorId,'now'=>$now,'id'=>$identifierId]);
            $neverReuseRejected = false;
            try {
                $insertIdentifier->execute(['personnel_id'=>$personId,'type_id'=>$personal['id'],'policy'=>$personal['enforce_global_unique'],'value'=>'TEST-PERSONAL-0001','valid_from'=>'2026-02-01','actor'=>$actorId,'now'=>$now]);
            } catch (PDOException) {
                $neverReuseRejected = true;
            }
            personnel_check($neverReuseRejected, 'runtime rejects historical personal-number reuse');
            $callSign = $types['call_sign'];
            $insertIdentifier->execute(['personnel_id'=>$personId,'type_id'=>$callSign['id'],'policy'=>$callSign['enforce_global_unique'],'value'=>'TEST-CALLSIGN','valid_from'=>'2026-01-01','actor'=>$actorId,'now'=>$now]);
            $callId = (int) $pdo->lastInsertId();
            $pdo->prepare('UPDATE personnel_identifiers SET valid_to=:date,ended_by=:actor,ended_at=:now WHERE id=:id')->execute(['date'=>'2026-02-01','actor'=>$actorId,'now'=>$now,'id'=>$callId]);
            $reuseAllowed = true;
            try {
                $insertIdentifier->execute(['personnel_id'=>$personId,'type_id'=>$callSign['id'],'policy'=>$callSign['enforce_global_unique'],'value'=>'TEST-CALLSIGN','valid_from'=>'2026-02-01','actor'=>$actorId,'now'=>$now]);
            } catch (PDOException) {
                $reuseAllowed = false;
            }
            personnel_check($reuseAllowed, 'runtime allows historical call-sign reuse');
            $event = $pdo->prepare("INSERT INTO personnel_change_events(personnel_id,actor_user_id,event_type,target_type,target_id,occurred_at) VALUES(:personnel_id,:actor,'test.event','personnel_record',:target_id,:now)");
            $event->execute(['personnel_id'=>$personId,'actor'=>$actorId,'target_id'=>$personId,'now'=>$now]);
            $eventId = (int) $pdo->lastInsertId();
            $eventUpdateRejected = false;
            try {
                $pdo->prepare("UPDATE personnel_change_events SET event_type='test.changed' WHERE id=:id")->execute(['id'=>$eventId]);
            } catch (PDOException) {
                $eventUpdateRejected = true;
            }
            personnel_check($eventUpdateRejected, 'runtime rejects event mutation');
            $deleteRejected = false;
            try {
                $pdo->prepare('DELETE FROM personnel_records WHERE id=:id')->execute(['id'=>$personId]);
            } catch (PDOException) {
                $deleteRejected = true;
            }
            personnel_check($deleteRejected, 'runtime rejects PersonnelRecord physical delete');
        } finally {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
        }
    }
}

if ($failures !== []) {
    fwrite(STDERR, sprintf("PERSONNEL_CORE_CARD_V1_CHECK=FAIL (%d failures, %d passes)\n", count($failures), $passes));
    exit(1);
}
echo "PERSONNEL_CORE_CARD_V1_CHECK=PASS\n";
echo "PASS_COUNT={$passes}\n";
echo 'RUNTIME=' . ($runRuntime ? 'RUN' : 'NOT_RUN') . "\n";
