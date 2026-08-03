<?php

declare(strict_types=1);

function military_rank_v2_sql_template(string $fileName): string
{
    $path = __DIR__ . '/' . $fileName;
    $sql = file_get_contents($path);
    if ($sql === false || trim($sql) === '') {
        throw new RuntimeException("Не удалось загрузить SQL-шаблон migration 012: {$fileName}.");
    }
    return $sql;
}

/** @param list<string> $fileNames */
function military_rank_v2_sql_templates(array $fileNames): string
{
    $parts = [];
    foreach ($fileNames as $fileName) {
        $parts[] = military_rank_v2_sql_template($fileName);
    }
    return implode("\n", $parts);
}

function military_rank_v2_trigger_sql(): string
{
    return military_rank_v2_sql_templates([
        'triggers-a.sql',
        'triggers-b.sql',
        'triggers-c.sql',
        'triggers-d.sql',
        'triggers-e.sql',
        'triggers-f.sql',
        'triggers-g.sql',
    ]);
}

function military_rank_v2_publication_sql(): string
{
    return military_rank_v2_sql_template('publication.sql');
}
