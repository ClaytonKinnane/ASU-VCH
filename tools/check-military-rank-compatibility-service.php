<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once dirname(__DIR__) . '/app/Directory/MilitaryRankCompatibilityService.php';

final class MilitaryRankCompatibilityFakeStatement extends PDOStatement
{
    /** @var array<string,mixed> */
    private array $params = [];
    private mixed $result = false;

    /** @param callable(string,array<string,mixed>):mixed $resolver */
    public function __construct(
        private readonly string $sql,
        private readonly Closure $resolver
    ) {
    }

    public function execute(?array $params = null): bool
    {
        $this->params = is_array($params) ? $params : [];
        $this->result = ($this->resolver)($this->sql, $this->params);
        return true;
    }

    public function fetch(
        int $mode = PDO::FETCH_DEFAULT,
        int $cursorOrientation = PDO::FETCH_ORI_NEXT,
        int $cursorOffset = 0
    ): mixed {
        return is_array($this->result) ? $this->result : false;
    }

    public function fetchColumn(int $column = 0): mixed
    {
        return $this->result;
    }
}

final class MilitaryRankCompatibilityFakePdo extends PDO
{
    /** @var array<int,string> */
    private array $versions = [1 => 'superseded', 2 => 'published', 3 => 'building'];

    /** @var array<int,array{catalog:int,parent:?int,selectable:?int}> */
    private array $compositions = [
        101 => ['catalog' => 1, 'parent' => null, 'selectable' => null],
        201 => ['catalog' => 2, 'parent' => null, 'selectable' => 0],
        202 => ['catalog' => 2, 'parent' => 201, 'selectable' => 1],
        203 => ['catalog' => 2, 'parent' => 201, 'selectable' => 1],
        204 => ['catalog' => 2, 'parent' => null, 'selectable' => 1],
        205 => ['catalog' => 2, 'parent' => 204, 'selectable' => 0],
        206 => ['catalog' => 2, 'parent' => 204, 'selectable' => 0],
        207 => ['catalog' => 2, 'parent' => 208, 'selectable' => 1],
        208 => ['catalog' => 2, 'parent' => 207, 'selectable' => 0],
    ];

    /** @var array<int,array{catalog:int,composition:int}> */
    private array $ranks = [
        1001 => ['catalog' => 1, 'composition' => 101],
        2001 => ['catalog' => 2, 'composition' => 202],
        2002 => ['catalog' => 2, 'composition' => 203],
        2003 => ['catalog' => 2, 'composition' => 205],
        2004 => ['catalog' => 2, 'composition' => 207],
    ];

    public function __construct()
    {
    }

    public function prepare(string $query, array $options = []): PDOStatement|false
    {
        return new MilitaryRankCompatibilityFakeStatement(
            $query,
            fn (string $sql, array $params): mixed => $this->resolve($sql, $params)
        );
    }

    /** @param array<string,mixed> $params */
    private function resolve(string $sql, array $params): mixed
    {
        if (str_contains($sql, 'SELECT lifecycle_status FROM military_rank_catalog_versions')) {
            return $this->versions[(int) ($params['catalog_version_id'] ?? 0)] ?? false;
        }

        if (str_contains($sql, 'SELECT c.id, c.parent_id, s.is_staffing_selectable')) {
            $id = (int) ($params['composition_id'] ?? 0);
            $catalog = (int) ($params['catalog_version_id'] ?? 0);
            $composition = $this->compositions[$id] ?? null;
            if (!is_array($composition) || $composition['catalog'] !== $catalog) {
                return false;
            }
            return [
                'id' => $id,
                'parent_id' => $composition['parent'],
                'is_staffing_selectable' => $composition['selectable'],
            ];
        }

        if (str_contains($sql, 'COUNT(*) FROM military_personnel_compositions')) {
            return isset($this->compositions[(int) ($params['composition_id'] ?? 0)]) ? 1 : 0;
        }

        if (str_contains($sql, 'SELECT composition_id FROM military_rank_levels')) {
            $id = (int) ($params['rank_level_id'] ?? 0);
            $catalog = (int) ($params['catalog_version_id'] ?? 0);
            $rank = $this->ranks[$id] ?? null;
            return is_array($rank) && $rank['catalog'] === $catalog ? $rank['composition'] : false;
        }

        if (str_contains($sql, 'COUNT(*) FROM military_rank_levels')) {
            return isset($this->ranks[(int) ($params['rank_level_id'] ?? 0)]) ? 1 : 0;
        }

        if (str_contains($sql, 'SELECT parent_id FROM military_personnel_compositions')) {
            $id = (int) ($params['composition_id'] ?? 0);
            $catalog = (int) ($params['catalog_version_id'] ?? 0);
            $composition = $this->compositions[$id] ?? null;
            if (!is_array($composition) || $composition['catalog'] !== $catalog) {
                return false;
            }
            return $composition['parent'];
        }

        throw new RuntimeException('Fake PDO получил неизвестный SQL-контракт.');
    }
}

function compatibility_check(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

try {
    $service = new MilitaryRankCompatibilityService(new MilitaryRankCompatibilityFakePdo());

    compatibility_check($service->check(2, 202, 2001) === MilitaryRankCompatibilityService::COMPATIBLE, 'Same composition failed.');
    compatibility_check($service->check(2, 204, 2003) === MilitaryRankCompatibilityService::COMPATIBLE, 'Ancestor composition failed.');
    compatibility_check($service->check(2, 202, 2002) === MilitaryRankCompatibilityService::INCOMPATIBLE, 'Incompatible branches failed.');
    compatibility_check($service->check(2, 205, 2003) === MilitaryRankCompatibilityService::COMPOSITION_NOT_SELECTABLE, 'Non-selectable scope failed.');
    compatibility_check($service->check(1, 101, 1001) === MilitaryRankCompatibilityService::COMPOSITION_NOT_SELECTABLE, 'Legacy v1 semantics failed.');
    compatibility_check($service->check(2, 101, 2001) === MilitaryRankCompatibilityService::INVALID_CATALOG_VERSION, 'Cross-version composition failed.');
    compatibility_check($service->check(2, 202, 1001) === MilitaryRankCompatibilityService::INVALID_CATALOG_VERSION, 'Cross-version rank failed.');
    compatibility_check($service->check(3, 202, 2001) === MilitaryRankCompatibilityService::INVALID_CATALOG_VERSION, 'Building version failed.');
    compatibility_check($service->check(2, 999, 2001) === MilitaryRankCompatibilityService::RECORD_NOT_FOUND, 'Missing composition failed.');
    compatibility_check($service->check(2, 202, 9999) === MilitaryRankCompatibilityService::RECORD_NOT_FOUND, 'Missing rank failed.');
    compatibility_check($service->check(2, 207, 2004) === MilitaryRankCompatibilityService::COMPATIBLE, 'Cycle same-node case failed.');
    compatibility_check($service->check(2, 202, 2004) === MilitaryRankCompatibilityService::INTEGRITY_ERROR, 'Cycle detection failed.');

    echo "MILITARY RANK COMPATIBILITY SERVICE CHECK PASSED\n";
    exit(0);
} catch (Throwable $exception) {
    fwrite(STDERR, 'MILITARY RANK COMPATIBILITY SERVICE CHECK FAILED: ' . $exception->getMessage() . PHP_EOL);
    exit(1);
}
