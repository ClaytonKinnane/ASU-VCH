<?php

declare(strict_types=1);

require_once __DIR__ . '/StaffingCreateUpdateTrait.php';
require_once __DIR__ . '/StaffingDocumentTrait.php';
require_once __DIR__ . '/StaffingLifecycleTrait.php';
require_once __DIR__ . '/StaffingSlotTrait.php';
require_once __DIR__ . '/StaffingSupportTrait.php';

final class StaffingService
{
    use StaffingCreateUpdateTrait;
    use StaffingDocumentTrait;
    use StaffingLifecycleTrait;
    use StaffingSlotTrait;
    use StaffingSupportTrait;

    public function __construct(private readonly PDO $pdo)
    {
    }
}
