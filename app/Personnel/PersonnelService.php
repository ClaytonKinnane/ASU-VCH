<?php

declare(strict_types=1);

require_once __DIR__ . '/PersonnelSupportTrait.php';
require_once __DIR__ . '/PersonnelCreateUpdateTrait.php';
require_once __DIR__ . '/PersonnelIdentifierTrait.php';
require_once __DIR__ . '/PersonnelLifecycleTrait.php';

final class PersonnelService
{
    use PersonnelSupportTrait;
    use PersonnelCreateUpdateTrait;
    use PersonnelIdentifierTrait;
    use PersonnelLifecycleTrait;

    public function __construct(private readonly PDO $pdo)
    {
    }
}
